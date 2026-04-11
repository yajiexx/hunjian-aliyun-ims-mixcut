<?php

namespace Hunjian\AliyunImsMixcut\Tests;

use Hunjian\AliyunImsMixcut\Application\ImsApplication;
use Hunjian\AliyunImsMixcut\Application\ImsApplicationFactory;
use Hunjian\AliyunImsMixcut\Builder\StrategyBuilder;
use Hunjian\AliyunImsMixcut\Builder\BatchTaskListBuilder;
use Hunjian\AliyunImsMixcut\Builder\CampaignTaskListBuilder;
use Hunjian\AliyunImsMixcut\Builder\TimelineBuilder;
use Hunjian\AliyunImsMixcut\Client\Adapter\StubAdapter;
use Hunjian\AliyunImsMixcut\Client\ImsJobClient;
use Hunjian\AliyunImsMixcut\Model\CampaignPlan;
use Hunjian\AliyunImsMixcut\Model\EpisodePlan;
use Hunjian\AliyunImsMixcut\Config\ImsConfig;
use Hunjian\AliyunImsMixcut\Model\AudioTrack;
use Hunjian\AliyunImsMixcut\Model\AudioTrackClip;
use Hunjian\AliyunImsMixcut\Model\BatchTask;
use Hunjian\AliyunImsMixcut\Model\Material;
use Hunjian\AliyunImsMixcut\Model\MaterialPool;
use Hunjian\AliyunImsMixcut\Model\OutputMediaConfig;
use Hunjian\AliyunImsMixcut\Model\ThemeConfig;
use Hunjian\AliyunImsMixcut\Model\VideoTrack;
use Hunjian\AliyunImsMixcut\Model\VideoTrackClip;
use Hunjian\AliyunImsMixcut\Service\BatchProducingService;
use Hunjian\AliyunImsMixcut\Service\CampaignProducingService;
use Hunjian\AliyunImsMixcut\Service\MediaProducingService;
use Hunjian\AliyunImsMixcut\Template\AiNarrationImageVideoTemplate;
use Hunjian\AliyunImsMixcut\Template\BatchRandomMixcutTemplate;
use Hunjian\AliyunImsMixcut\Template\PortraitMixcutTemplate;
use Hunjian\AliyunImsMixcut\Result\CampaignRunReport;
use Hunjian\AliyunImsMixcut\Result\JobRecord;
use Hunjian\AliyunImsMixcut\Storage\CsvJobRecordExporter;
use Hunjian\AliyunImsMixcut\Storage\CampaignRunReportRowMapper;
use Hunjian\AliyunImsMixcut\Storage\JobRecordRowMapper;
use Hunjian\AliyunImsMixcut\Storage\JsonCampaignRunReportExporter;
use Hunjian\AliyunImsMixcut\Storage\StorageFileWriter;
use Hunjian\AliyunImsMixcut\Repository\JsonFileCampaignRunReportRepository;
use Hunjian\AliyunImsMixcut\Repository\JsonFileJobRecordRepository;
use Hunjian\AliyunImsMixcut\Repository\MySqlCampaignRunReportSchema;
use Hunjian\AliyunImsMixcut\Repository\MySqlJobRecordSchema;

/**
 * Class SmokeTest
 *
 * Very small smoke tests runnable without external dependencies.
 */
class SmokeTest
{
    /**
     * Run all smoke tests.
     *
     * @return array
     */
    public function run()
    {
        return array(
            'timeline_builder_serializes' => $this->testTimelineBuilderSerializes(),
            'portrait_template_builds' => $this->testPortraitTemplateBuilds(),
            'ai_narration_template_builds' => $this->testAiNarrationTemplateBuilds(),
            'batch_random_template_builds' => $this->testBatchRandomTemplateBuilds(),
            'material_pool_builds' => $this->testMaterialPoolBuilds(),
            'theme_config_builds' => $this->testThemeConfigBuilds(),
            'batch_task_list_builder_builds' => $this->testBatchTaskListBuilderBuilds(),
            'batch_task_builder_applies_theme_defaults' => $this->testBatchTaskBuilderAppliesThemeDefaults(),
            'ai_narration_template_uses_theme_defaults' => $this->testAiNarrationTemplateUsesThemeDefaults(),
            'campaign_plan_builds' => $this->testCampaignPlanBuilds(),
            'campaign_task_list_builder_builds' => $this->testCampaignTaskListBuilderBuilds(),
            'campaign_run_report_builds' => $this->testCampaignRunReportBuilds(),
            'campaign_producing_service_runs_campaign' => $this->testCampaignProducingServiceRunsCampaign(),
            'job_record_row_mapper_builds' => $this->testJobRecordRowMapperBuilds(),
            'campaign_run_report_row_mapper_builds' => $this->testCampaignRunReportRowMapperBuilds(),
            'json_campaign_report_exporter_builds' => $this->testJsonCampaignReportExporterBuilds(),
            'csv_job_record_exporter_builds' => $this->testCsvJobRecordExporterBuilds(),
            'storage_file_writer_writes' => $this->testStorageFileWriterWrites(),
            'json_file_job_record_repository_saves_and_finds' => $this->testJsonFileJobRecordRepositorySavesAndFinds(),
            'json_file_campaign_report_repository_saves_and_finds' => $this->testJsonFileCampaignReportRepositorySavesAndFinds(),
            'mysql_schema_builds' => $this->testMySqlSchemaBuilds(),
            'batch_service_submits_task_objects' => $this->testBatchServiceSubmitsTaskObjects(),
            'service_submit_and_query_stub' => $this->testServiceSubmitAndQueryStub(),
            'ims_application_factory_builds' => $this->testImsApplicationFactoryBuilds(),
            'ims_application_runs_and_stores_campaign' => $this->testImsApplicationRunsAndStoresCampaign(),
        );
    }

