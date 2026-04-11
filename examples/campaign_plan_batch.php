<?php

require is_file(__DIR__ . '/../vendor/autoload.php')
    ? __DIR__ . '/../vendor/autoload.php'
    : __DIR__ . '/../tests/bootstrap.php';

use Hunjian\AliyunImsMixcut\Builder\CampaignTaskListBuilder;
use Hunjian\AliyunImsMixcut\Builder\StrategyBuilder;
use Hunjian\AliyunImsMixcut\Client\ImsClientFactory;
use Hunjian\AliyunImsMixcut\Client\ImsJobClient;
use Hunjian\AliyunImsMixcut\Config\ImsConfig;
use Hunjian\AliyunImsMixcut\Model\CampaignPlan;
use Hunjian\AliyunImsMixcut\Model\EpisodePlan;
use Hunjian\AliyunImsMixcut\Model\Material;
use Hunjian\AliyunImsMixcut\Model\MaterialPool;
use Hunjian\AliyunImsMixcut\Model\ThemeConfig;
use Hunjian\AliyunImsMixcut\Service\BatchProducingService;
use Hunjian\AliyunImsMixcut\Service\MediaProducingService;
use Hunjian\AliyunImsMixcut\Template\BatchRandomMixcutTemplate;

$config = ImsConfig::fromEnv()
    ->setBucket(getenv('ALIYUN_IMS_BUCKET') ?: 'demo-bucket')
    ->setOutputPathPrefix(getenv('ALIYUN_IMS_OUTPUT_PATH_PREFIX') ?: 'mixcut/examples/campaign');

$theme = (new ThemeConfig('summer-launch'))
    ->setVoice('zhitian_emo')
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
    ->setOutputPattern('oss://demo-bucket/out/{campaign}/{episode}/{n}.mp4');

$generalPool = new MaterialPool();
$generalPool->add(Material::video('oss://demo-bucket/materials/v1.mp4', 10)->setSubtitle('产品卖点 A'));
$generalPool->add(Material::video('oss://demo-bucket/materials/v2.mp4', 9)->setSubtitle('产品卖点 B'));
$generalPool->add(Material::image('oss://demo-bucket/materials/i1.jpg')->setSubtitle('主视觉 1'));

$educationPool = new MaterialPool();
$educationPool->add(Material::video('oss://demo-bucket/materials/e1.mp4', 12)->setSubtitle('教程片段 A'));
$educationPool->add(Material::image('oss://demo-bucket/materials/e2.jpg')->setSubtitle('教程封面'));

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

foreach ($tasks as $task) {
    echo sprintf(
        "Prepared task => campaign=%s episode=%s seq=%s output=%s\n",
        $task->getMetadata()['campaign'],
        $task->getMetadata()['episode'],
        $task->getMetadata()['sequence'],
        $task->getBuiltOutputMediaConfig()->toArray()['MediaURL']
    );
}

$adapter = ImsClientFactory::createAdapter($config);
$service = new MediaProducingService(new ImsJobClient($config, $adapter));
$batchService = new BatchProducingService($service);
$jobs = $batchService->submitBatch($tasks);

foreach ($jobs as $index => $job) {
    echo sprintf("Submitted Job #%d => %s (%s)\n", $index + 1, $job->getJobId(), $job->getStatus());
}
