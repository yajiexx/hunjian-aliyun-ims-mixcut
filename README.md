# aliyun-ims-mixcut

一个面向业务项目的 PHP 7.3 兼容 composer 包，用来封装阿里云智能媒体服务 IMS 的 AI 混剪能力。包的核心目标不是“演示如何调一个接口”，而是把 `SubmitMediaProducingJob` / `GetMediaProducingJob` 包装成一套可复用的 Timeline Builder、模板化混剪、批量随机混剪、结果查询和异常处理方案。

## 设计目标

- PHP 7.3 兼容，不使用 PHP 8+ 语法
- PSR-4 自动加载
- Timeline 结构尽量贴近阿里云官方 JSON
- 业务方通过 Builder 和模板类构建 Timeline，而不是手拼数组
- 客户端层通过 Adapter 隔离官方 SDK，便于后续升级
- 保留 `extraFields/raw` 扩展能力，用于承接官方新增字段

## 架构概览

### 1. 模型层

模型层尽量保持与阿里云官方 Timeline 结构一致：

- `Timeline`
- `VideoTrack` / `VideoTrackClip`
- `AudioTrack` / `AudioTrackClip`
- `SubtitleTrack` / `SubtitleTrackClip`
- `EffectTrack` / `EffectTrackItem`
- `OutputMediaConfig`

所有对象都实现 `toArray()`，最终可以稳定序列化为官方接口所需的数组 / JSON。

### 2. 局部效果和全局效果分离

- Clip 内效果：挂在 `VideoTrackClip::Effects` 或 `AudioTrackClip::Effects`
  - 例如 `Transition`、`Filter`、`VFX`、`Crop`、`KenBurns`
  - 这类效果只影响某个素材片段
- 全局效果：放在 `EffectTracks`
  - 例如 `GlobalImage`、全局滤镜、全局特效、水印
  - 这类效果对整条时间线或指定时间范围生效

这种分法适合 AI 混剪，因为随机化策略通常需要独立控制：

- 某个片段自己的滤镜 / 转场 / KenBurns
- 整条视频统一的风格滤镜 / 全局背景 / 水印

### 3. Builder 层

Builder 负责把“好写”翻译成“官方结构”：

- `TimelineBuilder`：统筹多轨、全局效果、输出配置
- `SubtitleBuilder`：字幕样式、背景框、描边、阴影、AutoWrap、FixedFontSize
- `AudioBuilder`：BGM、AI 配音、AFade、ADenoise、ALoudNorm、AEqualize
- `EffectTrackBuilder`：全局背景图、全局水印、全局滤镜、全局特效
- `Randomizer` / `StrategyBuilder`：素材顺序、截取区间、转场、滤镜、特效、BGM、字幕风格的随机化
- `MixcutTemplateBuilder`：模板类复用的高层组合器

### 4. 模板层

内置 3 个可直接使用的模板类：

- `PortraitMixcutTemplate`
- `AiNarrationImageVideoTemplate`
- `BatchRandomMixcutTemplate`

### 5. 客户端 / 服务层

- `ImsClientFactory`：创建官方 SDK 适配器或本地 Stub
- `ImsJobClient`：负责 submit/query 的标准化
- `MediaProducingService`：提交、查询、轮询
- `BatchProducingService`：批量提交

### 6. 业务 DTO 层

为了让真实项目接入更顺手，额外补了一层业务 DTO：

- `Material`：单个素材 DTO
- `MaterialPool`：素材池
- `ThemeConfig`：主题 / 活动级默认配置
- `BatchTask`：单个批量任务
- `BatchTaskListBuilder`：批量任务清单生成器

这样你的业务系统可以先维护素材池和批量任务，再由模板去消费，而不是每次都手拼 `pool` / `seed` / `outputMediaConfig` 数组。

### 7. Adapter 隔离

官方 SDK 相关逻辑全部放在：

- `src/Client/Adapter/OfficialIceAdapter.php`

当前实现按官方文档和示例使用以下类名：

