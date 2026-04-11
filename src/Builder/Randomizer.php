<?php

namespace Hunjian\AliyunImsMixcut\Builder;

/**
 * Class Randomizer
 *
 * Controlled randomness helper for batch mixcut generation.
 */
class Randomizer
{
    /**
     * Create randomizer with optional deterministic seed.
     *
     * @param int|null $seed
     */
    public function __construct($seed = null)
    {
        if ($seed !== null) {
            mt_srand((int) $seed);
        }
    }

    /**
     * Pick one item.
     *
     * @param array $items
     *
     * @return mixed|null
     */
    public function pick(array $items)
    {
        if (empty($items)) {
            return null;
        }

        return $items[array_rand($items)];
    }

    /**
     * Pick N items without preserving order.
     *
     * @param array $items
     * @param int   $count
     *
     * @return array
     */
    public function pickMany(array $items, $count)
    {
        $items = array_values($items);
        $count = min((int) $count, count($items));
        $keys = array_rand($items, $count);
        $keys = is_array($keys) ? $keys : array($keys);
        $result = array();

        foreach ($keys as $key) {
            $result[] = $items[$key];
        }

        return $result;
    }

    /**
     * Shuffle items.
     *
     * @param array $items
     *
     * @return array
     */
    public function shuffle(array $items)
    {
        shuffle($items);

        return $items;
    }

    /**
     * Random integer between min and max.
     *
     * @param int $min
     * @param int $max
     *
     * @return int
     */
    public function int($min, $max)
    {
        return mt_rand((int) $min, (int) $max);
    }

    /**
     * Random float between min and max.
     *
     * @param float $min
     * @param float $max
     *
     * @return float
     */
    public function float($min, $max)
    {
        return $min + (mt_rand() / mt_getrandmax()) * ($max - $min);
    }
}
