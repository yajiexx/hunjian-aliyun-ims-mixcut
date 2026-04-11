<?php

namespace Hunjian\AliyunImsMixcut\Repository;

use Hunjian\AliyunImsMixcut\Result\JobRecord;

/**
 * Interface JobRecordRepositoryInterface
 *
 * Persistence boundary for job records.
 */
interface JobRecordRepositoryInterface
{
    /**
     * Save one record.
     *
     * @param JobRecord $record
     *
     * @return mixed
     */
    public function save(JobRecord $record);

    /**
     * Save many records.
     *
     * @param array $records
     *
     * @return mixed
     */
    public function saveMany(array $records);
}