- `AlibabaCloud\\SDK\\ICE\\V20201109\\ICE`
- `AlibabaCloud\\SDK\\ICE\\V20201109\\Models\\SubmitMediaProducingJobRequest`
- `AlibabaCloud\\SDK\\ICE\\V20201109\\Models\\GetMediaProducingJobRequest`
- `Darabonba\\OpenApi\\Models\\Config`
- `AlibabaCloud\\Credentials\\Credential`

如果阿里云后续调整 SDK 类名或命名空间，只需要修改这个 Adapter，不需要改 Builder / 模板 / 业务层。

### 8. 应用装配层

如果你不想在业务项目里自己反复 `new config/client/service/repository/exporter`，现在还提供：

- `ImsApplication`
- `ImsApplicationFactory`

这一层会统一装配：

- `ImsJobClient`
- `MediaProducingService`
- `BatchProducingService`
- `CampaignProducingService`
- `JsonFileJobRecordRepository`
- `JsonFileCampaignRunReportRepository`
- `CsvJobRecordExporter`
- `JsonCampaignRunReportExporter`

默认情况下，结果会按目录约定落到：

- `runtime/ims/job-records`
- `runtime/ims/campaign-reports`

## 安装

```bash
composer require hunjian/aliyun-ims-mixcut
```

如果你需要真的调用阿里云接口，还需要安装官方 SDK 依赖：

```bash
composer require alibabacloud/ice-20201109
```

说明：

- `alibabacloud/ice-20201109`：IMS/ICE OpenAPI SDK，Composer 会自动安装它依赖的 `alibabacloud/credentials` 和 OpenAPI 相关包
- 如果你因为私有镜像、锁文件或排障需要手动补装 OpenAPI 模型包，请使用 `alibabacloud/darabonba-openapi`，不要写成 `darabonba/openapi`

## 环境变量

不要把 AccessKeyId / AccessKeySecret 硬编码进源码。

```dotenv
ALIYUN_IMS_ACCESS_KEY_ID=your-access-key-id
ALIYUN_IMS_ACCESS_KEY_SECRET=your-access-key-secret
ALIYUN_IMS_ENDPOINT=ice.cn-shanghai.aliyuncs.com
ALIYUN_IMS_REGION_ID=cn-shanghai
ALIYUN_IMS_BUCKET=your-output-bucket
ALIYUN_IMS_OUTPUT_PATH_PREFIX=mixcut/output
ALIYUN_IMS_PROJECT_ID=your-ims-project-id
ALIYUN_IMS_OUTPUT_MEDIA_TARGET=oss-object
ALIYUN_IMS_STORAGE_DIR=runtime/ims
```

`ImsConfig::fromEnv()` 会自动读取以上变量，也兼容常见的阿里云通用 AccessKey 变量名。

## 快速开始

```php
<?php

use Hunjian\AliyunImsMixcut\Client\ImsClientFactory;
use Hunjian\AliyunImsMixcut\Client\ImsJobClient;
use Hunjian\AliyunImsMixcut\Config\ImsConfig;
use Hunjian\AliyunImsMixcut\Service\MediaProducingService;
use Hunjian\AliyunImsMixcut\Template\PortraitMixcutTemplate;

$config = ImsConfig::fromEnv();
$adapter = ImsClientFactory::createAdapter($config);
$client = new ImsJobClient($config, $adapter);
$service = new MediaProducingService($client);

$template = new PortraitMixcutTemplate();
$built = $template->build(array(
    'mainVideo' => 'oss://demo-bucket/materials/main.mp4',
    'bgm' => 'oss://demo-bucket/materials/bgm.mp3',
    'watermark' => 'oss://demo-bucket/materials/logo.png',
    'subtitleSegments' => array(
        array('text' => '第一句字幕', 'start' => 0.0, 'end' => 2.0, 'referenceClipId' => 'main-video'),
        array('text' => '第二句字幕', 'start' => 2.0, 'end' => 4.0, 'referenceClipId' => 'main-video'),
    ),
));

$job = $service->submitTimeline($built['timeline'], $built['outputMediaConfig']);
echo $job->getJobId();
```

## 更省事的统一入口

如果你在业务项目里希望只实例化一个入口对象，建议直接用 `ImsApplicationFactory`：

