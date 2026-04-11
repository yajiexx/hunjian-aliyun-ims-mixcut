<?php

namespace Hunjian\AliyunImsMixcut\Client\Adapter;

/**
 * IMS 适配器接口
 *
 * SDK 适配器边界。保持业务层与实际官方类隔离。
 */
interface ImsAdapterInterface
{
    /**
     * 提交制作作业。
     *
     * @param array $payload
     *
     * @return array
     */
    public function submitMediaProducingJob(array $payload);

    /**
     * 查询制作作业。
     *
     * @param string $jobId
     *
     * @return array
     */
    public function getMediaProducingJob($jobId);
}
