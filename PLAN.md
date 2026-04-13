# Scene Mixcut 全组合候选模式计划

## Summary
- 保留现有 `submitSceneMixcut()` 单片语义和现有 scene 内多素材叠层能力不变。
- 新增“候选素材”模式，把部分 scene 视为可选镜头位；这些 scene 之间做笛卡尔积，全组合批量出片。
- 新增批量入口 `MediaProducingService::submitSceneMixcutBatch(array $context = array(), array $options = array())`，返回 `JobRecord[]`，每条结果都带组合元数据。

## Key Changes
- 输入协议新增 scene 级字段：
  - `materialMode`: `layered` 或 `candidates`，默认 `layered`
  - `durationMode`: 仅 `candidates` scene 可用，取值 `dubbing` / `fixed` / `materialOriginal`
  - `fixedDuration`: `durationMode=fixed` 时必填
  - `startMode`: 仅 `candidates` scene 可用，取值 `fromStart` / `smartRandom` / `custom`
- 候选素材规则：
  - `candidates` scene 的 `materials[]` v1 只允许 `video`
  - 候选 material 的 `duration` 解释为源素材原始时长，`startMode=custom` 时新增 `customStart`
  - `durationMode=materialOriginal` 时只允许 `startMode=fromStart`；其他组合直接校验失败
  - `durationMode=dubbing` 时必须提供 `dubbing.duration`
- 组合展开规则：
  - 只对 `materialMode=candidates` 的 scenes 做笛卡尔积
  - `layered` scenes 会原样复制到每条组合里
  - 每条组合里，每个候选 scene 只保留 1 个被选中的 material，再复用现有 `SceneMixcutTemplate` 单条构建链路
- 时间线规则：
  - `fromStart` 生成 `sourceRange.in = 0`
  - `custom` 生成 `sourceRange.in = material.customStart`
  - `smartRandom` 每次提交真随机选起点；实现中允许注入随机源，测试时改为桩值
  - 若目标时长小于可播时长，自动裁剪 `sourceRange.out`
  - 若目标时长大于可播时长，给该视频 clip 加 `FreezeFrame` 效果补尾帧，并移除该 clip 的 transition
  - 冻结尾帧先依赖 IMS 官方 effect 表达；若运行验证不支持，则本次组合报错，不做静默降级
- 字幕/花字规则：
  - scene 级 `subtitles` / `wordArts` 复制到每条组合
  - `referenceMaterialId` 命中当前选中素材时，照常映射为 `ReferenceClipId`
  - 若该引用素材未被本条组合选中，保留字幕内容，但去掉 `ReferenceClipId`
- 输出与返回：
  - 批量接口自动从传入的 `outputMediaURL` 或 `outputMediaConfig` 派生输出地址，在扩展名前追加 `-001`、`-002` 这种 3 位序号
  - 每条返回记录使用 `JobRecord`，`metadata` 至少包含 `comboIndex`、`comboKey`、`selectedMaterials`、`outputMediaUrl`
  - 现有 `TemplateInterface` 不改；批量逻辑通过新增组合展开器和 service 层封装完成

## Implementation Changes
- 在 `src/Scene` 增加一个组合展开器，例如 `SceneCombinationExpander`，负责：
  - 校验 `candidates` scene 配置
  - 产出每条组合对应的单片 context
  - 为每条组合写入 metadata 和派生输出地址
- 扩展现有三段式链路：
  - `ScenePayloadNormalizer` 识别新字段并做模式组合校验
  - `SceneDurationResolver` 支持候选 scene 的三种时长模式
  - `SceneTimelineAssembler` 支持候选 clip 的起播点、裁剪、冻结尾帧、transition 去除
- 在 `src/Service/MediaProducingService.php` 新增 `submitSceneMixcutBatch()`
  - 内部生成 `BatchTask[]`
  - 复用现有 `BatchProducingService::submitBatch()`
  - 将 `BatchTask + JobResult` 映射成 `JobRecord[]`
- 新增一个轻量视频效果封装，例如 `src/Model/Effect/FreezeFrame.php`；若实现时发现通用 `Effect` 已足够，则保持 helper 极简
- 同步更新场景混剪文档，明确：
  - `layered` 与 `candidates` 的边界
  - v1 只支持视频候选
  - `materialOriginal` 只能从头播
  - 冻结尾帧与 transition 的优先级

## Test Plan
- 现有 layered scene 用例继续通过，`submitSceneMixcut()` 返回结构不变
- `scene1=2`、`scene2=3` 的候选组合生成 6 条任务/6 条提交结果
- 候选 scene + layered scene 混合时，layered scene 在所有组合中保持一致
- `durationMode=dubbing`、`fixed`、`materialOriginal` 分别生成正确时长
- `startMode=fromStart`、`custom`、`smartRandom` 分别生成正确 `sourceRange`
- `materialOriginal + smartRandom/custom` 报稳定错误码和 path
- 候选 scene 中出现 `image` 报稳定错误码和 path
- `referenceMaterialId` 指向未选中素材时，字幕保留但 `ReferenceClipId` 为空
- 目标时长超出可播时长时，payload 带 `FreezeFrame`，且该 clip 不再带 transition
- 批量接口返回 `JobRecord[]`，每条 `metadata.selectedMaterials` 与输出 URL 序号正确

## Assumptions
- 默认新增字段命名使用当前代码风格的小驼峰：`materialMode`、`durationMode`、`fixedDuration`、`startMode`、`customStart`
- `smartRandom` 对外语义是真随机；测试通过注入随机源或封装随机函数实现可断言
- 冻结尾帧以 IMS 官方 effect 为第一实现路径，不在 v1 引入本地抽帧/静帧素材生成
- 批量接口是新增能力，不改变任何现有单片调用方