    /**
     * Assert timeline builder output.
     *
     * @return bool
     */
    public function testTimelineBuilderSerializes()
    {
        $videoTrack = new VideoTrack();
        $videoTrack->setMainTrack(true)->addClip(
            VideoTrackClip::fromMediaUrl('oss://demo/video.mp4')
                ->setDuration(3.0)
                ->setLayout(0, 0, 1080, 1920, 'Cover')
        );

        $audioTrack = new AudioTrack();
        $audioTrack->addClip(AudioTrackClip::fromMediaUrl('oss://demo/bgm.mp3')->setDuration(3.0));

        $builder = TimelineBuilder::make()
            ->portrait(1080, 1920)
            ->addVideoTrack($videoTrack)
            ->addAudioTrack($audioTrack);

        $data = $builder->buildTimeline()->toArray();

        $this->assert(isset($data['VideoTracks'][0]['VideoTrackClips'][0]['MediaURL']), 'VideoTracks missing.');
        $this->assert(isset($data['AudioTracks'][0]['AudioTrackClips'][0]['MediaURL']), 'AudioTracks missing.');
        $this->assert($builder->buildOutputMediaConfig()->toArray()['Width'] === 1080, 'Output width mismatch.');

        return true;
    }

    /**
     * Assert portrait template output.
     *
     * @return bool
     */
    public function testPortraitTemplateBuilds()
    {
        $template = new PortraitMixcutTemplate();
        $built = $template->build(array(
            'mainVideo' => 'oss://demo/main.mp4',
            'bgm' => 'oss://demo/bgm.mp3',
            'watermark' => 'oss://demo/logo.png',
            'subtitleSegments' => array(
                array('text' => '这是第一句字幕', 'start' => 0.0, 'end' => 1.5, 'referenceClipId' => 'main-video'),
                array('text' => '这是第二句字幕', 'start' => 1.5, 'end' => 3.0, 'referenceClipId' => 'main-video'),
            ),
        ));

        $timeline = $built['timeline']->toArray();
        $this->assert(count($timeline['VideoTracks']) >= 2, 'Portrait template should contain background and foreground tracks.');
        $this->assert(!empty($timeline['SubtitleTracks']), 'Portrait template should contain subtitles.');

        return true;
    }

    /**
     * Assert AI narration template output.
     *
     * @return bool
     */
    public function testAiNarrationTemplateBuilds()
    {
        $template = new AiNarrationImageVideoTemplate();
        $built = $template->build(array(
            'materials' => array(
                array('type' => 'image', 'url' => 'oss://demo/1.jpg', 'duration' => 2.5),
                array('type' => 'video', 'url' => 'oss://demo/2.mp4', 'duration' => 3.0),
            ),
            'narrationText' => '这是一个 AI 配音混剪示例。',
            'voice' => 'zhitian_emo',
            'highlight' => array('Color' => '#F8E16C'),
        ));

        $timeline = $built['timeline']->toArray();
        $audioClip = $timeline['AudioTracks'][0]['AudioTrackClips'][0];
        $this->assert($audioClip['Type'] === 'AI_TTS', 'AI narration clip should be AI_TTS.');
        $lastEffect = end($audioClip['Effects']);
        $this->assert($lastEffect['Type'] === 'AI_ASR', 'AI narration should include AI_ASR effect.');

        return true;
    }

