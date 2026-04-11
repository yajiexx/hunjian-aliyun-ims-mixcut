<?php

namespace Hunjian\AliyunImsMixcut\Result;

use Hunjian\AliyunImsMixcut\Model\BaseStructure;
use Hunjian\AliyunImsMixcut\Model\BatchTask;

/**
 * Class JobRecord
 *
 * Persistable record for one submitted IMS job.
 */
class JobRecord extends BaseStructure
{
    /**
     * @var string|null
     */
    protected $jobId;

    /**
     * @var string|null
     */
    protected $status;

    /**
     * @var string|null
     */
    protected $mediaUrl;

    /**
     * @var string|null
     */
    protected $outputMediaUrl;

    /**
     * @var array
     */
    protected $metadata = array();

    /**
     * @var array
     */
    protected $requestPayload = array();

    /**
     * @var array
     */
    protected $raw = array();

    /**
     * @var int
     */
    protected $attempts = 0;

    /**
     * @var float
     */
    protected $elapsedSeconds = 0.0;

    /**
     * @var string|null
     */
    protected $submittedAt;

    /**
     * @var string|null
     */
    protected $finishedAt;

    /**
     * Build record from a task and submit result.
     *
     * @param BatchTask $task
     * @param JobResult $jobResult
     *
     * @return self
     */
    public static function fromTaskAndJobResult(BatchTask $task, JobResult $jobResult)
    {
        $record = new self();
        $built = $task->build();
        $output = $built['outputMediaConfig']->toArray();

        $record
            ->setJobId($jobResult->getJobId())
            ->setStatus($jobResult->getStatus())
            ->setMediaUrl($jobResult->getMediaUrl())
            ->setOutputMediaUrl(isset($output['MediaURL']) ? $output['MediaURL'] : null)
            ->setMetadata($task->getMetadata())
            ->setRequestPayload($jobResult->getRequestPayload())
            ->setRaw($jobResult->getRaw())
            ->setSubmittedAt(date('c'));

        if ($record->isFinished() || $record->isFailed()) {
            $record->setFinishedAt(date('c'));
        }

        return $record;
    }

    /**
     * Restore record from persisted array.
     *
     * @param array $data
     *
     * @return self
     */
    public static function fromArray(array $data)
    {
        return (new self())
            ->setJobId(isset($data['jobId']) ? $data['jobId'] : null)
            ->setStatus(isset($data['status']) ? $data['status'] : null)
            ->setMediaUrl(isset($data['mediaUrl']) ? $data['mediaUrl'] : null)
            ->setOutputMediaUrl(isset($data['outputMediaUrl']) ? $data['outputMediaUrl'] : null)
            ->setMetadata(isset($data['metadata']) ? $data['metadata'] : array())
            ->setRequestPayload(isset($data['requestPayload']) ? $data['requestPayload'] : array())
            ->setRaw(isset($data['raw']) ? $data['raw'] : array())
            ->setAttempts(isset($data['attempts']) ? $data['attempts'] : 0)
            ->setElapsedSeconds(isset($data['elapsedSeconds']) ? $data['elapsedSeconds'] : 0)
            ->setSubmittedAt(isset($data['submittedAt']) ? $data['submittedAt'] : null)
            ->setFinishedAt(isset($data['finishedAt']) ? $data['finishedAt'] : null);
    }

    /**
     * Apply waited result back to the record.
     *
     * @param ProduceResult $produceResult
     *
     * @return $this
     */
    public function applyProduceResult(ProduceResult $produceResult)
    {
        $jobResult = $produceResult->getJobResult();

        $this->status = $jobResult->getStatus();
        $this->mediaUrl = $jobResult->getMediaUrl();
        $this->raw = $jobResult->getRaw();
        $this->attempts = $produceResult->getAttempts();
        $this->elapsedSeconds = $produceResult->getElapsedSeconds();

        if ($this->isFinished() || $this->isFailed()) {
            $this->finishedAt = date('c');
        }

        return $this;
    }

