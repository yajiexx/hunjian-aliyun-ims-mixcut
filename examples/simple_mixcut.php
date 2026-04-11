<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Hunjian\AliyunImsMixcut\Config\ImsConfig;
use Hunjian\AliyunImsMixcut\Client\ImsClientFactory;
use Hunjian\AliyunImsMixcut\Client\ImsJobClient;
use Hunjian\AliyunImsMixcut\Service\MediaProducingService;
use Hunjian\AliyunImsMixcut\Builder\TimelineBuilder;
use Hunjian\AliyunImsMixcut\Model\VideoTrack;
use Hunjian\AliyunImsMixcut\Model\VideoTrackClip;
use Hunjian\AliyunImsMixcut\Model\OutputMediaConfig;
use Hunjian\AliyunImsMixcut\Model\EffectTrack;
use Hunjian\AliyunImsMixcut\Model\EffectTrackItem;
use Hunjian\AliyunImsMixcut\Model\AudioTrack;
use Hunjian\AliyunImsMixcut\Model\AudioTrackClip;
use Hunjian\AliyunImsMixcut\Model\SubtitleTrack;
use Hunjian\AliyunImsMixcut\Model\SubtitleTrackClip;
use Hunjian\AliyunImsMixcut\Model\FontFace;
use Hunjian\AliyunImsMixcut\Model\Effect\Background;
use Hunjian\AliyunImsMixcut\Model\Effect\VFX;
use Hunjian\AliyunImsMixcut\Model\Effect\Volume;
use Hunjian\AliyunImsMixcut\Model\Effect\AFade;
use Hunjian\AliyunImsMixcut\Model\Effect\ADenoise;
use Hunjian\AliyunImsMixcut\Model\Effect\ALoudNorm;
use Hunjian\AliyunImsMixcut\Model\Effect\AI_ASR;
use Hunjian\AliyunImsMixcut\Model\Effect\AI_TTS;
use Hunjian\AliyunImsMixcut\Model\Effect\Filter;
use Hunjian\AliyunImsMixcut\Model\Effect\KenBurns;
use Hunjian\AliyunImsMixcut\Model\Effect\Crop;
use Hunjian\AliyunImsMixcut\Model\Effect\Transition;
use Hunjian\AliyunImsMixcut\Model\Effect\AEqualize;

/**
 * 简单混剪示例
 *
 * 本示例展示如何使用 SDK 构建一个包含多个视频片段、背景音乐、
 * AI 旁白和字幕的基本时间线，并提交到 IMS 进行制作。
 *
 * 运行前请确保已通过环境变量配置 AccessKey 和相关参数：
 * - ALIYUN_IMS_ACCESS_KEY_ID
 * - ALIYUN_IMS_ACCESS_KEY_SECRET
 * - ALIYUN_IMS_REGION_ID
 * - ALIYUN_IMS_ENDPOINT
 * - ALIYUN_IMS_PROJECT_ID
 * - ALIYUN_IMS_BUCKET
 */

// 创建 IMS 配置（从环境变量加载）
$config = ImsConfig::fromEnv();

// 创建适配器并实例化作业客户端
$adapter = ImsClientFactory::createAdapter($config);
$client = new ImsJobClient($config, $adapter);
$service = new MediaProducingService($client);

// 定义视频素材 URL
$video1 = 'https://example.com/video1.mp4';
$video2 = 'https://example.com/video2.mp4';
$video3 = 'https://example.com/video3.mp4';
$video4 = 'https://example.com/video4.mp4';
$bgm1 = 'https://example.com/bgm1.mp3';
$bgm2 = 'https://example.com/bgm2.mp3';

// 创建时间线构建器
$timelineBuilder = TimelineBuilder::make()
    ->portrait(1080, 1920); // 设置竖屏画布

// ==================== 构建视频轨道 ====================
// 创建主视频轨道
$mainVideoTrack = new VideoTrack();
$mainVideoTrack->setMainTrack(true);
$mainVideoTrack->setTrackShortenMode('ShortenFromEnd');

// 添加第一个视频片段
$clip1 = VideoTrackClip::fromMediaUrl($video1)
    ->setSourceRange(0, 5.0) // 从源视频的 0-5 秒截取
    ->setTimelineRange(0, 5.0)
    ->setLayout(0, 0, 1080, 1920, 'Cover') // 全屏覆盖布局
    ->setZOrder(1);