    /**
     * Assert batch random template output.
     *
     * @return bool
     */
    public function testBatchRandomTemplateBuilds()
    {
        $template = new BatchRandomMixcutTemplate();
        $strategy = (new StrategyBuilder())
            ->transitions(array('fade'))
            ->filters(array('warm'))
            ->vfx(array('shake'))
            ->bgms(array('oss://demo/a.mp3'))
            ->subtitleStyles(array(array('fontColor' => '#FFFFFF', 'boxColor' => '#000000')))
            ->clipDurations(array(2.0));

        $built = $template->build(array(
            'seed' => 100,
            'strategy' => $strategy,
            'pool' => array(
                'videos' => array(
                    array('type' => 'video', 'url' => 'oss://demo/v1.mp4', 'sourceDuration' => 10, 'subtitle' => '片段 A'),
                    array('type' => 'video', 'url' => 'oss://demo/v2.mp4', 'sourceDuration' => 9, 'subtitle' => '片段 B'),
                ),
                'images' => array(
                    array('type' => 'image', 'url' => 'oss://demo/i1.jpg', 'subtitle' => '图片 1'),
                    array('type' => 'image', 'url' => 'oss://demo/i2.jpg', 'subtitle' => '图片 2'),
                ),
            ),
        ));

        $timeline = $built['timeline']->toArray();
        $this->assert(count($timeline['VideoTracks'][0]['VideoTrackClips']) >= 1, 'Random template should contain clips.');
        $this->assert(!empty($timeline['AudioTracks']), 'Random template should contain BGM.');

        return true;
    }

    /**
     * Assert material pool grouping and export.
     *
     * @return bool
     */
    public function testMaterialPoolBuilds()
    {
        $pool = new MaterialPool();
        $pool->add(Material::video('oss://demo/v1.mp4', 10.0)->setSubtitle('视频一'));
        $pool->add(Material::image('oss://demo/i1.jpg')->setSubtitle('图片一'));
        $pool->add(Material::audio('oss://demo/a1.mp3', 18.0));

        $data = $pool->toTemplatePool();

        $this->assert(count($data['videos']) === 1, 'MaterialPool videos mismatch.');
        $this->assert(count($data['images']) === 1, 'MaterialPool images mismatch.');
        $this->assert(count($data['audios']) === 1, 'MaterialPool audios mismatch.');

        return true;
    }

    /**
     * Assert theme config exports stable values.
     *
     * @return bool
     */
    public function testThemeConfigBuilds()
    {
        $theme = (new ThemeConfig('brand-a'))
            ->setVoice('zhitian_emo')
            ->setBgm('oss://demo/default-bgm.mp3')
            ->setBgmPool(array('oss://demo/bgm-1.mp3', 'oss://demo/bgm-2.mp3'))
            ->setWatermark('oss://demo/logo.png')
            ->setGlobalFilter('warm')
            ->setGlobalVfx('glow')
            ->setOutputPattern('oss://demo-bucket/out/{theme}/{n}.mp4')
            ->setSubtitleStyle(array('fontColor' => '#FFFFFF', 'boxColor' => '#000000'))
            ->setHighlight(array('Color' => '#F8E16C'));

        $this->assert($theme->getVoice() === 'zhitian_emo', 'Theme voice mismatch.');
        $this->assert($theme->resolveOutputMediaUrl(2) === 'oss://demo-bucket/out/brand-a/2.mp4', 'Theme output pattern mismatch.');
        $this->assert($theme->toArray()['subtitleStyle']['fontColor'] === '#FFFFFF', 'Theme subtitle style mismatch.');

        return true;
    }

    /**
     * Assert batch task list generation.
     *
     * @return bool
     */
    public function testBatchTaskListBuilderBuilds()
    {
        $pool = new MaterialPool();
        $pool->add(Material::video('oss://demo/v1.mp4', 10.0)->setSubtitle('视频一'));
        $pool->add(Material::image('oss://demo/i1.jpg')->setSubtitle('图片一'));

        $strategy = (new StrategyBuilder())
            ->transitions(array('fade'))
            ->filters(array('warm'))
            ->vfx(array('shake'))
            ->bgms(array('oss://demo/bgm.mp3'))
            ->subtitleStyles(array(array('fontColor' => '#FFFFFF', 'boxColor' => '#000000')))
            ->clipDurations(array(2.0));

        $tasks = (new BatchTaskListBuilder())
            ->template(new BatchRandomMixcutTemplate())
            ->pool($pool)
            ->strategy($strategy)
            ->count(3)
            ->sceneCount(2)
            ->seed(88)
            ->outputPattern('oss://demo-bucket/out/random-{n}.mp4')
            ->build();

        $this->assert(count($tasks) === 3, 'BatchTaskListBuilder count mismatch.');
        $this->assert($tasks[0] instanceof BatchTask, 'BatchTaskListBuilder should return BatchTask objects.');
        $this->assert($tasks[0]->getContext()['seed'] !== $tasks[1]->getContext()['seed'], 'Seeds should be unique per task.');
        $this->assert($tasks[1]->getBuiltOutputMediaConfig()->toArray()['MediaURL'] === 'oss://demo-bucket/out/random-2.mp4', 'Output pattern mismatch.');

        return true;
    }

