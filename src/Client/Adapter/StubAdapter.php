<?php

namespace Hunjian\AliyunImsMixcut\Client\Adapter;

/**
 * 桩适配器
 *
 * 用于本地开发、示例和测试的内存适配器。
 */
class StubAdapter implements ImsAdapterInterface
{
    /**
     * @var array
     */
    protected $jobs = array();

    /**
     * 提交制作作业。
     *
     * @param array $payload
     *
     * @return array
     */
    public function submitMediaProducingJob(array $payload)
    {
        $jobId = 'stub-' . uniqid();
        $outputConfig = isset($payload['OutputMediaConfig']) ? $payload['OutputMediaConfig'] : array();
        $mediaUrl = isset($outputConfig['MediaURL']) ? $outputConfig['MediaURL'] : null;

        $this->jobs[$jobId] = array(
            'JobId' => $jobId,
            'Status' => 'Finished',
            'Request' => $payload,
            'MediaURL' => $mediaUrl,
            'SubmitTime' => date('c'),
            'FinishTime' => date('c'),
        );

        return array(
            'RequestId' => 'stub-request-' . uniqid(),
            'MediaProducingJob' => $this->jobs[$jobId],
        );
    }

    /**
     * 查询制作作业。
     *
     * @param string $jobId
     *
     * @return array
     */
    public function getMediaProducingJob($jobId)
    {
        $job = isset($this->jobs[$jobId]) ? $this->jobs[$jobId] : array(
            'JobId' => $jobId,
            'Status' => 'NotFound',
        );

        return array(
            'RequestId' => 'stub-query-' . uniqid(),
            'MediaProducingJob' => $job,
        );
    }
}
