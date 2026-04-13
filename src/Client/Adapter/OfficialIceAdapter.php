<?php

namespace Hunjian\AliyunImsMixcut\Client\Adapter;

use Hunjian\AliyunImsMixcut\Config\ImsConfig;
use Hunjian\AliyunImsMixcut\Exception\ImsException;

/**
 * Class OfficialIceAdapter
 *
 * Thin wrapper around the official Alibaba Cloud PHP SDK.
 *
 * Notes:
 * - The namespaces below are verified from official examples at the time of writing.
 * - If Alibaba Cloud changes namespaces or request model class names, only this adapter
 *   should need adjustment.
 */
class OfficialIceAdapter implements ImsAdapterInterface
{
    /**
     * @var ImsConfig
     */
    protected $config;

    /**
     * @var object
     */
    protected $client;

    /**
     * Create adapter.
     *
     * @param ImsConfig $config
     */
    public function __construct(ImsConfig $config)
    {
        $this->config = $config;
        $this->config->validate();
        $this->client = $this->makeClient();
    }

    /**
     * Submit a producing job.
     *
     * @param array $payload
     *
     * @return array
     */
    public function submitMediaProducingJob(array $payload)
    {
        $requestClass = 'AlibabaCloud\\SDK\\ICE\\V20201109\\Models\\SubmitMediaProducingJobRequest';
        $request = new $requestClass($payload);
        $response = $this->client->submitMediaProducingJob($request);

        return $this->normalizeResponse($response);
    }

    /**
     * Query a producing job.
     *
     * @param string $jobId
     *
     * @return array
     */
    public function getMediaProducingJob($jobId)
    {
        $requestClass = 'AlibabaCloud\\SDK\\ICE\\V20201109\\Models\\GetMediaProducingJobRequest';
        $request = new $requestClass(array(
            'JobId' => $jobId,
        ));
        $response = $this->client->getMediaProducingJob($request);

        return $this->normalizeResponse($response);
    }

    /**
     * Create the official SDK client.
     *
     * @return object
     */
    protected function makeClient()
    {
        $credentialClass = 'AlibabaCloud\\Credentials\\Credential';
        $openApiConfigClass = 'Darabonba\\OpenApi\\Models\\Config';
        $clientClass = 'AlibabaCloud\\SDK\\ICE\\V20201109\\ICE';

        if (!class_exists($credentialClass) || !class_exists($openApiConfigClass) || !class_exists($clientClass)) {
            throw new ImsException('Official IMS SDK classes are missing. Install alibabacloud/ice-20201109 first; Composer should pull the required credentials/OpenAPI packages transitively.');
        }

        $credential = new $credentialClass(array(
            'accessKeyId' => $this->config->getAccessKeyId(),
            'accessKeySecret' => $this->config->getAccessKeySecret(),
        ));

        $openApiConfig = new $openApiConfigClass(array(
            'credential' => $credential,
            'endpoint' => $this->config->getEndpoint(),
            'regionId' => $this->config->getRegionId(),
        ));

        return new $clientClass($openApiConfig);
    }

    /**
     * Normalize SDK response object to array.
     *
     * @param mixed $response
     *
     * @return array
     */
    protected function normalizeResponse($response)
    {
        if (is_array($response)) {
            return $response;
        }

        if (is_object($response)) {
            if (method_exists($response, 'toMap')) {
                return $response->toMap();
            }

            if (method_exists($response, 'toArray')) {
                return $response->toArray();
            }

            if (method_exists($response, 'jsonSerialize')) {
                return (array) $response->jsonSerialize();
            }

            return (array) $response;
        }

        return array(
            'RawResponse' => $response,
        );
    }
}
