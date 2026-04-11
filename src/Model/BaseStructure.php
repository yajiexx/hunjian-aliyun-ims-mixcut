<?php

namespace Hunjian\AliyunImsMixcut\Model;

use Hunjian\AliyunImsMixcut\Contracts\ArrayableInterface;
use Hunjian\AliyunImsMixcut\Support\ArrayHelper;
use Hunjian\AliyunImsMixcut\Support\Json;

/**
 * Class BaseStructure
 *
 * Shared base for timeline objects with extension field support.
 */
abstract class BaseStructure implements ArrayableInterface
{
    /**
     * @var array
     */
    protected $extraFields = array();

    /**
     * 将非官方或未来官方字段合并到结构中。
     *
     * @param array $fields
     *
     * @return $this
     */
    public function withExtraFields(array $fields)
    {
        $this->extraFields = array_merge($this->extraFields, $fields);

        return $this;
    }

    /**
     * Set a single extension field.
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return $this
     */
    public function setExtraField($key, $value)
    {
        $this->extraFields[$key] = $value;

        return $this;
    }

    /**
     * 原始扩展字段的别名。
     *
     * @param array $fields
     *
     * @return $this
     */
    public function raw(array $fields)
    {
        return $this->withExtraFields($fields);
    }

    /**
     * 将当前结构转换为 JSON。
     *
     * @param bool $pretty
     *
     * @return string
     */
    public function toJson($pretty = false)
    {
        return Json::encode($this->toArray(), $pretty);
    }

    /**
     * Finalize outgoing array.
     *
     * @param array $data
     *
     * @return array
     */
    protected function finalize(array $data)
    {
        return ArrayHelper::mergeExtra(ArrayHelper::serialize($data), $this->extraFields);
    }
}
