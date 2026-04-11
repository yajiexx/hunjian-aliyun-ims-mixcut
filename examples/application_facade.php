<?php

require is_file(__DIR__ . '/../vendor/autoload.php')
    ? __DIR__ . '/../vendor/autoload.php'
    : __DIR__ . '/../tests/bootstrap.php';

use Hunjian\AliyunImsMixcut\Application\ImsApplicationFactory;
use Hunjian\AliyunImsMixcut\Builder\StrategyBuilder;
use Hunjian\AliyunImsMixcut\Model\CampaignPlan;
use Hunjian\AliyunImsMixcut\Model\EpisodePlan;
use Hunjian\AliyunImsMixcut\Model\Material;
use Hunjian\AliyunImsMixcut\Model\MaterialPool;
use Hunjian\AliyunImsMixcut\Model\ThemeConfig;
use Hunjian\AliyunImsMixcut\Template\BatchRandomMixcutTemplate;

$theme = (new ThemeConfig('facade-demo'))
    ->setWatermark('oss://demo-bucket/materials/logo.png')
    ->setGlobalFilter('warm')
    ->setOutputPattern('oss://demo-bucket/out/{campaign}/{episode}/{n}.mp4');

$pool = new MaterialPool();
$pool->add(Material::video('oss://demo-bucket/materials/v1.mp4', 10)->setSubtitle('产品卖点 A'));
$pool->add(Material::image('oss://demo-bucket/materials/i1.jpg')->setSubtitle('主视觉 1'));

$strategy = (new StrategyBuilder())
    ->transitions(array('fade', 'directional-left'))
    ->filters(array('warm', 'movie'))
    ->vfx(array('glow'))
    ->clipDurations(array(2.5, 3.0));

$campaign = (new CampaignPlan('facade-demo-2026'))
    ->setTheme($theme)
    ->setMetadata(array('owner' => 'content-team'))
    ->addEpisode(
        (new EpisodePlan('episode-a', new BatchRandomMixcutTemplate()))
            ->setPool($pool)
            ->setStrategy($strategy)
            ->setCount(2)
            ->setSceneCount(2)
    );

// 本地先用 stub；真实环境安装官方 SDK 后可改成 official 或直接省略。
$app = ImsApplicationFactory::fromEnv(array(
    'preferred_adapter' => 'stub',
    'storage_dir' => __DIR__ . '/../tmp/application-facade',
));

$result = $app->runCampaignAndStore($campaign, true, 0, 1);

$jsonPath = $app->exportCampaignReportJsonTo(
    $result['report'],
    __DIR__ . '/../tmp/application-facade/campaign-report.export.json'
);

$csvPath = $app->exportJobRecordsCsvTo(
    $result['report']->getRecords(),
    __DIR__ . '/../tmp/application-facade/job-records.export.csv'
);

echo "Report path: {$result['reportPath']}\n";
echo "Record count: " . count($result['recordPaths']) . "\n";
echo "Exported JSON: {$jsonPath}\n";
echo "Exported CSV: {$csvPath}\n";