$clip1->addEffect(Volume::gain(-6.0)); // 降低原视频音量
$mainVideoTrack->addClip($clip1);

// 添加第二个视频片段
$clip2 = VideoTrackClip::fromMediaUrl($video2)
    ->setSourceRange(10.0, 18.0)
    ->setTimelineRange(5.0, 13.0)
    ->setLayout(0, 0, 1080, 1920, 'Cover')
    ->setZOrder(1)
    ->addEffect(Volume::gain(-6.0));
$mainVideoTrack->addClip($clip2);

// 添加图片片段（作为过渡或占位）
$imageClip = VideoTrackClip::image('https://example.com/placeholder.jpg', 2.0)
    ->setTimelineRange(13.0, 15.0)
    ->setLayout(0, 0, 1080, 1920, 'Cover')
    ->setZOrder(1)
    ->addEffect(KenBurns::make('0|0|1080|1920', '50|50|1030|1870')); // 添加肯本效果
$mainVideoTrack->addClip($imageClip);

// 添加第三个视频片段
$clip3 = VideoTrackClip::fromMediaUrl($video3)
    ->setSourceRange(0, 8.0)
    ->setTimelineRange(15.0, 23.0)
    ->setLayout(0, 0, 1080, 1920, 'Cover')
    ->setZOrder(1)
    ->addEffect(Volume::gain(-6.0));
$mainVideoTrack->addClip($clip3);

// 添加第四个视频片段（带裁剪效果）
$clip4 = VideoTrackClip::fromMediaUrl($video4)
    ->setSourceRange(5.0, 15.0)
    ->setTimelineRange(23.0, 33.0)
    ->setLayout(0, 0, 1080, 1920, 'Cover')
    ->setZOrder(1)
    ->addEffect(Crop::rect(100, 100, 880, 1680)) // 裁剪中间区域
    ->addEffect(Volume::gain(-6.0));
$mainVideoTrack->addClip($clip4);

// 将主视频轨道添加到时间线
$timelineBuilder->addVideoTrack($mainVideoTrack);

// ==================== 构建音频轨道 ====================
// 创建背景音乐轨道
$bgmTrack = new AudioTrack();

// 添加背景音乐 1
$bgmClip1 = AudioTrackClip::fromMediaUrl($bgm1)
    ->setLoopMode('Loop') // 循环播放
    ->addEffect(Volume::gain(-10.0)) // 降低 BGM 音量
    ->addEffect(ADenoise::make('medium')) // 降噪
    ->addEffect(ALoudNorm::make()); // 响度标准化
$bgmTrack->addClip($bgmClip1);

// 添加 AI 旁白
$narrationClip = AudioTrackClip::fromTts(AI_TTS::fromText(
    '欢迎观看本期内容，今天我们将为大家介绍如何使用阿里云智能媒体服务进行视频制作。',
    'xiaoyun'
))
    ->setTimelineRange(0.0, 33.0)
    ->addEffect(AFade::make('In', 0.5)) // 淡入
    ->addEffect(AFade::make('Out', 0.8)) // 淡出
    ->addEffect(ADenoise::make('medium'))
    ->addEffect(ALoudNorm::make())
    ->addEffect(AEqualize::make(1200, 200, 2.0)); // 增强人声
$bgmTrack->addClip($narrationClip);

// 添加 ASR 字幕生成效果
$asrClip = AudioTrackClip::fromMediaUrl($video1)
    ->setSourceRange(0, 5.0)
    ->addEffect(AI_ASR::make('zh-CN', array(
        'SubtitleFormat' => 'ass',
        'SubtitleColor' => '#FFFF00',
    )));
$bgmTrack->addClip($asrClip);

// 将音频轨道添加到时间线
$timelineBuilder->addAudioTrack($bgmTrack);

// ==================== 构建字幕轨道 ====================
// 创建字幕轨道
$subtitleTrack = new SubtitleTrack();

// 添加字幕片段
$subtitle1 = SubtitleTrackClip::text('欢迎观看本期内容', 0.5, 3.0)
    ->setStyle('Alibaba PuHuiTi 2.0', 56, '#FFFFFF', FontFace::bold())
    ->setLayout(54, 1500, 972, 180, 'BottomCenter')
    ->setAutoWrap(true)
    ->setFixedFontSize(true)
    ->setReferenceClipId('clip-001');
$subtitleTrack->addClip($subtitle1);

