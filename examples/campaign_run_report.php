<?php

require is_file(__DIR__ . '/../vendor/autoload.php')
    ? __DIR__ . '/../vendor/autoload.php'
    : __DIR__ . '/../tests/bootstrap.php';

use Hunjian\AliyunImsMixcut\Builder\StrategyBuilder;
use Hunjian\AliyunImsMixcut\Client\ImsClientFactory;
use Hunjian\AliyunImsMixcut\Client\ImsJobClient;
use Hunjian\AliyunImsMixcut\Config\ImsConfig;
use Hunjian\AliyunImsMixcut\Model\CampaignPlan;
use Hunjian\AliyunImsMixcut\Model\EpisodePlan;
use Hunjian\AliyunImsMixcut\Model\Material;
use Hunjian\AliyunImsMixcut\Model\MaterialPool;
use Hunjian\AliyunImsMixcut\Model\ThemeConfig;
use Hunjian\AliyunImsMixcut\Service\CampaignProducingService;
use Hunjian\AliyunImsMixcut\Service\MediaProducingService;
use Hunjian\AliyunImsMixcut\Template\BatchRandomMixcutTemplate;

$config = ImsConfig::fromEnv()
    ->setBucket(getenv('ALIYUN_IMS_BUCKET') ?: 'demo-bucket')
    ->setOutputPathPrefix(getenv('ALIYUN_IMS_OUTPUT_PATH_PREFIX') ?: 'mixcut/examples/report');

$theme = (new ThemeConfig('summer-launch'))
    ->setWatermark('oss://demo-bucket/materials/logo.png')
    ->setGlobalFilter('warm')
    ->setGlobalVfx('glow')
    ->setBgmPool(array(
        'oss://demo-bucket/materials/bgm-1.mp3',
        'oss://demo-bucket/materials/bgm-2.mp3',
    ))
    ->setOutputPattern('oss://demo-bucket/out/{campaign}/{episode}/{n}.mp4');

$pool = new MaterialPool();
$pool->add(Material::video('oss://demo-bucket/materials/v1.mp4', 10)->setSubtitle('产品卖点 A'));
$pool->add(Material::video('oss://demo-bucket/materials/v2.mp4', 9)->setSubtitle('产品卖点 B'));
$pool->add(Material::image('oss://demo-bucket/materials/i1.jpg')->setSubtitle('主视觉 1'));

$strategy = (new StrategyBuilder())
    ->transitions(array('fade', 'directional-left'))
    ->filters(array('warm', 'movie'))
    ->vfx(array('glow'))
    ->clipDurations(array(2.5, 3.0, 3.5));

$campaign = (new CampaignPlan('summer-launch-2026'))
    ->setTheme($theme)
    ->setMetadata(array(
        'owner' => 'content-team',
        'channelGroup' => 'short-video',
    ))
    ->addEpisode(
        (new EpisodePlan('product-highlights', new BatchRandomMixcutTemplate()))
            ->setPool($pool)
            ->setStrategy($strategy)
            ->setCount(2)
            ->setSceneCount(3)
            ->setMetadata(array('channel' => 'douyin'))
    );

$adapter = ImsClientFactory::createAdapter($config);
$mediaService = new MediaProducingService(new ImsJobClient($config, $adapter));
$campaignService = new CampaignProducingService($mediaService);

$report = $campaignService->runCampaign($campaign, true, 0, 1);

echo json_encode($report->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
