<?php

namespace Hunjian\AliyunImsMixcut\Model;

/**
 * Class MaterialPool
 *
 * Typed material collection used by batch mixcut scenarios.
 */
class MaterialPool extends BaseStructure
{
    /**
     * @var array
     */
    protected $videos = array();

    /**
     * @var array
     */
    protected $images = array();

    /**
     * @var array
     */
    protected $audios = array();

    /**
     * Add material to pool.
     *
     * @param Material $material
     *
     * @return $this
     */
    public function add(Material $material)
    {
        $type = $material->getType();

        if ($type === 'video') {
            $this->videos[] = $material;
        } elseif ($type === 'image') {
            $this->images[] = $material;
        } elseif ($type === 'audio') {
            $this->audios[] = $material;
        }

        return $this;
    }

    /**
     * Convert pool to template-friendly array.
     *
     * @return array
     */
    public function toTemplatePool()
    {
        return array(
            'videos' => $this->export($this->videos),
            'images' => $this->export($this->images),
            'audios' => $this->export($this->audios),
        );
    }

    /**
     * Export materials.
     *
     * @param array $items
     *
     * @return array
     */
    protected function export(array $items)
    {
        $result = array();

        foreach ($items as $item) {
            $result[] = $item->toTemplateItem();
        }

        return $result;
    }

    /**
     * Convert object to array.
     *
     * @return array
     */
    public function toArray()
    {
        return $this->toTemplatePool();
    }
}