    /**
     * Assert batch task builder uses theme defaults.
     *
     * @return bool
     */
    public function testBatchTaskBuilderAppliesThemeDefaults()
    {
        $pool = new MaterialPool();
        $pool->add(Material::video('oss://demo/v1.mp4', 10.0)->setSubtitle('视频一'));
        $theme = (new ThemeConfig('activity-1'))
            ->setVoice('zhitian_emo')
            ->setWatermark('oss://demo/logo.png')
            ->setOutputPattern('oss://demo-bucket/out/{theme}-{n}.mp4');

        $tasks = (new BatchTaskListBuilder())
            ->template(new BatchRandomMixcutTemplate())
            ->pool($pool)
            ->theme($theme)
            ->count(2)
            ->build();

        $this->assert($tasks[0]->getContext()['theme'] instanceof ThemeConfig, 'Theme should be injected into task context.');
        $this->assert($tasks[0]->getBuiltOutputMediaConfig()->toArray()['MediaURL'] === 'oss://demo-bucket/out/activity-1-1.mp4', 'Theme output naming should be applied.');

        return true;
    }

    /**
     * Assert narration template uses theme defaults.
     *
     * @return bool
     */
    public function testAiNarrationTemplateUsesThemeDefaults()
    {
        $theme = (new ThemeConfig('brand-b'))
            ->setVoice('zhitian_emo')
            ->setBgm('oss://demo/theme-bgm.mp3')
            ->setWatermark('oss://demo/theme-logo.png')
            ->setGlobalFilter('warm')
            ->setGlobalVfx('glow')
            ->setHighlight(array('Color' => '#F8E16C'));

        $template = new AiNarrationImageVideoTemplate();
        $built = $template->build(array(
            'theme' => $theme,
            'materials' => array(
                array('type' => 'image', 'url' => 'oss://demo/1.jpg', 'duration' => 2.5),
                array('type' => 'video', 'url' => 'oss://demo/2.mp4', 'duration' => 3.0),
            ),
            'narrationText' => '主题默认值测试',
        ));

        $timeline = $built['timeline']->toArray();
        $audioClip = $timeline['AudioTracks'][0]['AudioTrackClips'][0];
        $this->assert($audioClip['Voice'] === 'zhitian_emo', 'Theme voice should be applied.');
        $this->assert(count($timeline['EffectTracks']) >= 2, 'Theme watermark/filter/vfx should create effect tracks.');

        return true;
    }

    /**
     * Assert campaign plan structure.
     *
     * @return bool
     */
    public function testCampaignPlanBuilds()
    {
        $theme = (new ThemeConfig('campaign-theme'))->setOutputPattern('oss://demo/{campaign}/{episode}/{n}.mp4');
        $pool = new MaterialPool();
        $pool->add(Material::video('oss://demo/v1.mp4', 10.0)->setSubtitle('视频一'));

        $episode = (new EpisodePlan('episode-a', new BatchRandomMixcutTemplate()))
            ->setPool($pool)
            ->setCount(2)
            ->setSceneCount(1)
            ->setMetadata(array('channel' => 'douyin'));

        $campaign = (new CampaignPlan('campaign-2026'))
            ->setTheme($theme)
            ->setMetadata(array('operator' => 'team-a'))
            ->addEpisode($episode);

        $this->assert(count($campaign->getEpisodes()) === 1, 'Campaign should contain one episode.');
        $this->assert($campaign->getMetadata()['operator'] === 'team-a', 'Campaign metadata mismatch.');
        $this->assert($campaign->resolveOutputMediaUrl('episode-a', 2) === 'oss://demo/campaign-2026/episode-a/2.mp4', 'Campaign output pattern mismatch.');

        return true;
    }