```php
<?php

use Hunjian\AliyunImsMixcut\Application\ImsApplicationFactory;
use Hunjian\AliyunImsMixcut\Template\PortraitMixcutTemplate;

$app = ImsApplicationFactory::fromEnv(array(
    'preferred_adapter' => 'stub',
    'storage_dir' => __DIR__ . '/runtime/ims',
));

$template = new PortraitMixcutTemplate();
$job = $app->submitLocalTemplate($template, array(
    'mainVideo' => 'oss://demo-bucket/materials/main.mp4',
    'bgm' => 'oss://demo-bucket/materials/bgm.mp3',
));

echo $job->getJobId();
```

说明：

- 本地联调可以先用 `preferred_adapter => 'stub'`
- 真实提交时，安装官方 SDK 后可改为 `preferred_adapter => 'official'`，或者直接省略，让工厂自动判断
- `storage_dir` 不传时默认用 `runtime/ims`

## 如何创建一个竖屏 AI 混剪任务

`PortraitMixcutTemplate` 适合：

- 横转竖
- 主视频 + 字幕 + BGM + 水印
- 模糊背景
- 三分屏 / 分屏布局

```php
<?php

use Hunjian\AliyunImsMixcut\Template\PortraitMixcutTemplate;

$template = new PortraitMixcutTemplate();
$built = $template->build(array(
    'mainVideo' => 'oss://demo-bucket/materials/main-landscape.mp4',
    'layout' => 'three_split',
    'splitVideos' => array(
        'oss://demo-bucket/materials/main-landscape.mp4',
        'oss://demo-bucket/materials/clip-1.mp4',
        'oss://demo-bucket/materials/clip-2.mp4',
    ),
    'blurRadius' => 28,
    'bgm' => 'oss://demo-bucket/materials/bgm.mp3',
    'watermark' => 'oss://demo-bucket/materials/logo.png',
    'subtitleSegments' => array(
        array('text' => '横屏转竖屏模板', 'start' => 0.0, 'end' => 2.0, 'referenceClipId' => 'main-video'),
        array('text' => '支持模糊背景和三分屏', 'start' => 2.0, 'end' => 4.0, 'referenceClipId' => 'main-video'),
    ),
));
```

对应可运行示例：

- `examples/simple_mixcut.php`

## 如何创建一个图片/视频混合 + AI 配音 + AI 字幕任务

`AiNarrationImageVideoTemplate` 适合：

- 图片 / 视频混编
- AI_TTS 配音
- AI_ASR 自动字幕
- 重点字幕高亮
- 转场 + KenBurns

```php
<?php

use Hunjian\AliyunImsMixcut\Model\ThemeConfig;
use Hunjian\AliyunImsMixcut\Template\AiNarrationImageVideoTemplate;

$theme = (new ThemeConfig('narration-brand'))
    ->setVoice('zhitian_emo')
    ->setBgm('oss://demo-bucket/materials/bgm-soft.mp3')
    ->setWatermark('oss://demo-bucket/materials/logo.png')
    ->setGlobalFilter('warm')
    ->setGlobalVfx('glow')
    ->setHighlight(array(
        'Color' => '#F8E16C',
        'BorderColor' => '#1D3557',
    ))
    ->setSubtitleStyle(array(
        'font' => 'Alibaba PuHuiTi 2.0',
        'fontSize' => 50,
        'fontColor' => '#FFFFFF',
        'boxColor' => '#101820',
    ));

$template = new AiNarrationImageVideoTemplate();
$built = $template->build(array(
    'theme' => $theme,
    'materials' => array(
        array('type' => 'image', 'url' => 'oss://demo-bucket/materials/p1.jpg', 'duration' => 2.5),
        array('type' => 'video', 'url' => 'oss://demo-bucket/materials/v1.mp4', 'duration' => 3.0, 'transition' => 'fade'),
        array('type' => 'image', 'url' => 'oss://demo-bucket/materials/p2.jpg', 'duration' => 2.8),
    ),
    'narrationText' => '这是一条由 AI 配音生成的图文混剪短视频。',
    'ssml' => '<speak>这是一条 <emphasis>AI 配音</emphasis> 视频</speak>',
));
```

对应可运行示例：

- `examples/ai_narration_mixcut.php`

