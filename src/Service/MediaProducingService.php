<?php

namespace Hunjian\AliyunImsMixcut\Service;

use Hunjian\AliyunImsMixcut\Client\ImsJobClient;
use Hunjian\AliyunImsMixcut\Contracts\TemplateInterface;
use Hunjian\AliyunImsMixcut\Exception\JobFailedException;
use Hunjian\AliyunImsMixcut\Model\OutputMediaConfig;
use Hunjian\AliyunImsMixcut\Model\Timeline;
use Hunjian\AliyunImsMixcut\Result\JobResult;
use Hunjian\AliyunImsMixcut\Result\ProduceResult;

/**
 * 媒体制作服务类
 *
 * 时间线提交和轮询的高级门面。
 */
class MediaProducingService
{
    /**
     * @var ImsJobClient
     */
    protected $client;

    /**
     * 创建服务。
     *
     * @param ImsJobClient $client
     */
    public function __construct(ImsJobClient $client)
    {
        $this->client = $client;
    }

    /**
     * Submit a timeline object.
     *
     * @param Timeline          $timeline
     * @param OutputMediaConfig $outputMediaConfig
     * @param array             $options
     *
     * @return JobResult
     */
    public function submitTimeline(Timeline $timeline, OutputMediaConfig $outputMediaConfig, array $options = array())
    {
        return $this->client->submitTimeline($timeline, $outputMediaConfig, $options);
    }

    /**
     * 提交本地包模板。
     *
     * @param TemplateInterface $template
     * @param array             $context
     * @param array             $options
     *
     * @return JobResult
     */
    public function submitLocalTemplate(TemplateInterface $template, array $context = array(), array $options = array())
    {
        $built = $template->build($context);

        return $this->submitTimeline($built['timeline'], $built['outputMediaConfig'], $options);
    }

    /**
     * Submit an official IMS TemplateId job.
     *
     * @param string            $templateId
     * @param array             $clipsParam
     * @param OutputMediaConfig $outputMediaConfig
     * @param array             $options
     *
     * @return JobResult
     */
    public function submitTemplate($templateId, array $clipsParam, OutputMediaConfig $outputMediaConfig, array $options = array())
    {
        return $this->client->submitTemplate($templateId, $clipsParam, $outputMediaConfig, $options);
    }

    /**
     * 查询作业。
     *
     * @param string $jobId
     *
     * @return JobResult
     */
    public function getJob($jobId)
    {
        return $this->client->getJob($jobId);
    }

    /**
     * 轮询直到 IMS 返回终止状态。
     *
     * @param string $jobId
     * @param int    $intervalSeconds
     * @param int    $timeoutSeconds
     *
     * @return ProduceResult
     */
    public function waitUntilFinished($jobId, $intervalSeconds = 5, $timeoutSeconds = 600)
    {
        $start = microtime(true);
        $attempts = 0;

        do {
            $attempts++;
            $jobResult = $this->getJob($jobId);

            if ($jobResult->isFinished()) {
                return new ProduceResult($jobResult, $attempts, microtime(true) - $start);
            }

            if ($jobResult->isFailed()) {
                throw new JobFailedException('IMS job failed. JobId=' . $jobId . ' Status=' . $jobResult->getStatus());
            }

            sleep((int) $intervalSeconds);
        } while ((microtime(true) - $start) < $timeoutSeconds);

        return new ProduceResult($jobResult, $attempts, microtime(true) - $start);
    }
}
