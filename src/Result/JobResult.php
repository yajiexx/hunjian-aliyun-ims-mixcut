<?php

namespace Hunjian\AliyunImsMixcut\Result;

/**
 * Class JobResult
 *
 * Unified submission/query response object.
 */
class JobResult
{
    /**
     * @var array
     */
    protected $raw = array();

    /**
     * @var array
     */
    protected $requestPayload = array();

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
     * Create result from submit response.
     *
     * @param array $response
     *
     * @return self
     */
    public static function fromSubmitResponse(array $response)
    {
        return self::fromResponse($response);
    }

    /**
     * Create result from query response.
     *
     * @param array $response
     *
     * @return self
     */
    public static function fromQueryResponse(array $response)
    {
        return self::fromResponse($response);
    }

    /**
     * Internal response parser.
     *
     * @param array $response
     *
     * @return self
     */
    protected static function fromResponse(array $response)
    {
        $job = isset($response['MediaProducingJob']) ? $response['MediaProducingJob'] : $response;
        $result = new self();
        $result->raw = $response;
        $result->jobId = self::pick($job, array('JobId', 'MediaProducingJobId', 'Id'));
        $result->status = self::pick($job, array('Status', 'State', 'JobStatus'));
        $result->mediaUrl = self::pick($job, array('MediaURL', 'OutputMediaURL', 'OutputUrl'));

        return $result;
    }

    /**
     * Pick first existing key.
     *
     * @param array $data
     * @param array $keys
     *
     * @return mixed|null
     */
    protected static function pick(array $data, array $keys)
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $data)) {
                return $data[$key];
            }
        }

        return null;
    }

    /**
     * Set normalized request payload.
     *
     * @param array $payload
     *
     * @return $this
     */
    public function setRequestPayload(array $payload)
    {
        $this->requestPayload = $payload;

        return $this;
    }

    /**
     * Get raw response.
     *
     * @return array
     */
    public function getRaw()
    {
        return $this->raw;
    }

    /**
     * Get request payload.
     *
     * @return array
     */
    public function getRequestPayload()
    {
        return $this->requestPayload;
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
     * Get media URL.
     *
     * @return string|null
     */
    public function getMediaUrl()
    {
        return $this->mediaUrl;
    }

    /**
     * Determine whether job is finished successfully.
     *
     * @return bool
     */
    public function isFinished()
    {
        return in_array($this->status, array('Finished', 'Success', 'Succeeded'), true);
    }

    /**
     * Determine whether job failed.
     *
     * @return bool
     */
    public function isFailed()
    {
        return in_array($this->status, array('Failed', 'Fail', 'Error', 'Canceled'), true);
    }

    /**
     * Convert object to array.
     *
     * @return array
     */
    public function toArray()
    {
        return array(
            'jobId' => $this->jobId,
            'status' => $this->status,
            'mediaUrl' => $this->mediaUrl,
            'requestPayload' => $this->requestPayload,
            'raw' => $this->raw,
        );
    }
}