## 如何创建一个批量随机混剪任务

`BatchRandomMixcutTemplate` 支持：

- 素材池
- 随机素材顺序
- 随机片段截取区间
- 随机转场
- 随机滤镜 / 特效
- 随机 BGM
- 随机字幕样式
- 同主题批量生成多个不同 Timeline

```php
<?php

use Hunjian\AliyunImsMixcut\Builder\BatchTaskListBuilder;
use Hunjian\AliyunImsMixcut\Builder\StrategyBuilder;
use Hunjian\AliyunImsMixcut\Model\Material;
use Hunjian\AliyunImsMixcut\Model\MaterialPool;
use Hunjian\AliyunImsMixcut\Model\ThemeConfig;
use Hunjian\AliyunImsMixcut\Template\BatchRandomMixcutTemplate;

$strategy = (new StrategyBuilder())
    ->transitions(array('fade', 'directional-left', 'directional-up'))
    ->filters(array('warm', 'cool', 'movie'))
    ->vfx(array('shake', 'glow', 'chromatic'))
    ->bgms(array(
        'oss://demo-bucket/materials/bgm-1.mp3',
        'oss://demo-bucket/materials/bgm-2.mp3',
    ))
    ->subtitleStyles(array(
        array('fontColor' => '#FFFFFF', 'boxColor' => '#000000'),
        array('fontColor' => '#F8E16C', 'boxColor' => '#1D3557'),
    ))
    ->clipDurations(array(2.5, 3.0, 3.5));

$pool = new MaterialPool();
$pool->add(Material::video('oss://demo-bucket/materials/v1.mp4', 10)->setSubtitle('视频素材 1'));
$pool->add(Material::video('oss://demo-bucket/materials/v2.mp4', 9)->setSubtitle('视频素材 2'));
$pool->add(Material::image('oss://demo-bucket/materials/i1.jpg')->setSubtitle('图片素材 1'));
$pool->add(Material::image('oss://demo-bucket/materials/i2.jpg')->setSubtitle('图片素材 2'));

$theme = (new ThemeConfig('spring-campaign'))
    ->setWatermark('oss://demo-bucket/materials/logo.png')
    ->setGlobalFilter('warm')
    ->setGlobalVfx('glow')
    ->setBgmPool(array(
        'oss://demo-bucket/materials/bgm-1.mp3',
        'oss://demo-bucket/materials/bgm-2.mp3',
    ))
    ->setSubtitleStyle(array(
        'fontColor' => '#F8E16C',
        'boxColor' => '#1D3557',
    ))
    ->setOutputPattern('oss://demo-bucket/out/{theme}-{n}.mp4');

$template = new BatchRandomMixcutTemplate();
$tasks = (new BatchTaskListBuilder())
    ->template($template)
    ->pool($pool)
    ->strategy($strategy)
    ->theme($theme)
    ->count(3)
    ->sceneCount(4)
    ->seed(20260411)
    ->build();
```

对应可运行示例：

- `examples/batch_random_mixcut.php`

如果你需要直接提交：

```php
$jobs = $batchService->submitBatch($tasks);
```

`BatchProducingService` 现在同时支持两种输入：

- 旧格式数组
- `BatchTask` 对象

## 主题 / 活动配置对象

如果你的业务是“同一主题下生成多条统一风格视频”，建议把公共配置收敛到 `ThemeConfig`：

```php
<?php

use Hunjian\AliyunImsMixcut\Model\ThemeConfig;

$theme = (new ThemeConfig('spring-campaign'))
    ->setVoice('zhitian_emo')
    ->setBgm('oss://demo-bucket/materials/default-bgm.mp3')
    ->setBgmPool(array(
        'oss://demo-bucket/materials/bgm-1.mp3',
        'oss://demo-bucket/materials/bgm-2.mp3',
    ))
    ->setWatermark('oss://demo-bucket/materials/logo.png')
    ->setGlobalFilter('warm')
    ->setGlobalVfx('glow')
    ->setSubtitleStyle(array(
        'font' => 'Alibaba PuHuiTi 2.0',
        'fontSize' => 50,
        'fontColor' => '#FFFFFF',
        'boxColor' => '#101820',
    ))
    ->setHighlight(array(
        'Color' => '#F8E16C',
    ))
    ->setOutputPattern('oss://demo-bucket/out/{theme}-{n}.mp4');
```