    /**
     * Assert campaign plan expands into tasks with metadata.
     *
     * @return bool
     */
    public function testCampaignTaskListBuilderBuilds()
    {
        $theme = (new ThemeConfig('campaign-theme'))->setOutputPattern('oss://demo/{campaign}/{episode}/{n}.mp4');

        $poolA = new MaterialPool();
        $poolA->add(Material::video('oss://demo/v1.mp4', 10.0)->setSubtitle('视频一'));
        $poolB = new MaterialPool();
        $poolB->add(Material::image('oss://demo/i1.jpg')->setSubtitle('图片一'));

        $campaign = (new CampaignPlan('campaign-2026'))
            ->setTheme($theme)
            ->setMetadata(array('project' => 'launch'));

        $campaign->addEpisode(
            (new EpisodePlan('episode-a', new BatchRandomMixcutTemplate()))
                ->setPool($poolA)
                ->setCount(2)
                ->setSceneCount(1)
                ->setMetadata(array('channel' => 'douyin'))
        );

        $campaign->addEpisode(
            (new EpisodePlan('episode-b', new BatchRandomMixcutTemplate()))
                ->setPool($poolB)
                ->setCount(1)
                ->setSceneCount(1)
                ->setMetadata(array('channel' => 'kuaishou'))
        );

        $tasks = (new CampaignTaskListBuilder())->fromCampaign($campaign)->build();

        $this->assert(count($tasks) === 3, 'Campaign should expand to three tasks.');
        $this->assert($tasks[0] instanceof BatchTask, 'Campaign builder should return BatchTask objects.');
        $this->assert($tasks[0]->getMetadata()['campaign'] === 'campaign-2026', 'Campaign metadata should be injected.');
        $this->assert($tasks[0]->getMetadata()['episode'] === 'episode-a', 'Episode metadata should be injected.');
        $this->assert($tasks[2]->getBuiltOutputMediaConfig()->toArray()['MediaURL'] === 'oss://demo/campaign-2026/episode-b/1.mp4', 'Campaign output naming should match placeholders.');

        return true;
    }

    /**
     * Assert campaign run report summary.
     *
     * @return bool
     */
    public function testCampaignRunReportBuilds()
    {
        $report = CampaignRunReport::start('campaign-2026', 'theme-a', array('operator' => 'team-a'));
        $report->addRecord(
            (new JobRecord())
                ->setJobId('job-1')
                ->setStatus('Finished')
                ->setMetadata(array('campaign' => 'campaign-2026', 'episode' => 'ep-a'))
        );
        $report->addRecord(
            (new JobRecord())
                ->setJobId('job-2')
                ->setStatus('Failed')
                ->setMetadata(array('campaign' => 'campaign-2026', 'episode' => 'ep-b'))
        );
        $report->markFinished();

        $summary = $report->getSummary();
        $this->assert($summary['total'] === 2, 'CampaignRunReport total mismatch.');
        $this->assert($summary['finished'] === 1, 'CampaignRunReport finished mismatch.');
        $this->assert($summary['failed'] === 1, 'CampaignRunReport failed mismatch.');

        return true;
    }

    /**
     * Assert campaign service creates report and records.
     *
     * @return bool
     */
    public function testCampaignProducingServiceRunsCampaign()
    {
        $config = (new ImsConfig())
            ->setEndpoint('ice.cn-shanghai.aliyuncs.com')
            ->setRegionId('cn-shanghai')
            ->setBucket('demo-bucket')
            ->setOutputPathPrefix('mixcut')
            ->setProjectId('test-project');

        $theme = (new ThemeConfig('campaign-theme'))->setOutputPattern('oss://demo/{campaign}/{episode}/{n}.mp4');
        $pool = new MaterialPool();
        $pool->add(Material::video('oss://demo/v1.mp4', 10.0)->setSubtitle('视频一'));

        $campaign = (new CampaignPlan('campaign-2026'))
            ->setTheme($theme)
            ->addEpisode(
                (new EpisodePlan('episode-a', new BatchRandomMixcutTemplate()))
                    ->setPool($pool)
                    ->setCount(2)
                    ->setSceneCount(1)
                    ->setMetadata(array('channel' => 'douyin'))
            );

        $service = new CampaignProducingService(new MediaProducingService(new ImsJobClient($config, new StubAdapter())));
        $report = $service->runCampaign($campaign, true, 0, 1);

        $this->assert($report instanceof CampaignRunReport, 'CampaignProducingService should return CampaignRunReport.');
        $this->assert(count($report->getRecords()) === 2, 'Campaign report record count mismatch.');
        $this->assert($report->getRecords()[0]->getStatus() === 'Finished', 'Campaign report should contain finished records.');
        $this->assert($report->getRecords()[0]->getMetadata()['episode'] === 'episode-a', 'Campaign record metadata mismatch.');

        return true;
    }

    /**
     * Assert job record database row mapping.
     *
     * @return bool
     */
    public function testJobRecordRowMapperBuilds()
    {
        $record = (new JobRecord())
            ->setJobId('job-1')
            ->setStatus('Finished')
            ->setMediaUrl('oss://demo/output-1.mp4')
            ->setOutputMediaUrl('oss://demo/expected-1.mp4')
            ->setMetadata(array('campaign' => 'campaign-1', 'episode' => 'episode-a', 'sequence' => 1))
            ->setAttempts(3)
            ->setElapsedSeconds(6.5)
            ->setSubmittedAt('2026-04-11T10:00:00+08:00')
            ->setFinishedAt('2026-04-11T10:00:07+08:00');

        $row = (new JobRecordRowMapper())->map($record);

        $this->assert($row['job_id'] === 'job-1', 'JobRecordRowMapper job_id mismatch.');
        $this->assert($row['campaign'] === 'campaign-1', 'JobRecordRowMapper campaign mismatch.');
        $this->assert($row['elapsed_seconds'] === 6.5, 'JobRecordRowMapper elapsed mismatch.');

        return true;
    }

