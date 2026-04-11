<?php

namespace Hunjian\AliyunImsMixcut\Exception;

/**
 * Scene mixcut payload validation exception with machine-readable fields.
 */
class InvalidSceneMixcutException extends ImsException
{
    /**
     * @var string
     */
    protected $errorCodeName;

    /**
     * @var string|null
     */
    protected $path;

    /**
     * @var array
     */
    protected $details = array();

    /**
     * @param string            $errorCodeName
     * @param string            $message
     * @param string|null       $path
     * @param array             $details
     * @param int               $code
     * @param \Throwable|null $previous
     */
    public function __construct($errorCodeName, $message, $path = null, array $details = array(), $code = 0, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);

        $this->errorCodeName = $errorCodeName;
        $this->path = $path;
        $this->details = $details;
    }

    /**
     * Get stable error code name.
     *
     * @return string
     */
    public function getErrorCodeName()
    {
        return $this->errorCodeName;
    }

    /**
     * Get payload path.
     *
     * @return string|null
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Get extra details.
     *
     * @return array
     */
    public function getDetails()
    {
        return $this->details;
    }

    /**
     * Export exception payload.
     *
     * @return array
     */
    public function toArray()
    {
        return array(
            'code' => $this->errorCodeName,
            'message' => $this->getMessage(),
            'path' => $this->path,
            'details' => $this->details,
        );
    }
}