$subtitle2 = SubtitleTrackClip::text('今天我们将为大家介绍', 3.5, 6.0)
    ->setStyle('Alibaba PuHuiTi 2.0', 56, '#FFFFFF', FontFace::bold())
    ->setLayout(54, 1500, 972, 180, 'BottomCenter')
    ->setAutoWrap(true)
    ->setFixedFontSize(true)
    ->setReferenceClipId('clip-002');
$subtitleTrack->addClip($subtitle2);

// 将字幕轨道添加到时间线
$timelineBuilder->addSubtitleTrack($subtitleTrack);

// ==================== 构建效果轨道 ====================
// 创建全局效果轨道
$effectTrack = new EffectTrack();

// 添加全局背景模糊效果（用于主视频轨道）
$bgEffect = new EffectTrackItem('Background', 'Blur', array(
    'Radius' => 30,
    'X' => 0,
    'Y' => 0,
    'Width' => 1080,
    'Height' => 1920,
));
$bgEffect->setRange(0.0, 33.0);
$effectTrack->addItem($bgEffect);

// 添加全局 VFX 效果
$vfxEffect = new EffectTrackItem('VFX', 'Mosaic', array(
    'X' => 900,
    'Y' => 100,
    'Width' => 100,
    'Height' => 100,
));
$vfxEffect->setRange(5.0, 13.0); // 只在第二个片段应用马赛克
$effectTrack->addItem($vfxEffect);

// 添加全局滤镜
$filterEffect = new EffectTrackItem('Filter', 'Vagetable', array(
    'Brightness' => 10,
    'Contrast' => 15,
));
$filterEffect->setRange(15.0, 33.0);
$effectTrack->addItem($filterEffect);

// 将效果轨道添加到时间线
$timelineBuilder->addEffectTrack($effectTrack);

// ==================== 添加全局元素 ====================
// 添加背景图片
$timelineBuilder->withGlobalImage(
    'https://example.com/background.jpg',
    0, 0, 1080, 1920,
    0.0, null
);

// 添加水印
$timelineBuilder->withWatermark(
    'https://example.com/watermark.png',
    900, 50, 120, 120,
    0.0, null
);

// 添加全局滤镜
$timelineBuilder->withGlobalFilter(
    'Natural',
    0.0,
    null,
    array('Brightness' => 5)
);

// 添加全局 VFX
$timelineBuilder->withGlobalVfx(
    'Mosaic',
    20.0,
    25.0,
    array('X' => 800, 'Y' => 800, 'Width' => 200, 'Height' => 200)
);

// ==================== 构建并提交作业 ====================
// 创建输出配置
$outputConfig = OutputMediaConfig::oss(
    'oss://your-bucket/output/simple_mixcut_' . date('YmdHis') . '.mp4'
);

// 提交时间线作业
echo "正在提交作业...\n";
$jobResult = $service->submitTimeline(
    $timelineBuilder->buildTimeline(),
    $outputConfig
);

echo "作业 ID: " . $jobResult->getJobId() . "\n";
echo "作业状态: " . $jobResult->getStatus() . "\n";

// 轮询直到完成
echo "正在等待作业完成...\n";
$result = $service->waitUntilFinished($jobResult->getJobId(), 5, 300);

echo "轮询次数: " . $result->getAttempts() . "\n";
echo "耗时: " . $result->getElapsedSeconds() . " 秒\n";

if ($result->getJobResult()->isFinished()) {
    echo "作业完成！\n";
    echo "输出媒体 URL: " . $result->getJobResult()->getMediaUrl() . "\n";
} else {
    echo "作业失败，状态: " . $result->getJobResult()->getStatus() . "\n";
}

// 如果使用 Stub 适配器（无需真实 API 密钥）
echo "\n\n========== Stub 适配器示例 ==========\n";
$stubAdapter = new \Hunjian\AliyunImsMixcut\Client\Adapter\StubAdapter();
$stubClient = new ImsJobClient($config, $stubAdapter);
$stubService = new MediaProducingService($stubClient);

$stubResult = $stubService->submitTimeline(
    $timelineBuilder->buildTimeline(),
    $outputConfig
);

echo "Stub 作业 ID: " . $stubResult->getJobId() . "\n";
echo "Stub 作业状态: " . $stubResult->getStatus() . "\n";
echo "作业是否完成: " . ($stubResult->isFinished() ? '是' : '否') . "\n";
