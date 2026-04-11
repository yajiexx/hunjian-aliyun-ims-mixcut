<?php

namespace Hunjian\AliyunImsMixcut\Client;

use Hunjian\AliyunImsMixcut\Client\Adapter\ImsAdapterInterface;
use Hunjian\AliyunImsMixcut\Config\ImsConfig;
use Hunjian\AliyunImsMixcut\Exception\InvalidTimelineException;
use Hunjian\AliyunImsMixcut\Exception\JobSubmitException;
use Hunjian\AliyunImsMixcut\Model\OutputMediaConfig;
use Hunjian\AliyunImsMixcut\Model\Timeline;
use Hunjian\AliyunImsMixcut\Result\JobResult;

/**
 * Class ImsJobClient
 *
 * Job-oriented client with request payload normalization.
 */
class ImsJobClient
{
    /**
     * @var ImsConfig
     */
    protected $config;

    /**
     * @var ImsAdapterInterface
     */
    protected $adapter;

    /**
     * Create job client.
     *
     * @param ImsConfig           $config
     * @param ImsAdapterInterface $adapter
     */
    public function __construct(ImsConfig $config, ImsAdapterInterface $adapter)
    {
        $this->config = $config;
        $this->adapter = $adapter;
    }

    /**
     * Submit a timeline job.
     *
     * @param Timeline          $timeline
     * @param OutputMediaConfig $outputMediaConfig
     * @param array             $options
     *
     * @return JobResult
     */
    public function submitTimeline(Timeline $timeline, OutputMediaConfig $outputMediaConfig, array $options = array())
    {
        $timelineArray = $timeline->toArray();
        $this->assertTimeline($timelineArray);

        $payload = array_merge(array(
            'ProjectId' => $this->config->getProjectId(),
            'Timeline' => $timelineArray,
            'OutputMediaConfig' => $this->resolveOutputMediaConfig($outputMediaConfig)->toArray(),
            'OutputMediaTarget' => $this->config->getOutputMediaTarget(),
        ), $options);

        $response = $this->adapter->submitMediaProducingJob($payload);
        $jobResult = JobResult::fromSubmitResponse($response);

        if (!$jobResult->getJobId()) {
            throw new JobSubmitException('IMS submit succeeded without JobId. Check adapter response mapping.');
        }

        return $jobResult->setRequestPayload($payload);
    }

    /**
     * Submit an IMS TemplateId job.
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
        $payload = array_merge(array(
            'ProjectId' => $this->config->getProjectId(),
            'TemplateId' => $templateId,
            'ClipsParam' => $clipsParam,
            'OutputMediaConfig' => $this->resolveOutputMediaConfig($outputMediaConfig)->toArray(),
            'OutputMediaTarget' => $this->config->getOutputMediaTarget(),
        ), $options);

        $response = $this->adapter->submitMediaProducingJob($payload);
        $jobResult = JobResult::fromSubmitResponse($response);

        if (!$jobResult->getJobId()) {
            throw new JobSubmitException('IMS template submit succeeded without JobId. Check adapter response mapping.');
        }

        return $jobResult->setRequestPayload($payload);
    }

    /**
     * Query a job.
     *
     * @param string $jobId
     *
     * @return JobResult
     */
    public function getJob($jobId)
    {
        $response = $this->adapter->getMediaProducingJob($jobId);

        return JobResult::fromQueryResponse($response);
    }

    /**
     * Assert timeline has at least one visual or audio track.
     *
     * @param array $timeline
     *
     * @return void
     */
    protected function assertTimeline(array $timeline)
    {
        $hasVideo = !empty($timeline['VideoTracks']);
        $hasAudio = !empty($timeline['AudioTracks']);

        if (!$hasVideo && !$hasAudio) {
            throw new InvalidTimelineException('Timeline must contain at least one VideoTrack or AudioTrack.');
        }
    }

    /**
     * Fill default output path when caller omitted MediaURL.
     *
     * @param OutputMediaConfig $outputMediaConfig
     *
     * @return OutputMediaConfig
     */
    protected function resolveOutputMediaConfig(OutputMediaConfig $outputMediaConfig)
    {
        $output = clone $outputMediaConfig;
        $data = $output->toArray();

        if (empty($data['MediaURL']) && $this->config->getBucket()) {
            $name = 'ims-mixcut-' . date('YmdHis') . '-' . substr(md5(uniqid('', true)), 0, 8) . '.mp4';
            $output->setMediaURL($this->config->buildOutputMediaUrl($name));
        }

        return $output;
    }
}
