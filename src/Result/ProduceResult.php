<?php

namespace Hunjian\AliyunImsMixcut\Result;

/**
 * Class ProduceResult
 *
 * Final production result after waiting or batch orchestration.
 */
class ProduceResult
{
    /**
     * @var JobResult
     */
    protected $jobResult;

    /**
     * @var int
     */
    protected $attempts = 0;

    /**
     * @var float
     */
    protected $elapsedSeconds = 0.0;

    /**
     * Create result object.
     *
     * @param JobResult $jobResult
     * @param int       $attempts
     * @param float     $elapsedSeconds
     */
    public function __construct(JobResult $jobResult, $attempts, $elapsedSeconds)
    {
        $this->jobResult = $jobResult;
        $this->attempts = (int) $attempts;
        $this->elapsedSeconds = (float) $elapsedSeconds;
    }

    /**
     * Get job result.
     *
     * @return JobResult
     */
    public function getJobResult()
    {
        return $this->jobResult;
    }

    /**
     * Get polling attempts.
     *
     * @return int
     */
    public function getAttempts()
    {
        return $this->attempts;
    }

    /**
     * Get elapsed seconds.
     *
     * @return float
     */
    public function getElapsedSeconds()
    {
        return $this->elapsedSeconds;
    }

    /**
     * Convert object to array.
     *
     * @return array
     */
    public function toArray()
    {
        return array(
            'attempts' => $this->attempts,
            'elapsedSeconds' => $this->elapsedSeconds,
            'job' => $this->jobResult->toArray(),
        );
    }
}
