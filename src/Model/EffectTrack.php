<?php

namespace Hunjian\AliyunImsMixcut\Model;

/**
 * 效果轨道类
 *
 * 官方效果轨道结构。
 */
class EffectTrack extends BaseStructure
{
    /**
     * @var array
     */
    protected $items = array();

    /**
     * 添加效果轨道项目。
     *
     * @param EffectTrackItem $item
     *
     * @return $this
     */
    public function addItem(EffectTrackItem $item)
    {
        $this->items[] = $item;

        return $this;
    }

    /**
     * 将对象转换为数组。
     *
     * @return array
     */
    public function toArray()
    {
        return $this->finalize(array(
            'EffectTrackItems' => $this->items,
        ));
    }
}
