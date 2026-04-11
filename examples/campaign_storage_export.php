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
use Hunjian\AliyunImsMixcut\Storage\CampaignRunReportRowMapper;
use Hunjian\AliyunImsMixcut\Storage\CsvJobRecordExporter;
use Hunjian\AliyunImsMixcut\Storage\JsonCampaignRunReportExporter;
use Hunjian\AliyunImsMixcut\Storage\JobRecordRowMapper;
use Hunjian\AliyunImsMixcut\Storage\StorageFileWriter;
use Hunjian\AliyunImsMixcut\Template\BatchRandomMixcutTemplate;

$config = ImsConfig::fromEnv()
    ->setBucket(getenv('ALIYUN_IMS_BUCKET') ?: 'demo-bucket')
    ->setOutputPathPrefix(getenv('ALIYUN_IMS_OUTPUT_PATH_PREFIX') ?: 'mixcut/examples/storage');

$theme = (new ThemeConfig('storage-demo'))
    ->setWatermark('oss://demo-bucket/materials/logo.png')
    ->setOutputPattern('oss://demo-bucket/out/{campaign}/{episode}/{n}.mp4');

$pool = new MaterialPool();
$pool->add(Material::video('oss://demo-bucket/materials/v1.mp4', 10)->setSubtitle('产品卖点 A'));
$pool->add(Material::image('oss://demo-bucket/materials/i1.jpg')->setSubtitle('主视觉 1'));

$strategy = (new StrategyBuilder())
    ->transitions(array('fade'))
    ->filters(array('warm'))
    ->vfx(array('glow'))
    ->clipDurations(array(2.5, 3.0));

$campaign = (new CampaignPlan('storage-demo-2026'))
    ->setTheme($theme)
    ->setMetadata(array('owner' => 'ops-team'))
    ->addEpisode(
        (new EpisodePlan('episode-a', new BatchRandomMixcutTemplate()))
            ->setPool($pool)
            ->setStrategy($strategy)
            ->setCount(2)
            ->setSceneCount(2)
            ->setMetadata(array('channel' => 'douyin'))
    );

$adapter = ImsClientFactory::createAdapter($config);
$mediaService = new MediaProducingService(new ImsJobClient($config, $adapter));
$campaignService = new CampaignProducingService($mediaService);
$report = $campaignService->runCampaign($campaign, true, 0, 1);

$campaignRow = (new CampaignRunReportRowMapper())->map($report);
$jobRowMapper = new JobRecordRowMapper();
$jobRows = array();
foreach ($report->getRecords() as $record) {
    $jobRows[] = $jobRowMapper->map($record);
}

echo "Campaign row:\n";
print_r($campaignRow);

echo "Job rows:\n";
print_r($jobRows);

$json = (new JsonCampaignRunReportExporter())->export($report);
$csv = (new CsvJobRecordExporter())->export($report->getRecords());

$writer = new StorageFileWriter();
$writer->write(__DIR__ . '/../tmp/campaign-report.json', $json);
$writer->write(__DIR__ . '/../tmp/job-records.csv', $csv);

echo "Wrote files:\n";
echo " - tmp/campaign-report.json\n";
echo " - tmp/job-records.csv\n";
