<?php

namespace Hunjian\AliyunImsMixcut\Repository;

use Hunjian\AliyunImsMixcut\Result\CampaignRunReport;
use Hunjian\AliyunImsMixcut\Storage\StorageFileWriter;
use Hunjian\AliyunImsMixcut\Support\Json;

/**
 * Class JsonFileCampaignRunReportRepository
 *
 * Stores each CampaignRunReport as one JSON file.
 */
class JsonFileCampaignRunReportRepository implements CampaignRunReportRepositoryInterface
{
    /**
     * @var string
     */
    protected $baseDir;

    /**
     * @var StorageFileWriter
     */
    protected $writer;

    /**
     * Create repository.
     *
     * @param string                 $baseDir
     * @param StorageFileWriter|null $writer
     */
    public function __construct($baseDir, StorageFileWriter $writer = null)
    {
        $this->baseDir = rtrim($baseDir, '/\\');
        $this->writer = $writer ?: new StorageFileWriter();
    }

    /**
     * Save one report.
     *
     * @param CampaignRunReport $report
     *
     * @return string
     */
    public function save(CampaignRunReport $report)
    {
        $file = $report->getCampaignName() . '-' . date('YmdHis') . '.json';
        $path = $this->baseDir . DIRECTORY_SEPARATOR . $file;
        $this->writer->write($path, Json::encode($report->toArray(), true));

        return $path;
    }

    /**
     * Save many reports.
     *
     * @param array $reports
     *
     * @return array
     */
    public function saveMany(array $reports)
    {
        $paths = array();

        foreach ($reports as $report) {
            $paths[] = $this->save($report);
        }

        return $paths;
    }

    /**
     * Read a report from path.
     *
     * @param string $path
     *
     * @return CampaignRunReport|null
     */
    public function findByPath($path)
    {
        if (!is_file($path)) {
            return null;
        }

        return CampaignRunReport::fromArray(Json::decode(file_get_contents($path)));
    }
}
