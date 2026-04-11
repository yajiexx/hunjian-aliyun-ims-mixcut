<?php

require is_file(__DIR__ . '/../vendor/autoload.php')
    ? __DIR__ . '/../vendor/autoload.php'
    : __DIR__ . '/../tests/bootstrap.php';

use Hunjian\AliyunImsMixcut\Client\ImsClientFactory;
use Hunjian\AliyunImsMixcut\Client\ImsJobClient;
use Hunjian\AliyunImsMixcut\Config\ImsConfig;
use Hunjian\AliyunImsMixcut\Model\ThemeConfig;
use Hunjian\AliyunImsMixcut\Service\MediaProducingService;
use Hunjian\AliyunImsMixcut\Template\AiNarrationImageVideoTemplate;

$config = ImsConfig::fromEnv()
    ->setBucket(getenv('ALIYUN_IMS_BUCKET') ?: 'demo-bucket')
    ->setOutputPathPrefix(getenv('ALIYUN_IMS_OUTPUT_PATH_PREFIX') ?: 'mixcut/examples');

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
        array('type' => 'image', 'url' => 'oss://demo-bucket/materials/poster-1.jpg', 'duration' => 2.8),
        array('type' => 'video', 'url' => 'oss://demo-bucket/materials/clip-1.mp4', 'duration' => 3.2, 'transition' => 'fade'),
        array('type' => 'image', 'url' => 'oss://demo-bucket/materials/poster-2.jpg', 'duration' => 2.6),
    ),
    'narrationText' => '这是一条图片加视频混合的 AI 讲解短视频，由 AI 配音并自动生成字幕。',
    'manualSubtitleSegments' => array(
        array('text' => '支持 AI 配音、ASR 字幕和重点高亮', 'start' => 0.0, 'end' => 3.0, 'referenceClipId' => 'scene-0'),
    ),
));

echo "Timeline JSON:\n";
echo $built['timeline']->toJson(true) . PHP_EOL;

$adapter = ImsClientFactory::createAdapter($config);
$service = new MediaProducingService(new ImsJobClient($config, $adapter));
$job = $service->submitTimeline($built['timeline'], $built['outputMediaConfig']);

echo "Submitted JobId: " . $job->getJobId() . PHP_EOL;
