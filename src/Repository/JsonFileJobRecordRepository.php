<?php

namespace Hunjian\AliyunImsMixcut\Repository;

use Hunjian\AliyunImsMixcut\Result\JobRecord;
use Hunjian\AliyunImsMixcut\Storage\StorageFileWriter;
use Hunjian\AliyunImsMixcut\Support\Json;

/**
 * Class JsonFileJobRecordRepository
 *
 * Stores each JobRecord as one JSON file.
 */
class JsonFileJobRecordRepository implements JobRecordRepositoryInterface
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
     * Save one record.
     *
     * @param JobRecord $record
     *
     * @return string
     */
    public function save(JobRecord $record)
    {
        $jobId = $record->getJobId() ?: ('job-' . uniqid());
        $path = $this->baseDir . DIRECTORY_SEPARATOR . $jobId . '.json';
        $this->writer->write($path, Json::encode($record->toArray(), true));

        return $path;
    }

    /**
     * Save many records.
     *
     * @param array $records
     *
     * @return array
     */
    public function saveMany(array $records)
    {
        $paths = array();

        foreach ($records as $record) {
            $paths[] = $this->save($record);
        }

        return $paths;
    }

    /**
     * Find by job id.
     *
     * @param string $jobId
     *
     * @return JobRecord|null
     */
    public function findByJobId($jobId)
    {
        $path = $this->baseDir . DIRECTORY_SEPARATOR . $jobId . '.json';
        if (!is_file($path)) {
            return null;
        }

        return JobRecord::fromArray(Json::decode(file_get_contents($path)));
    }
}
