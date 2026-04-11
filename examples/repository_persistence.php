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
use Hunjian\AliyunImsMixcut\Repository\JsonFileCampaignRunReportRepository;
use Hunjian\AliyunImsMixcut\Repository\JsonFileJobRecordRepository;
use Hunjian\AliyunImsMixcut\Repository\MySqlCampaignRunReportSchema;
use Hunjian\AliyunImsMixcut\Repository\MySqlJobRecordSchema;
use Hunjian\AliyunImsMixcut\Service\CampaignProducingService;
use Hunjian\AliyunImsMixcut\Service\MediaProducingService;
use Hunjian\AliyunImsMixcut\Template\BatchRandomMixcutTemplate;

$config = ImsConfig::fromEnv()
    ->setBucket(getenv('ALIYUN_IMS_BUCKET') ?: 'demo-bucket')
    ->setOutputPathPrefix(getenv('ALIYUN_IMS_OUTPUT_PATH_PREFIX') ?: 'mixcut/examples/repository');

$theme = (new ThemeConfig('repo-demo'))
    ->setOutputPattern('oss://demo-bucket/out/{campaign}/{episode}/{n}.mp4');

$pool = new MaterialPool();
$pool->add(Material::video('oss://demo-bucket/materials/v1.mp4', 10)->setSubtitle('产品卖点 A'));
$pool->add(Material::image('oss://demo-bucket/materials/i1.jpg')->setSubtitle('主视觉 1'));

$strategy = (new StrategyBuilder())
    ->transitions(array('fade'))
    ->filters(array('warm'))
    ->vfx(array('glow'))
    ->clipDurations(array(2.5, 3.0));

$campaign = (new CampaignPlan('repo-demo-2026'))
    ->setTheme($theme)
    ->setMetadata(array('owner' => 'ops-team'))
    ->addEpisode(
        (new EpisodePlan('episode-a', new BatchRandomMixcutTemplate()))
            ->setPool($pool)
            ->setStrategy($strategy)
            ->setCount(2)
            ->setSceneCount(2)
    );

$adapter = ImsClientFactory::createAdapter($config);
$mediaService = new MediaProducingService(new ImsJobClient($config, $adapter));
$campaignService = new CampaignProducingService($mediaService);
$report = $campaignService->runCampaign($campaign, true, 0, 1);

$jobRepo = new JsonFileJobRecordRepository(__DIR__ . '/../tmp/job-records');
$reportRepo = new JsonFileCampaignRunReportRepository(__DIR__ . '/../tmp/campaign-reports');

$jobRepo->saveMany($report->getRecords());
$reportPath = $reportRepo->save($report);

echo "Saved campaign report to: {$reportPath}\n";
echo "Suggested MySQL DDL:\n";
echo (new MySqlJobRecordSchema())->createTableSql('ims_job_records') . "\n\n";
echo (new MySqlCampaignRunReportSchema())->createTableSql('ims_campaign_reports') . "\n";
