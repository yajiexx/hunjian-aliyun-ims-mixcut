<?php

namespace Hunjian\AliyunImsMixcut\Storage;

use Hunjian\AliyunImsMixcut\Result\JobRecord;
use Hunjian\AliyunImsMixcut\Support\Json;

/**
 * Class JobRecordRowMapper
 *
 * Maps JobRecord into one flat database-friendly row.
 */
class JobRecordRowMapper
{
    /**
     * Map one job record.
     *
     * @param JobRecord $record
     *
     * @return array
     */
    public function map(JobRecord $record)
    {
        $data = $record->toArray();
        $metadata = isset($data['metadata']) ? $data['metadata'] : array();

        return array(
            'job_id' => isset($data['jobId']) ? $data['jobId'] : null,
            'status' => isset($data['status']) ? $data['status'] : null,
            'media_url' => isset($data['mediaUrl']) ? $data['mediaUrl'] : null,
            'output_media_url' => isset($data['outputMediaUrl']) ? $data['outputMediaUrl'] : null,
            'campaign' => isset($metadata['campaign']) ? $metadata['campaign'] : null,
            'episode' => isset($metadata['episode']) ? $metadata['episode'] : null,
            'theme' => isset($metadata['theme']) ? $metadata['theme'] : null,
            'sequence' => isset($metadata['sequence']) ? $metadata['sequence'] : null,
            'attempts' => isset($data['attempts']) ? $data['attempts'] : 0,
            'elapsed_seconds' => isset($data['elapsedSeconds']) ? $data['elapsedSeconds'] : 0,
            'submitted_at' => isset($data['submittedAt']) ? $data['submittedAt'] : null,
            'finished_at' => isset($data['finishedAt']) ? $data['finishedAt'] : null,
            'metadata_json' => Json::encode($metadata),
            'request_payload_json' => Json::encode(isset($data['requestPayload']) ? $data['requestPayload'] : array()),
            'raw_json' => Json::encode(isset($data['raw']) ? $data['raw'] : array()),
        );
    }
}