    /**
     * Assert campaign report database row mapping.
     *
     * @return bool
     */
    public function testCampaignRunReportRowMapperBuilds()
    {
        $report = CampaignRunReport::start('campaign-2026', 'theme-a', array('operator' => 'team-a'));
        $report->addRecord((new JobRecord())->setJobId('job-1')->setStatus('Finished')->setMetadata(array('campaign' => 'campaign-2026')));
        $report->addRecord((new JobRecord())->setJobId('job-2')->setStatus('Failed')->setMetadata(array('campaign' => 'campaign-2026')));
        $report->markFinished();

        $row = (new CampaignRunReportRowMapper())->map($report);

        $this->assert($row['campaign_name'] === 'campaign-2026', 'CampaignRunReportRowMapper campaign mismatch.');
        $this->assert($row['total_jobs'] === 2, 'CampaignRunReportRowMapper total mismatch.');
        $this->assert($row['failed_jobs'] === 1, 'CampaignRunReportRowMapper failed mismatch.');

        return true;
    }

    /**
     * Assert JSON campaign exporter output.
     *
     * @return bool
     */
    public function testJsonCampaignReportExporterBuilds()
    {
        $report = CampaignRunReport::start('campaign-2026', 'theme-a');
        $report->addRecord((new JobRecord())->setJobId('job-1')->setStatus('Finished'));
        $json = (new JsonCampaignRunReportExporter())->export($report);

        $this->assert(strpos($json, '"campaignName": "campaign-2026"') !== false, 'JSON exporter campaign mismatch.');
        $this->assert(strpos($json, '"jobId": "job-1"') !== false, 'JSON exporter record mismatch.');

        return true;
    }

    /**
     * Assert CSV job record exporter output.
     *
     * @return bool
     */
    public function testCsvJobRecordExporterBuilds()
    {
        $records = array(
            (new JobRecord())->setJobId('job-1')->setStatus('Finished')->setMetadata(array('campaign' => 'campaign-1', 'episode' => 'ep-a', 'sequence' => 1)),
            (new JobRecord())->setJobId('job-2')->setStatus('Failed')->setMetadata(array('campaign' => 'campaign-1', 'episode' => 'ep-b', 'sequence' => 2)),
        );

        $csv = (new CsvJobRecordExporter())->export($records);

        $this->assert(strpos($csv, 'job_id,status,campaign,episode,sequence') !== false, 'CSV header mismatch.');
        $this->assert(strpos($csv, 'job-2,Failed,campaign-1,ep-b,2') !== false, 'CSV row mismatch.');

        return true;
    }

    /**
     * Assert file writer writes content to disk.
     *
     * @return bool
     */
    public function testStorageFileWriterWrites()
    {
        $path = __DIR__ . '/tmp/report-output.json';
        $writer = new StorageFileWriter();
        $writer->write($path, '{"ok":true}');
        $this->assert(is_file($path), 'StorageFileWriter should create file.');
        $content = file_get_contents($path);
        $this->assert($content === '{"ok":true}', 'StorageFileWriter content mismatch.');
        @unlink($path);

        return true;
    }

    /**
     * Assert job record file repository saves and restores domain objects.
     *
     * @return bool
     */
    public function testJsonFileJobRecordRepositorySavesAndFinds()
    {
        $baseDir = __DIR__ . '/tmp/job-repo';
        $repo = new JsonFileJobRecordRepository($baseDir);
        $record = (new JobRecord())
            ->setJobId('job-file-1')
            ->setStatus('Finished')
            ->setMediaUrl('oss://demo/out.mp4')
            ->setMetadata(array('campaign' => 'campaign-a', 'episode' => 'ep-1', 'sequence' => 1));

        $repo->save($record);
        $found = $repo->findByJobId('job-file-1');

        $this->assert($found instanceof JobRecord, 'Job file repository should restore JobRecord.');
        $this->assert($found->getJobId() === 'job-file-1', 'Job file repository job id mismatch.');
        $this->assert($found->getMetadata()['campaign'] === 'campaign-a', 'Job file repository metadata mismatch.');
        @unlink($baseDir . '/job-file-1.json');
        @rmdir($baseDir);

        return true;
    }