    /**
     * Set job id.
     *
     * @param string|null $jobId
     *
     * @return $this
     */
    public function setJobId($jobId)
    {
        $this->jobId = $jobId;

        return $this;
    }

    /**
     * Set status.
     *
     * @param string|null $status
     *
     * @return $this
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Set final media url.
     *
     * @param string|null $mediaUrl
     *
     * @return $this
     */
    public function setMediaUrl($mediaUrl)
    {
        $this->mediaUrl = $mediaUrl;

        return $this;
    }

    /**
     * Set expected output media url.
     *
     * @param string|null $outputMediaUrl
     *
     * @return $this
     */
    public function setOutputMediaUrl($outputMediaUrl)
    {
        $this->outputMediaUrl = $outputMediaUrl;

        return $this;
    }

    /**
     * Set metadata.
     *
     * @param array $metadata
     *
     * @return $this
     */
    public function setMetadata(array $metadata)
    {
        $this->metadata = $metadata;

        return $this;
    }

    /**
     * Set request payload.
     *
     * @param array $requestPayload
     *
     * @return $this
     */
    public function setRequestPayload(array $requestPayload)
    {
        $this->requestPayload = $requestPayload;

        return $this;
    }

    /**
     * Set raw response.
     *
     * @param array $raw
     *
     * @return $this
     */
    public function setRaw(array $raw)
    {
        $this->raw = $raw;

        return $this;
    }

    /**
     * Set attempts.
     *
     * @param int $attempts
     *
     * @return $this
     */
    public function setAttempts($attempts)
    {
        $this->attempts = (int) $attempts;

        return $this;
    }

    /**
     * Set elapsed seconds.
     *
     * @param float $elapsedSeconds
     *
     * @return $this
     */
    public function setElapsedSeconds($elapsedSeconds)
    {
        $this->elapsedSeconds = (float) $elapsedSeconds;

        return $this;
    }

    /**
     * Set submission time.
     *
     * @param string|null $submittedAt
     *
     * @return $this
     */
    public function setSubmittedAt($submittedAt)
    {
        $this->submittedAt = $submittedAt;

        return $this;
    }

    /**
     * Set finish time.
     *
     * @param string|null $finishedAt
     *
     * @return $this
     */
    public function setFinishedAt($finishedAt)
    {
        $this->finishedAt = $finishedAt;

        return $this;
    }

    /**
     * Get job id.
     *
     * @return string|null
     */
    public function getJobId()
    {
        return $this->jobId;
    }

    /**
     * Get status.
     *
     * @return string|null
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Get metadata.
     *
     * @return array
     */
    public function getMetadata()
    {
        return $this->metadata;
    }

    /**
     * Get media url.
     *
     * @return string|null
     */
    public function getMediaUrl()
    {
        return $this->mediaUrl;
    }

    /**
     * Get expected output media url.
     *
     * @return string|null
     */
    public function getOutputMediaUrl()
    {
        return $this->outputMediaUrl;
    }

    /**
     * Determine whether the record is finished.
     *
     * @return bool
     */
    public function isFinished()
    {
        return in_array($this->status, array('Finished', 'Success', 'Succeeded'), true);
    }

    /**
     * Determine whether the record failed.
     *
     * @return bool
     */
    public function isFailed()
    {
        return in_array($this->status, array('Failed', 'Fail', 'Error', 'Canceled'), true);
    }

    /**
     * Convert to array.
     *
     * @return array
     */
    public function toArray()
    {
        return $this->finalize(array(
            'jobId' => $this->jobId,
            'status' => $this->status,
            'mediaUrl' => $this->mediaUrl,
            'outputMediaUrl' => $this->outputMediaUrl,
            'metadata' => $this->metadata,
            'requestPayload' => $this->requestPayload,
            'raw' => $this->raw,
            'attempts' => $this->attempts,
            'elapsedSeconds' => $this->elapsedSeconds,
            'submittedAt' => $this->submittedAt,
            'finishedAt' => $this->finishedAt,
        ));
    }
}
