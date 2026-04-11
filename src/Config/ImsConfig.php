<?php

namespace Hunjian\AliyunImsMixcut\Config;

use Hunjian\AliyunImsMixcut\Exception\ImsException;

/**
 * Class ImsConfig
 *
 * Runtime config for IMS job submission.
 */
class ImsConfig
{
    /**
     * @var string|null
     */
    protected $accessKeyId;

    /**
     * @var string|null
     */
    protected $accessKeySecret;

    /**
     * @var string|null
     */
    protected $endpoint;

    /**
     * @var string|null
     */
    protected $regionId;

    /**
     * @var string|null
     */
    protected $bucket;

    /**
     * @var string|null
     */
    protected $outputPathPrefix;

    /**
     * @var string|null
     */
    protected $projectId;

    /**
     * @var string|null
     */
    protected $outputMediaTarget = 'oss-object';

    /**
     * Build config from environment variables.
     *
     * @param array $map
     *
     * @return self
     */
    public static function fromEnv(array $map = array())
    {
        $defaults = array(
            'access_key_id' => array('ALIYUN_IMS_ACCESS_KEY_ID', 'ALIBABA_CLOUD_ACCESS_KEY_ID', 'ALIYUN_ACCESS_KEY_ID'),
            'access_key_secret' => array('ALIYUN_IMS_ACCESS_KEY_SECRET', 'ALIBABA_CLOUD_ACCESS_KEY_SECRET', 'ALIYUN_ACCESS_KEY_SECRET'),
            'endpoint' => array('ALIYUN_IMS_ENDPOINT'),
            'region_id' => array('ALIYUN_IMS_REGION_ID'),
            'bucket' => array('ALIYUN_IMS_BUCKET'),
            'output_path_prefix' => array('ALIYUN_IMS_OUTPUT_PATH_PREFIX'),
            'project_id' => array('ALIYUN_IMS_PROJECT_ID'),
            'output_media_target' => array('ALIYUN_IMS_OUTPUT_MEDIA_TARGET'),
        );

        $map = array_merge($defaults, $map);
        $config = new self();
        $config->setAccessKeyId(self::firstEnv($map['access_key_id']));
        $config->setAccessKeySecret(self::firstEnv($map['access_key_secret']));
        $config->setEndpoint(self::firstEnv($map['endpoint']));
        $config->setRegionId(self::firstEnv($map['region_id']));
        $config->setBucket(self::firstEnv($map['bucket']));
        $config->setOutputPathPrefix(self::firstEnv($map['output_path_prefix']));
        $config->setProjectId(self::firstEnv($map['project_id']));
        $outputMediaTarget = self::firstEnv($map['output_media_target']);
        if ($outputMediaTarget) {
            $config->setOutputMediaTarget($outputMediaTarget);
        }

        return $config;
    }

    /**
     * Return first non-empty environment value.
     *
     * @param array $keys
     *
     * @return string|null
     */
    protected static function firstEnv(array $keys)
    {
        foreach ($keys as $key) {
            $value = getenv($key);
            if ($value !== false && $value !== '') {
                return $value;
            }
        }

        return null;
    }

    /**
     * Set AccessKeyId.
     *
     * @param string|null $accessKeyId
     *
     * @return $this
     */
    public function setAccessKeyId($accessKeyId)
    {
        $this->accessKeyId = $accessKeyId;

        return $this;
    }

    /**
     * Set AccessKeySecret.
     *
     * @param string|null $accessKeySecret
     *
     * @return $this
     */
    public function setAccessKeySecret($accessKeySecret)
    {
        $this->accessKeySecret = $accessKeySecret;

        return $this;
    }

    /**
     * Set endpoint.
     *
     * @param string|null $endpoint
     *
     * @return $this
     */
    public function setEndpoint($endpoint)
    {
        $this->endpoint = $endpoint;

        return $this;
    }

    /**
     * Set region id.
     *
     * @param string|null $regionId
     *
     * @return $this
     */
    public function setRegionId($regionId)
    {
        $this->regionId = $regionId;

        return $this;
    }

    /**
     * Set output bucket.
     *
     * @param string|null $bucket
     *
     * @return $this
     */
    public function setBucket($bucket)
    {
        $this->bucket = $bucket;

        return $this;
    }

    /**
     * Set output path prefix.
     *
     * @param string|null $outputPathPrefix
     *
     * @return $this
     */
    public function setOutputPathPrefix($outputPathPrefix)
    {
        $this->outputPathPrefix = $outputPathPrefix;

        return $this;
    }

    /**
     * Set project id.
     *
     * @param string|null $projectId
     *
     * @return $this
     */
    public function setProjectId($projectId)
    {
        $this->projectId = $projectId;

        return $this;
    }

    /**
     * Set output media target.
     *
     * @param string $outputMediaTarget
     *
     * @return $this
     */
    public function setOutputMediaTarget($outputMediaTarget)
    {
        $this->outputMediaTarget = $outputMediaTarget;

        return $this;
    }

    /**
     * Validate required fields for actual official SDK submission.
     *
     * @return void
     */
    public function validate()
    {
        if (!$this->accessKeyId || !$this->accessKeySecret || !$this->endpoint || !$this->regionId) {
            throw new ImsException('IMS config is incomplete. Please provide AccessKey, endpoint and region via env vars.');
        }
    }

    /**
     * Build a default OSS output media URL.
     *
     * @param string $name
     *
     * @return string
     */
    public function buildOutputMediaUrl($name)
    {
        $prefix = trim((string) $this->outputPathPrefix, '/');
        $path = $prefix === '' ? $name : $prefix . '/' . $name;

        return 'oss://' . $this->bucket . '/' . $path;
    }

    /**
     * Get AccessKeyId.
     *
     * @return string|null
     */
    public function getAccessKeyId()
    {
        return $this->accessKeyId;
    }

    /**
     * Get AccessKeySecret.
     *
     * @return string|null
     */
    public function getAccessKeySecret()
    {
        return $this->accessKeySecret;
    }

    /**
     * Get endpoint.
     *
     * @return string|null
     */
    public function getEndpoint()
    {
        return $this->endpoint;
    }

    /**
     * Get region id.
     *
     * @return string|null
     */
    public function getRegionId()
    {
        return $this->regionId;
    }

    /**
     * Get bucket.
     *
     * @return string|null
     */
    public function getBucket()
    {
        return $this->bucket;
    }

    /**
     * Get output path prefix.
     *
     * @return string|null
     */
    public function getOutputPathPrefix()
    {
        return $this->outputPathPrefix;
    }

    /**
     * Get project id.
     *
     * @return string|null
     */
    public function getProjectId()
    {
        return $this->projectId;
    }

    /**
     * Get output media target.
     *
     * @return string|null
     */
    public function getOutputMediaTarget()
    {
        return $this->outputMediaTarget;
    }
}
