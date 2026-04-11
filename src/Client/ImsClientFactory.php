<?php

namespace Hunjian\AliyunImsMixcut\Client;

use Hunjian\AliyunImsMixcut\Client\Adapter\ImsAdapterInterface;
use Hunjian\AliyunImsMixcut\Client\Adapter\OfficialIceAdapter;
use Hunjian\AliyunImsMixcut\Client\Adapter\StubAdapter;
use Hunjian\AliyunImsMixcut\Config\ImsConfig;

/**
 * IMS 客户端工厂类
 *
 * 根据已安装的依赖项或明确偏好解析适配器。
 */
class ImsClientFactory
{
    /**
     * Create IMS adapter.
     *
     * @param ImsConfig   $config
     * @param string|null $preferred
     *
     * @return ImsAdapterInterface
     */
    public static function createAdapter(ImsConfig $config, $preferred = null)
    {
        if ($preferred === 'stub') {
            return new StubAdapter();
        }

        $sdkClass = 'AlibabaCloud\\SDK\\ICE\\V20201109\\ICE';
        $hasRuntimeConfig = $config->getAccessKeyId() && $config->getAccessKeySecret() && $config->getEndpoint() && $config->getRegionId();

        if ($preferred === 'official') {
            return new OfficialIceAdapter($config);
        }

        if (class_exists($sdkClass) && $hasRuntimeConfig) {
            return new OfficialIceAdapter($config);
        }

        return new StubAdapter();
    }
}
