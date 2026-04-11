<?php

require is_file(__DIR__ . '/../vendor/autoload.php')
    ? __DIR__ . '/../vendor/autoload.php'
    : __DIR__ . '/../tests/bootstrap.php';

use Hunjian\AliyunImsMixcut\Builder\StrategyBuilder;
use Hunjian\AliyunImsMixcut\Builder\BatchTaskListBuilder;
use Hunjian\AliyunImsMixcut\Client\ImsClientFactory;
use Hunjian\AliyunImsMixcut\Client\ImsJobClient;
use Hunjian\AliyunImsMixcut\Config\ImsConfig;
use Hunjian\AliyunImsMixcut\Model\Material;
use Hunjian\AliyunImsMixcut\Model\MaterialPool;
use Hunjian\AliyunImsMixcut\Model\ThemeConfig;
use Hunjian\AliyunImsMixcut\Service\BatchProducingService;
use Hunjian\AliyunImsMixcut\Service\MediaProducingService;
use Hunjian\AliyunImsMixcut\Template\BatchRandomMixcutTemplate;

$config = ImsConfig::fromEnv()
    ->setBucket(getenv('ALIYUN_IMS_BUCKET') ?: 'demo-bucket')
    ->setOutputPathPrefix(getenv('ALIYUN_IMS_OUTPUT_PATH_PREFIX') ?: 'mixcut/examples/batch');

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
$pool->add(Material::video('oss://demo-bucket/materials/v3.mp4', 8)->setSubtitle('视频素材 3'));
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
    ->setOutputPattern('oss://demo-bucket/mixcut/examples/batch/{theme}-{n}.mp4');

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

$adapter = ImsClientFactory::createAdapter($config);
$service = new MediaProducingService(new ImsJobClient($config, $adapter));
$batchService = new BatchProducingService($service);
$jobs = $batchService->submitBatch($tasks);

foreach ($jobs as $index => $job) {
    echo sprintf("Job #%d => %s (%s)\n", $index + 1, $job->getJobId(), $job->getStatus());
}
