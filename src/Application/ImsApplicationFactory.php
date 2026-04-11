<?php

namespace Hunjian\AliyunImsMixcut\Application;

use Hunjian\AliyunImsMixcut\Client\Adapter\ImsAdapterInterface;
use Hunjian\AliyunImsMixcut\Client\ImsClientFactory;
use Hunjian\AliyunImsMixcut\Client\ImsJobClient;
use Hunjian\AliyunImsMixcut\Config\ImsConfig;
use Hunjian\AliyunImsMixcut\Repository\CampaignRunReportRepositoryInterface;
use Hunjian\AliyunImsMixcut\Repository\JsonFileCampaignRunReportRepository;
use Hunjian\AliyunImsMixcut\Repository\JsonFileJobRecordRepository;
use Hunjian\AliyunImsMixcut\Repository\JobRecordRepositoryInterface;
use Hunjian\AliyunImsMixcut\Service\BatchProducingService;
use Hunjian\AliyunImsMixcut\Service\CampaignProducingService;
use Hunjian\AliyunImsMixcut\Service\MediaProducingService;
use Hunjian\AliyunImsMixcut\Storage\CsvJobRecordExporter;
use Hunjian\AliyunImsMixcut\Storage\JsonCampaignRunReportExporter;
use Hunjian\AliyunImsMixcut\Storage\StorageFileWriter;

/**
 * Class ImsApplicationFactory
 *
 * Factory for assembling a ready-to-use application facade.
 */
class ImsApplicationFactory
{
    /**
     * Build application from environment variables.
     *
     * Supported options:
     * - env_map
     * - preferred_adapter
     * - adapter
     * - storage_dir
     * - job_record_dir
     * - campaign_report_dir
     * - writer
     * - job_record_repository
     * - campaign_report_repository
     * - job_record_exporter
     * - campaign_report_exporter
     *
     * @param array $options
     *
     * @return ImsApplication
     */
    public static function fromEnv(array $options = array())
    {
        $envMap = isset($options['env_map']) && is_array($options['env_map']) ? $options['env_map'] : array();

        return self::create(ImsConfig::fromEnv($envMap), $options);
    }

    /**
     * Build application from explicit config.
     *
     * @param ImsConfig $config
     * @param array     $options
     *
     * @return ImsApplication
     */
    public static function create(ImsConfig $config, array $options = array())
    {
        $writer = isset($options['writer']) && $options['writer'] instanceof StorageFileWriter
            ? $options['writer']
            : new StorageFileWriter();

        $paths = self::resolvePaths($options);
        $adapter = self::resolveAdapter($config, $options);
        $client = new ImsJobClient($config, $adapter);
        $mediaProducingService = new MediaProducingService($client);
        $batchProducingService = new BatchProducingService($mediaProducingService);
        $campaignProducingService = new CampaignProducingService($mediaProducingService);

        $jobRecordRepository = self::resolveJobRecordRepository($paths['jobRecordDir'], $writer, $options);
        $campaignRunReportRepository = self::resolveCampaignRunReportRepository($paths['campaignReportDir'], $writer, $options);
        $jobRecordExporter = isset($options['job_record_exporter']) && $options['job_record_exporter'] instanceof CsvJobRecordExporter
            ? $options['job_record_exporter']
            : new CsvJobRecordExporter();
        $campaignRunReportExporter = isset($options['campaign_report_exporter']) && $options['campaign_report_exporter'] instanceof JsonCampaignRunReportExporter
            ? $options['campaign_report_exporter']
            : new JsonCampaignRunReportExporter();

        return new ImsApplication(
            $config,
            $client,
            $mediaProducingService,
            $batchProducingService,
            $campaignProducingService,
            $jobRecordRepository,
            $campaignRunReportRepository,
            $jobRecordExporter,
            $campaignRunReportExporter,
            $writer,
            $paths
        );
    }

    /**
     * Resolve default storage paths.
     *
     * @param array $options
     *
     * @return array
     */
    protected static function resolvePaths(array $options)
    {
        $storageDir = self::trimPath(self::option($options, 'storage_dir', 'ALIYUN_IMS_STORAGE_DIR', 'runtime/ims'));
        $jobRecordDir = self::trimPath(self::option($options, 'job_record_dir', 'ALIYUN_IMS_JOB_RECORD_DIR', $storageDir . DIRECTORY_SEPARATOR . 'job-records'));
        $campaignReportDir = self::trimPath(self::option($options, 'campaign_report_dir', 'ALIYUN_IMS_CAMPAIGN_REPORT_DIR', $storageDir . DIRECTORY_SEPARATOR . 'campaign-reports'));

        return array(
            'storageDir' => $storageDir,
            'jobRecordDir' => $jobRecordDir,
            'campaignReportDir' => $campaignReportDir,
        );
    }

    /**
     * Resolve adapter from options or config.
     *
     * @param ImsConfig $config
     * @param array     $options
     *
     * @return ImsAdapterInterface
     */
    protected static function resolveAdapter(ImsConfig $config, array $options)
    {
        if (isset($options['adapter']) && $options['adapter'] instanceof ImsAdapterInterface) {
            return $options['adapter'];
        }

        $preferred = isset($options['preferred_adapter']) ? $options['preferred_adapter'] : null;

        return ImsClientFactory::createAdapter($config, $preferred);
    }

    /**
     * Resolve job record repository.
     *
     * @param string            $baseDir
     * @param StorageFileWriter $writer
     * @param array             $options
     *
     * @return JobRecordRepositoryInterface
     */
    protected static function resolveJobRecordRepository($baseDir, StorageFileWriter $writer, array $options)
    {
        if (isset($options['job_record_repository']) && $options['job_record_repository'] instanceof JobRecordRepositoryInterface) {
            return $options['job_record_repository'];
        }

        return new JsonFileJobRecordRepository($baseDir, $writer);
    }

    /**
     * Resolve campaign report repository.
     *
     * @param string            $baseDir
     * @param StorageFileWriter $writer
     * @param array             $options
     *
     * @return CampaignRunReportRepositoryInterface
     */
    protected static function resolveCampaignRunReportRepository($baseDir, StorageFileWriter $writer, array $options)
    {
        if (isset($options['campaign_report_repository']) && $options['campaign_report_repository'] instanceof CampaignRunReportRepositoryInterface) {
            return $options['campaign_report_repository'];
        }

        return new JsonFileCampaignRunReportRepository($baseDir, $writer);
    }

    /**
     * Pick one option with env fallback.
     *
     * @param array  $options
     * @param string $optionKey
     * @param string $envKey
     * @param mixed  $default
     *
     * @return mixed
     */
    protected static function option(array $options, $optionKey, $envKey, $default)
    {
        if (isset($options[$optionKey]) && $options[$optionKey] !== '') {
            return $options[$optionKey];
        }

        $envValue = getenv($envKey);
        if ($envValue !== false && $envValue !== '') {
            return $envValue;
        }

        return $default;
    }

    /**
     * Trim ending directory separators.
     *
     * @param string $path
     *
     * @return string
     */
    protected static function trimPath($path)
    {
        return rtrim((string) $path, '/\\');
    }
}