这层对象现在已经能直接作用于：

- `AiNarrationImageVideoTemplate`
- `PortraitMixcutTemplate`
- `BatchRandomMixcutTemplate`
- `BatchTaskListBuilder`

覆盖顺序是：

- 单次任务 `context` 显式传入值
- `ThemeConfig` 默认值
- 模板内部默认值

## 投放计划对象

如果你的业务是“一个活动里有多组内容、每组批量生产、并且需要记录任务来源”，可以直接用：

- `CampaignPlan`
- `EpisodePlan`
- `CampaignTaskListBuilder`

`CampaignPlan` 负责活动级信息：

- 活动名 / 批次名
- 主题配置
- 活动级元数据
- 多个 `EpisodePlan`

`EpisodePlan` 负责每个内容分组：

- 使用哪个模板
- 这一组素材池
- 这一组随机策略
- 生成数量
- 分组级元数据

示例：

```php
<?php

use Hunjian\AliyunImsMixcut\Builder\CampaignTaskListBuilder;
use Hunjian\AliyunImsMixcut\Model\CampaignPlan;
use Hunjian\AliyunImsMixcut\Model\EpisodePlan;
use Hunjian\AliyunImsMixcut\Template\BatchRandomMixcutTemplate;

$campaign = (new CampaignPlan('summer-launch-2026'))
    ->setTheme($theme)
    ->setMetadata(array('owner' => 'content-team'))
    ->addEpisode(
        (new EpisodePlan('product-highlights', new BatchRandomMixcutTemplate()))
            ->setPool($generalPool)
            ->setStrategy($strategy)
            ->setCount(3)
            ->setSceneCount(4)
            ->setMetadata(array('channel' => 'douyin'))
    )
    ->addEpisode(
        (new EpisodePlan('education-series', new BatchRandomMixcutTemplate()))
            ->setPool($educationPool)
            ->setStrategy($strategy)
            ->setCount(2)
            ->setSceneCount(3)
            ->setMetadata(array('channel' => 'kuaishou'))
    );

$tasks = (new CampaignTaskListBuilder())
    ->fromCampaign($campaign)
    ->build();
```

输出命名支持占位符：

- `{campaign}`
- `{episode}`
- `{theme}`
- `{n}`

例如：

```php
$theme->setOutputPattern('oss://demo-bucket/out/{campaign}/{episode}/{n}.mp4');
```

构建出来的 `BatchTask` 会附带 `metadata`，适合后续：

- 落库任务表
- 追踪任务来源
- 做渠道对账
- 回写生成结果

完整示例见：

- `examples/campaign_plan_batch.php`

## 结果回写对象

如果你需要把提交结果落数据库、写任务表、或者给后台页面展示运行概览，这一层现在有：

- `JobRecord`
- `CampaignRunReport`
- `CampaignProducingService`

`JobRecord` 对应一条任务，包含：

- `jobId`
- `status`
- `mediaUrl`
- `outputMediaUrl`
- `metadata`
- `requestPayload`
- `raw`
- `attempts`
- `elapsedSeconds`
- `submittedAt`
- `finishedAt`

`CampaignRunReport` 对应一次活动运行，包含：

- `campaignName`
- `themeName`
- `metadata`
- `summary`
- `records`
- `startedAt`
- `finishedAt`

示例：

```php
<?php

use Hunjian\AliyunImsMixcut\Service\CampaignProducingService;

$campaignService = new CampaignProducingService($mediaService);
$report = $campaignService->runCampaign($campaign, true, 5, 600);

$data = $report->toArray();
```

`runCampaign()` 会：

1. 从 `CampaignPlan` 展开任务
2. 提交所有任务
3. 可选轮询到终态
4. 产出 `CampaignRunReport`

完整示例见：

- `examples/campaign_run_report.php`

## 应用门面 + 持久化

如果你已经用了 `CampaignPlan`，现在可以直接通过 `ImsApplication` 一步完成：

