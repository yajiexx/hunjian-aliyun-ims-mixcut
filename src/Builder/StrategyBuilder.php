<?php

namespace Hunjian\AliyunImsMixcut\Builder;

/**
 * Class StrategyBuilder
 *
 * Strategy pools used by batch random mixcut templates.
 */
class StrategyBuilder
{
    /**
     * @var array
     */
    protected $strategy = array(
        'transitions' => array(),
        'filters' => array(),
        'vfx' => array(),
        'bgms' => array(),
        'subtitleStyles' => array(),
        'clipDurations' => array(2.0, 3.0, 4.0),
        'layouts' => array('single'),
    );

    /**
     * Set transition pool.
     *
     * @param array $items
     *
     * @return $this
     */
    public function transitions(array $items)
    {
        $this->strategy['transitions'] = $items;

        return $this;
    }

    /**
     * Set filter pool.
     *
     * @param array $items
     *
     * @return $this
     */
    public function filters(array $items)
    {
        $this->strategy['filters'] = $items;

        return $this;
    }

    /**
     * Set VFX pool.
     *
     * @param array $items
     *
     * @return $this
     */
    public function vfx(array $items)
    {
        $this->strategy['vfx'] = $items;

        return $this;
    }

    /**
     * Set BGM pool.
     *
     * @param array $items
     *
     * @return $this
     */
    public function bgms(array $items)
    {
        $this->strategy['bgms'] = $items;

        return $this;
    }

    /**
     * Set subtitle style pool.
     *
     * @param array $items
     *
     * @return $this
     */
    public function subtitleStyles(array $items)
    {
        $this->strategy['subtitleStyles'] = $items;

        return $this;
    }

    /**
     * Set clip duration pool.
     *
     * @param array $items
     *
     * @return $this
     */
    public function clipDurations(array $items)
    {
        $this->strategy['clipDurations'] = $items;

        return $this;
    }

    /**
     * Set layout pool.
     *
     * @param array $items
     *
     * @return $this
     */
    public function layouts(array $items)
    {
        $this->strategy['layouts'] = $items;

        return $this;
    }

    /**
     * Get complete strategy.
     *
     * @return array
     */
    public function build()
    {
        return $this->strategy;
    }

    /**
     * Pick one randomized strategy.
     *
     * @param Randomizer $randomizer
     *
     * @return array
     */
    public function pick(Randomizer $randomizer)
    {
        return array(
            'transition' => $randomizer->pick($this->strategy['transitions']),
            'filter' => $randomizer->pick($this->strategy['filters']),
            'vfx' => $randomizer->pick($this->strategy['vfx']),
            'bgm' => $randomizer->pick($this->strategy['bgms']),
            'subtitleStyle' => $randomizer->pick($this->strategy['subtitleStyles']),
            'clipDuration' => $randomizer->pick($this->strategy['clipDurations']),
            'layout' => $randomizer->pick($this->strategy['layouts']),
        );
    }
}
