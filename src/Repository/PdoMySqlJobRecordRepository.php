<?php

namespace Hunjian\AliyunImsMixcut\Repository;

use Hunjian\AliyunImsMixcut\Result\JobRecord;
use Hunjian\AliyunImsMixcut\Storage\JobRecordRowMapper;
use PDO;

/**
 * Class PdoMySqlJobRecordRepository
 *
 * MySQL/PDO repository example for job records.
 */
class PdoMySqlJobRecordRepository implements JobRecordRepositoryInterface
{
    /**
     * @var PDO
     */
    protected $pdo;

    /**
     * @var string
     */
    protected $table;

    /**
     * @var JobRecordRowMapper
     */
    protected $mapper;

    /**
     * Create repository.
     *
     * @param PDO                      $pdo
     * @param string                   $table
     * @param JobRecordRowMapper|null  $mapper
     */
    public function __construct(PDO $pdo, $table = 'ims_job_records', JobRecordRowMapper $mapper = null)
    {
        $this->pdo = $pdo;
        $this->table = $table;
        $this->mapper = $mapper ?: new JobRecordRowMapper();
    }

    /**
     * Save one record.
     *
     * @param JobRecord $record
     *
     * @return void
     */
    public function save(JobRecord $record)
    {
        $row = $this->mapper->map($record);
        $sql = "INSERT INTO `{$this->table}` (`job_id`,`status`,`media_url`,`output_media_url`,`campaign`,`episode`,`theme`,`sequence`,`attempts`,`elapsed_seconds`,`submitted_at`,`finished_at`,`metadata_json`,`request_payload_json`,`raw_json`)
VALUES (:job_id,:status,:media_url,:output_media_url,:campaign,:episode,:theme,:sequence,:attempts,:elapsed_seconds,:submitted_at,:finished_at,:metadata_json,:request_payload_json,:raw_json)
ON DUPLICATE KEY UPDATE
`status`=VALUES(`status`),
`media_url`=VALUES(`media_url`),
`output_media_url`=VALUES(`output_media_url`),
`campaign`=VALUES(`campaign`),
`episode`=VALUES(`episode`),
`theme`=VALUES(`theme`),
`sequence`=VALUES(`sequence`),
`attempts`=VALUES(`attempts`),
`elapsed_seconds`=VALUES(`elapsed_seconds`),
`submitted_at`=VALUES(`submitted_at`),
`finished_at`=VALUES(`finished_at`),
`metadata_json`=VALUES(`metadata_json`),
`request_payload_json`=VALUES(`request_payload_json`),
`raw_json`=VALUES(`raw_json`)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($row);
    }

    /**
     * Save many records.
     *
     * @param array $records
     *
     * @return void
     */
    public function saveMany(array $records)
    {
        $this->pdo->beginTransaction();
        try {
            foreach ($records as $record) {
                $this->save($record);
            }
            $this->pdo->commit();
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }
}