1. 运行活动
2. 可选轮询到完成
3. 落盘 `CampaignRunReport`
4. 落盘每条 `JobRecord`
5. 导出 JSON / CSV

```php
<?php

use Hunjian\AliyunImsMixcut\Application\ImsApplicationFactory;

$app = ImsApplicationFactory::fromEnv(array(
    'preferred_adapter' => 'stub',
    'storage_dir' => __DIR__ . '/runtime/ims',
));

$result = $app->runCampaignAndStore($campaign, true, 0, 1);

$app->exportCampaignReportJsonTo(
    $result['report'],
    __DIR__ . '/runtime/ims/campaign-report.export.json'
);

$app->exportJobRecordsCsvTo(
    $result['report']->getRecords(),
    __DIR__ . '/runtime/ims/job-records.export.csv'
);
```

`runCampaignAndStore()` 返回：

- `report`
- `reportPath`
- `recordPaths`

完整示例见：

- `examples/application_facade.php`

## 存储适配层

如果你不想在业务项目里重复写“结果对象转数据库行 / JSON 文件 / CSV 文件”的扁平化逻辑，现在内置了：

- `JobRecordRowMapper`
- `CampaignRunReportRowMapper`
- `JsonCampaignRunReportExporter`
- `CsvJobRecordExporter`
- `StorageFileWriter`

### 映射数据库行

```php
<?php

use Hunjian\AliyunImsMixcut\Storage\CampaignRunReportRowMapper;
use Hunjian\AliyunImsMixcut\Storage\JobRecordRowMapper;

$campaignRow = (new CampaignRunReportRowMapper())->map($report);

$jobRows = array();
$mapper = new JobRecordRowMapper();
foreach ($report->getRecords() as $record) {
    $jobRows[] = $mapper->map($record);
}
```

`JobRecordRowMapper` 会输出类似字段：

- `job_id`
- `status`
- `media_url`
- `output_media_url`
- `campaign`
- `episode`
- `theme`
- `sequence`
- `attempts`
- `elapsed_seconds`
- `submitted_at`
- `finished_at`
- `metadata_json`
- `request_payload_json`
- `raw_json`

`CampaignRunReportRowMapper` 会输出类似字段：

- `campaign_name`
- `theme_name`
- `total_jobs`
- `finished_jobs`
- `failed_jobs`
- `pending_jobs`
- `started_at`
- `finished_at`
- `metadata_json`
- `summary_json`
- `report_json`

### 导出 JSON / CSV 文件

```php
<?php

use Hunjian\AliyunImsMixcut\Storage\CsvJobRecordExporter;
use Hunjian\AliyunImsMixcut\Storage\JsonCampaignRunReportExporter;
use Hunjian\AliyunImsMixcut\Storage\StorageFileWriter;

$json = (new JsonCampaignRunReportExporter())->export($report);
$csv = (new CsvJobRecordExporter())->export($report->getRecords());

$writer = new StorageFileWriter();
$writer->write(__DIR__ . '/campaign-report.json', $json);
$writer->write(__DIR__ . '/job-records.csv', $csv);
```

完整示例见：

- `examples/campaign_storage_export.php`

## 仓储接口层

如果你希望直接把结果对象存起来，而不是先自己写一层 repository，现在内置了：

- `JobRecordRepositoryInterface`
- `CampaignRunReportRepositoryInterface`
- `JsonFileJobRecordRepository`
- `JsonFileCampaignRunReportRepository`
- `PdoMySqlJobRecordRepository`
- `PdoMySqlCampaignRunReportRepository`
- `MySqlJobRecordSchema`
- `MySqlCampaignRunReportSchema`

### 文件仓储

文件仓储适合：

- 本地开发
- 测试环境
- 轻量后台
- 没有数据库时先跑通链路

```php
<?php

use Hunjian\AliyunImsMixcut\Repository\JsonFileCampaignRunReportRepository;
use Hunjian\AliyunImsMixcut\Repository\JsonFileJobRecordRepository;

$jobRepo = new JsonFileJobRecordRepository(__DIR__ . '/tmp/job-records');
$reportRepo = new JsonFileCampaignRunReportRepository(__DIR__ . '/tmp/campaign-reports');

$jobRepo->saveMany($report->getRecords());
$path = $reportRepo->save($report);
```