    /**
     * Assert campaign report file repository saves and restores reports.
     *
     * @return bool
     */
    public function testJsonFileCampaignReportRepositorySavesAndFinds()
    {
        $baseDir = __DIR__ . '/tmp/campaign-repo';
        $repo = new JsonFileCampaignRunReportRepository($baseDir);
        $report = CampaignRunReport::start('campaign-file', 'theme-x', array('owner' => 'team-a'));
        $report->addRecord((new JobRecord())->setJobId('job-a')->setStatus('Finished')->setMetadata(array('campaign' => 'campaign-file')));
        $report->markFinished();

        $path = $repo->save($report);
        $found = $repo->findByPath($path);

        $this->assert($found instanceof CampaignRunReport, 'Campaign file repository should restore CampaignRunReport.');
        $this->assert($found->toArray()['campaignName'] === 'campaign-file', 'Campaign file repository campaign mismatch.');
        @unlink($path);
        @rmdir($baseDir);

        return true;
    }

    /**
     * Assert MySQL schema providers generate create table SQL.
     *
     * @return bool
     */
    public function testMySqlSchemaBuilds()
    {
        $jobSql = (new MySqlJobRecordSchema())->createTableSql('ims_job_records');
        $campaignSql = (new MySqlCampaignRunReportSchema())->createTableSql('ims_campaign_reports');

        $this->assert(strpos($jobSql, 'CREATE TABLE') !== false, 'Job schema should contain CREATE TABLE.');
        $this->assert(strpos($jobSql, 'ims_job_records') !== false, 'Job schema table mismatch.');
        $this->assert(strpos($campaignSql, 'ims_campaign_reports') !== false, 'Campaign schema table mismatch.');

        return true;
    }

    /**
     * Assert batch service accepts BatchTask objects.
     *
     * @return bool
     */
    public function testBatchServiceSubmitsTaskObjects()
    {
        $config = (new ImsConfig())
            ->setEndpoint('ice.cn-shanghai.aliyuncs.com')
            ->setRegionId('cn-shanghai')
            ->setBucket('demo-bucket')
            ->setOutputPathPrefix('mixcut')
            ->setProjectId('test-project');

        $pool = new MaterialPool();
        $pool->add(Material::video('oss://demo/v1.mp4', 10.0)->setSubtitle('视频一'));
        $pool->add(Material::image('oss://demo/i1.jpg')->setSubtitle('图片一'));

        $tasks = (new BatchTaskListBuilder())
            ->template(new BatchRandomMixcutTemplate())
            ->pool($pool)
            ->count(2)
            ->seed(99)
            ->outputPattern('oss://demo-bucket/out/task-{n}.mp4')
            ->build();

        $client = new ImsJobClient($config, new StubAdapter());
        $service = new MediaProducingService($client);
        $batchService = new BatchProducingService($service);
        $results = $batchService->submitBatch($tasks);

        $this->assert(count($results) === 2, 'Batch service should submit BatchTask objects.');
        $this->assert($results[0]->getJobId() !== null, 'BatchTask submit should return JobId.');

        return true;
    }

    /**
     * Assert stub service submit/query flow.
     *
     * @return bool
     */
    public function testServiceSubmitAndQueryStub()
    {
        $config = (new ImsConfig())
            ->setEndpoint('ice.cn-shanghai.aliyuncs.com')
            ->setRegionId('cn-shanghai')
            ->setBucket('demo-bucket')
            ->setOutputPathPrefix('mixcut')
            ->setProjectId('test-project');

        $client = new ImsJobClient($config, new StubAdapter());
        $service = new MediaProducingService($client);

        $template = new PortraitMixcutTemplate();
        $built = $template->build(array(
            'mainVideo' => 'oss://demo/main.mp4',
            'outputMediaConfig' => OutputMediaConfig::oss('oss://demo-bucket/out/test.mp4'),
        ));

        $job = $service->submitTimeline($built['timeline'], $built['outputMediaConfig']);
        $this->assert($job->getJobId() !== null, 'Stub submit should return JobId.');
        $result = $service->waitUntilFinished($job->getJobId(), 0, 1);
        $this->assert($result->getJobResult()->isFinished(), 'Stub job should finish immediately.');

        return true;
    }

