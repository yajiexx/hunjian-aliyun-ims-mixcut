<?php

namespace Hunjian\AliyunImsMixcut\Model;

/**
 * Class OutputMediaConfig
 *
 * Mirrors official OutputMediaConfig request object.
 */
class OutputMediaConfig extends BaseStructure
{
    /**
     * @var string|null
     */
    protected $mediaURL;

    /**
     * @var string|null
     */
    protected $storageLocation;

    /**
     * @var string|null
     */
    protected $fileName;

    /**
     * @var int|null
     */
    protected $width;

    /**
     * @var int|null
     */
    protected $height;

    /**
     * @var int|null
     */
    protected $bitrate;

    /**
     * Build OSS output config.
     *
     * @param string $mediaURL
     *
     * @return self
     */
    public static function oss($mediaURL)
    {
        $config = new self();
        $config->setMediaURL($mediaURL);

        return $config;
    }

    /**
     * Build VOD output config.
     *
     * @param string $storageLocation
     * @param string $fileName
     *
     * @return self
     */
    public static function vod($storageLocation, $fileName)
    {
        $config = new self();
        $config->setStorageLocation($storageLocation);
        $config->setFileName($fileName);

        return $config;
    }

    /**
     * Set MediaURL.
     *
     * @param string $mediaURL
     *
     * @return $this
     */
    public function setMediaURL($mediaURL)
    {
        $this->mediaURL = $mediaURL;

        return $this;
    }

    /**
     * Set VOD storage location.
     *
     * @param string $storageLocation
     *
     * @return $this
     */
    public function setStorageLocation($storageLocation)
    {
        $this->storageLocation = $storageLocation;

        return $this;
    }

    /**
     * Set VOD file name.
     *
     * @param string $fileName
     *
     * @return $this
     */
    public function setFileName($fileName)
    {
        $this->fileName = $fileName;

        return $this;
    }

    /**
     * Set output size.
     *
     * @param int $width
     * @param int $height
     *
     * @return $this
     */
    public function setSize($width, $height)
    {
        $this->width = (int) $width;
        $this->height = (int) $height;

        return $this;
    }

    /**
     * Set output bitrate.
     *
     * @param int $bitrate
     *
     * @return $this
     */
    public function setBitrate($bitrate)
    {
        $this->bitrate = (int) $bitrate;

        return $this;
    }

    /**
     * Convert object to array.
     *
     * @return array
     */
    public function toArray()
    {
        return $this->finalize(array(
            'MediaURL' => $this->mediaURL,
            'StorageLocation' => $this->storageLocation,
            'FileName' => $this->fileName,
            'Width' => $this->width,
            'Height' => $this->height,
            'Bitrate' => $this->bitrate,
        ));
    }
}