也支持读回：

```php
$record = $jobRepo->findByJobId('your-job-id');
$report = $reportRepo->findByPath($path);
```

### PDO / MySQL 仓储

如果你要接 MySQL，可以直接复用内置 schema 和 row mapper：

```php
<?php

use Hunjian\AliyunImsMixcut\Repository\MySqlCampaignRunReportSchema;
use Hunjian\AliyunImsMixcut\Repository\MySqlJobRecordSchema;
use Hunjian\AliyunImsMixcut\Repository\PdoMySqlCampaignRunReportRepository;
use Hunjian\AliyunImsMixcut\Repository\PdoMySqlJobRecordRepository;

$jobSql = (new MySqlJobRecordSchema())->createTableSql('ims_job_records');
$campaignSql = (new MySqlCampaignRunReportSchema())->createTableSql('ims_campaign_reports');

$jobRepo = new PdoMySqlJobRecordRepository($pdo, 'ims_job_records');
$campaignRepo = new PdoMySqlCampaignRunReportRepository($pdo, 'ims_campaign_reports');

$jobRepo->saveMany($report->getRecords());
$campaignRepo->save($report);
```

说明：

- `PdoMySqlJobRecordRepository` 使用 `INSERT ... ON DUPLICATE KEY UPDATE`
- `PdoMySqlCampaignRunReportRepository` 是最小可用插入实现
- 这两者在当前环境只做了语法级验证，没有连真实 MySQL 跑集成测试

完整示例见：

- `examples/repository_persistence.php`

## 业务素材池 API

如果你的业务端已经有“素材中心”或“主题素材池”，建议先映射到 `MaterialPool`：

```php
<?php

use Hunjian\AliyunImsMixcut\Model\Material;
use Hunjian\AliyunImsMixcut\Model\MaterialPool;

$pool = new MaterialPool();
$pool->add(Material::video('oss://demo-bucket/v1.mp4', 12.5)->setSubtitle('卖点片段 A'));
$pool->add(Material::image('oss://demo-bucket/p1.jpg')->setSubtitle('卖点主图'));
$pool->add(Material::audio('oss://demo-bucket/bgm.mp3', 18.0));

$templatePool = $pool->toTemplatePool();
```

导出的结构会自动变成：

- `videos`
- `images`
- `audios`

正好可被随机模板直接消费。

## 如何添加 BGM、字幕、水印、全局滤镜、全局特效

### BGM

```php
$audioBuilder = new AudioBuilder();
$audioBuilder->addBgm('oss://demo-bucket/materials/bgm.mp3', 15.0, true, -12.0);
```

支持：

- 单独音轨混入
- BGM 循环
- 音量衰减
- ALoudNorm

### 字幕

```php
$subtitleBuilder = (new SubtitleBuilder())
    ->style('Alibaba PuHuiTi 2.0', 50, '#FFFFFF')
    ->box('#000000', 0.35, 20)
    ->outline('#111111', 4)
    ->shadow('#000000', 3, 3)
    ->layout(80, 1500, 920, 220, 'BottomCenter')
    ->autoWrap(true)
    ->fixedFontSize(true);
```

支持：

- 普通字幕
- AutoWrap
- FixedFontSize
- 背景框
- 描边
- 阴影
- `ReferenceClipId` 关联画面
- `extra()` 扩展 FECanvas 兼容字段或字幕高亮字段

### 全局背景图 / 水印 / 全局滤镜 / 全局特效

```php
$timelineBuilder
    ->withGlobalImage('oss://demo-bucket/materials/bg.png', 0, 0, 1080, 1920)
    ->withWatermark('oss://demo-bucket/materials/logo.png', 840, 80, 180, 72)
    ->withGlobalFilter('warm')
    ->withGlobalVfx('glow');
```

这些都会进入 `EffectTracks`，不会污染 Clip 内效果。

## 如何使用 AI_TTS / AI_ASR

### AI_TTS

`AI_TTS` 在官方结构里更接近“音频片段类型”而不是普通 Clip Effect，所以本包把它建模为：