    /**
     * Assert application factory assembles default components.
     *
     * @return bool
     */
    public function testImsApplicationFactoryBuilds()
    {
        $baseDir = __DIR__ . '/tmp/app-factory';

        putenv('ALIYUN_IMS_ENDPOINT=ice.cn-shanghai.aliyuncs.com');
        putenv('ALIYUN_IMS_REGION_ID=cn-shanghai');
        putenv('ALIYUN_IMS_BUCKET=demo-bucket');
        putenv('ALIYUN_IMS_OUTPUT_PATH_PREFIX=mixcut');
        putenv('ALIYUN_IMS_PROJECT_ID=test-project');

        $app = ImsApplicationFactory::fromEnv(array(
            'preferred_adapter' => 'stub',
            'storage_dir' => $baseDir,
        ));

        $this->assert($app instanceof ImsApplication, 'Factory should return ImsApplication.');
        $this->assert($app->getMediaProducingService() instanceof MediaProducingService, 'Application should expose MediaProducingService.');
        $this->assert($app->getBatchProducingService() instanceof BatchProducingService, 'Application should expose BatchProducingService.');
        $this->assert($app->getCampaignProducingService() instanceof CampaignProducingService, 'Application should expose CampaignProducingService.');

        $paths = $app->getPaths();
        $this->assert($paths['storageDir'] === $baseDir, 'Application storage dir mismatch.');
        $this->assert(strpos($paths['jobRecordDir'], 'job-records') !== false, 'Application job record dir mismatch.');
        $this->assert(strpos($paths['campaignReportDir'], 'campaign-reports') !== false, 'Application campaign report dir mismatch.');

        putenv('ALIYUN_IMS_ENDPOINT');
        putenv('ALIYUN_IMS_REGION_ID');
        putenv('ALIYUN_IMS_BUCKET');
        putenv('ALIYUN_IMS_OUTPUT_PATH_PREFIX');
        putenv('ALIYUN_IMS_PROJECT_ID');
        $this->deleteDirectory($baseDir);

        return true;
    }

    /**
     * Assert application facade can run and persist a campaign.
     *
     * @return bool
     */
    public function testImsApplicationRunsAndStoresCampaign()
    {
        $config = (new ImsConfig())
            ->setEndpoint('ice.cn-shanghai.aliyuncs.com')
            ->setRegionId('cn-shanghai')
            ->setBucket('demo-bucket')
            ->setOutputPathPrefix('mixcut')
            ->setProjectId('test-project');

        $baseDir = __DIR__ . '/tmp/app-runtime';
        $theme = (new ThemeConfig('campaign-theme'))->setOutputPattern('oss://demo/{campaign}/{episode}/{n}.mp4');
        $pool = new MaterialPool();
        $pool->add(Material::video('oss://demo/v1.mp4', 10.0)->setSubtitle('视频一'));

        $campaign = (new CampaignPlan('campaign-app'))
            ->setTheme($theme)
            ->addEpisode(
                (new EpisodePlan('episode-app', new BatchRandomMixcutTemplate()))
                    ->setPool($pool)
                    ->setCount(1)
                    ->setSceneCount(1)
            );

        $app = ImsApplicationFactory::create($config, array(
            'adapter' => new StubAdapter(),
            'storage_dir' => $baseDir,
        ));

        $result = $app->runCampaignAndStore($campaign, true, 0, 1);

        $this->assert($result['report'] instanceof CampaignRunReport, 'Application should return CampaignRunReport.');
        $this->assert(is_file($result['reportPath']), 'Application should persist campaign report.');
        $this->assert(count($result['recordPaths']) === 1, 'Application should persist job records.');
        $this->assert(is_file($result['recordPaths'][0]), 'Application should persist job record file.');

        $reportJson = $app->exportCampaignReportJson($result['report']);
        $recordsCsv = $app->exportJobRecordsCsv($result['report']->getRecords());
        $this->assert(strpos($reportJson, '"campaignName": "campaign-app"') !== false, 'Application JSON export mismatch.');
        $this->assert(strpos($recordsCsv, 'job_id,status,campaign,episode,sequence') !== false, 'Application CSV export mismatch.');

        $found = $app->getCampaignRunReportRepository()->findByPath($result['reportPath']);
        $this->assert($found instanceof CampaignRunReport, 'Application should restore stored campaign report.');
        $this->assert($found->toArray()['campaignName'] === 'campaign-app', 'Stored campaign report mismatch.');

        $this->deleteDirectory($baseDir);

        return true;
    }

    /**
     * Minimal assertion helper.
     *
     * @param bool   $condition
     * @param string $message
     *
     * @return void
     */
    protected function assert($condition, $message)
    {
        if (!$condition) {
            throw new \RuntimeException($message);
        }
    }

    /**
     * Remove a directory recursively when it exists.
     *
     * @param string $path
     *
     * @return void
     */
    protected function deleteDirectory($path)
    {
        if (!is_dir($path)) {
            return;
        }

        $items = scandir($path);
        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $current = $path . DIRECTORY_SEPARATOR . $item;
            if (is_dir($current)) {
                $this->deleteDirectory($current);
                continue;
            }

            @unlink($current);
        }

        @rmdir($path);
    }
}