- `Model\\Effect\\AI_TTS`
- 通过 `AudioTrackClip::fromTts()` 或 `AudioBuilder::addAiNarration()` 落到 `AudioTrackClip`

```php
$audioBuilder->addAiNarration(
    '这是一个 AI 配音示例',
    'zhitian_emo',
    0.0,
    6.0,
    array(
        'speechRate' => 0,
        'pitchRate' => 0,
        'ssml' => '<speak>这是一个 <emphasis>AI 配音</emphasis> 示例</speak>',
    )
);
```

### AI_ASR

`AI_ASR` 被建模为普通 `Effect`，可以挂在音频片段或视频片段的 `Effects` 上：

```php
$audioBuilder->addAiNarration(
    '这是一个带自动字幕的示例',
    'zhitian_emo',
    0.0,
    8.0,
    array(
        'asr' => array(
            'ReferenceClipId' => 'scene-0',
            'HighlightConfig' => array(
                'Color' => '#F8E16C',
            ),
        ),
    )
);
```

说明：

- `HighlightConfig` 字段在不同租户/版本下可能需要用实际官方字段名微调
- 如果你需要完全透传某个最新字段，请用 `extraFields/raw`

## 如何轮询任务直到完成

```php
$job = $service->submitTimeline($built['timeline'], $built['outputMediaConfig']);
$result = $service->waitUntilFinished($job->getJobId(), 5, 600);

if ($result->getJobResult()->isFinished()) {
    var_dump($result->getJobResult()->getMediaUrl());
}
```

## 常见坑说明

### 1. Timeline 根字段不要乱改名

请尽量保持官方 PascalCase：

- `VideoTracks`
- `AudioTracks`
- `SubtitleTracks`
- `EffectTracks`
- `OutputMediaConfig`

业务层如果想扩展，请走 `raw()/withExtraFields()`，不要把现有字段改成自己的风格。

### 2. Clip 局部效果和全局效果不要混用

- 局部：`VideoTrackClip::Effects` / `AudioTrackClip::Effects`
- 全局：`EffectTracks`

如果把全局水印做成 clip-local，后续随机换素材时很容易错位。

### 3. AI_TTS 不是普通滤镜

官方语义上，AI_TTS 更像音频片段输入。不要把它和 `Filter` / `VFX` 放在一层理解。

### 4. 自动字幕高亮字段要预留扩展

ASR 高亮、字幕样式、FECanvas 兼容字段，不同版本可能有细节差异。这个包已经预留：

- `extra()`
- `raw()`
- `withExtraFields()`

接入时建议先用官方控制台导出一份成功任务的 Timeline，对照本包输出做差异检查。

### 5. 官方 SDK 变化不要向上蔓延

如果阿里云官方 PHP SDK 类名更新，请只改：

- `src/Client/Adapter/OfficialIceAdapter.php`

不要改模板或 Builder。

## 目录结构

```text
.
├─ composer.json
├─ README.md
├─ examples
│  ├─ ai_narration_mixcut.php
│  ├─ application_facade.php
│  ├─ batch_random_mixcut.php
│  ├─ campaign_plan_batch.php
│  ├─ campaign_run_report.php
│  ├─ campaign_storage_export.php
│  ├─ repository_persistence.php
│  └─ simple_mixcut.php
├─ src
│  ├─ Application
│  ├─ Builder
│  ├─ Client
│  ├─ Config
│  ├─ Contracts
│  ├─ Exception
│  ├─ Model
│  ├─ Result
│  ├─ Service
│  ├─ Support
│  └─ Template
└─ tests
   ├─ bootstrap.php
   ├─ run.php
   └─ SmokeTest.php
```

## 本地验证

无需安装额外依赖即可运行 smoke test：

```bash
php tests/run.php
```

如果要做语法检查：

```bash
Get-ChildItem src -Recurse -Filter *.php | ForEach-Object { php -l $_.FullName }
```

## 官方文档参考

- 提交任务：`SubmitMediaProducingJob`
- 查询任务：`GetMediaProducingJob`
- Timeline 配置说明
- Effect 配置说明
- AI 混剪场景说明

本包设计时对齐了这些官方资料，但仍建议你在真实项目接入前，用你自己租户里的一条成功任务做一次对拍。
