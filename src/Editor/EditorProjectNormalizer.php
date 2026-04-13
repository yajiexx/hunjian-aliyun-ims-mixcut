<?php

namespace Hunjian\AliyunImsMixcut\Editor;

use Hunjian\AliyunImsMixcut\Exception\InvalidEditorProjectException;
use Hunjian\AliyunImsMixcut\Model\OutputMediaConfig;
use Hunjian\AliyunImsMixcut\Support\ArrayHelper;

/**
 * Normalize editor project payloads into a stable internal structure.
 */
class EditorProjectNormalizer
{
    /**
     * Normalize top-level project payload.
     *
     * @param array $project
     *
     * @return array
     */
    public function normalize(array $project)
    {
        $canvas = isset($project['canvas']) && is_array($project['canvas']) ? $project['canvas'] : array();
        $width = isset($canvas['width']) ? $this->normalizePositiveNumber($canvas['width'], 'canvas.width') : 1080.0;
        $height = isset($canvas['height']) ? $this->normalizePositiveNumber($canvas['height'], 'canvas.height') : 1920.0;

        if (empty($project['sequence']) || !is_array($project['sequence'])) {
            $this->fail('INVALID_EDITOR_SEQUENCE', 'Editor project must contain a sequence.', 'sequence');
        }

        $sequence = $project['sequence'];
        $duration = isset($sequence['duration'])
            ? $this->normalizePositiveNumber($sequence['duration'], 'sequence.duration')
            : 0.0;

        $layers = isset($sequence['layers']) ? $this->normalizeCollection($sequence['layers']) : array();
        if (empty($layers)) {
            $this->fail('INVALID_EDITOR_LAYERS', 'Editor project sequence must contain at least one layer.', 'sequence.layers');
        }

        $normalizedLayers = array();
        foreach ($layers as $layerIndex => $layer) {
            if (!is_array($layer)) {
                $this->fail('INVALID_EDITOR_LAYER', 'Each layer must be an array.', 'sequence.layers[' . $layerIndex . ']');
            }

            $layerPath = 'sequence.layers[' . $layerIndex . ']';
            $layerType = isset($layer['type']) ? strtolower((string) $layer['type']) : '';
            if (!in_array($layerType, array('background', 'video', 'audio', 'text', 'element'), true)) {
                $this->fail('INVALID_EDITOR_LAYER_TYPE', 'Unsupported editor layer type.', $layerPath . '.type');
            }

            $items = isset($layer['items']) ? $this->normalizeCollection($layer['items']) : array();
            if (empty($items)) {
                $this->fail('INVALID_EDITOR_LAYER_ITEMS', 'Each layer must contain at least one item.', $layerPath . '.items');
            }

            $normalizedItems = array();
            foreach ($items as $itemIndex => $item) {
                if (!is_array($item)) {
                    $this->fail('INVALID_EDITOR_ITEM', 'Each layer item must be an array.', $layerPath . '.items[' . $itemIndex . ']');
                }

                $normalizedItems[] = $this->normalizeItem(
                    $item,
                    $layerType,
                    $layerIndex,
                    $itemIndex,
                    $width,
                    $height
                );
            }

            $normalizedLayers[] = array(
                'layerId' => isset($layer['layerId']) && $layer['layerId'] !== ''
                    ? (string) $layer['layerId']
                    : $layerType . '-' . ($layerIndex + 1),
                'type' => $layerType,
                'items' => $normalizedItems,
            );
        }

        return array(
            'canvas' => array(
                'width' => (int) $width,
                'height' => (int) $height,
            ),
            'sequence' => array(
                'duration' => $duration,
                'layers' => $normalizedLayers,
            ),
            'outputMediaConfig' => isset($project['outputMediaConfig']) && $project['outputMediaConfig'] instanceof OutputMediaConfig
                ? $project['outputMediaConfig']
                : null,
            'outputMediaURL' => isset($project['outputMediaURL']) ? $project['outputMediaURL'] : null,
        );
    }

    /**
     * Normalize one layer item.
     *
     * @param array $item
     * @param string $layerType
     * @param int $layerIndex
     * @param int $itemIndex
     * @param float $canvasWidth
     * @param float $canvasHeight
     *
     * @return array
     */
    protected function normalizeItem(array $item, $layerType, $layerIndex, $itemIndex, $canvasWidth, $canvasHeight)
    {
        $itemPath = 'sequence.layers[' . $layerIndex . '].items[' . $itemIndex . ']';
        $type = isset($item['type']) ? strtolower((string) $item['type']) : $this->defaultItemTypeForLayer($layerType);

        return array(
            'clipId' => isset($item['clipId']) && $item['clipId'] !== ''
                ? (string) $item['clipId']
                : $layerType . '-' . ($layerIndex + 1) . '-item-' . ($itemIndex + 1),
            'type' => $type,
            'text' => isset($item['text']) ? $item['text'] : null,
            'url' => isset($item['url']) ? $item['url'] : null,
            'color' => isset($item['color']) ? $item['color'] : null,
            'role' => isset($item['role']) ? strtolower((string) $item['role']) : null,
            'start' => isset($item['start']) ? $this->normalizeNonNegativeNumber($item['start'], $itemPath . '.start') : 0.0,
            'duration' => isset($item['duration']) ? $this->normalizePositiveNumber($item['duration'], $itemPath . '.duration') : 0.0,
            'loop' => !empty($item['loop']),
            'volume' => isset($item['volume']) ? (float) $item['volume'] : null,
            'zIndex' => isset($item['zIndex']) ? (int) $item['zIndex'] : null,
            'sourceRange' => $this->normalizeRange(
                isset($item['sourceRange']) ? $item['sourceRange'] : null,
                $itemPath . '.sourceRange',
                'in',
                'out'
            ),
            'layout' => $this->normalizeLayout(
                isset($item['layout']) && is_array($item['layout']) ? $item['layout'] : array(),
                $canvasWidth,
                $canvasHeight,
                $layerType === 'text'
            ),
            'style' => $this->normalizeStyle(
                isset($item['style']) && is_array($item['style']) ? $item['style'] : array(),
                $layerType === 'text'
            ),
            'transition' => $this->normalizeTransition(isset($item['transition']) ? $item['transition'] : null),
            'animations' => $this->normalizeAnimations(
                isset($item['animations']) ? $this->normalizeCollection($item['animations']) : array(),
                $itemPath . '.animations'
            ),
            'raw' => isset($item['raw']) && is_array($item['raw']) ? $item['raw'] : array(),
        );
    }

    /**
     * Normalize item collections while accepting one item.
     *
     * @param mixed $value
     *
     * @return array
     */
    protected function normalizeCollection($value)
    {
        if ($value === null) {
            return array();
        }

        if (!is_array($value)) {
            return array($value);
        }

        return ArrayHelper::isAssoc($value) ? array($value) : $value;
    }

    /**
     * Normalize range arrays.
     *
     * @param mixed $range
     * @param string $path
     * @param string $startKey
     * @param string $endKey
     *
     * @return array|null
     */
    protected function normalizeRange($range, $path, $startKey = 'start', $endKey = 'end')
    {
        if ($range === null || $range === array()) {
            return null;
        }

        if (!is_array($range)) {
            $this->fail('INVALID_EDITOR_RANGE', 'Range must be an array.', $path);
        }

        $start = $this->normalizeNonNegativeNumber(isset($range[$startKey]) ? $range[$startKey] : null, $path . '.' . $startKey);
        $end = $this->normalizePositiveNumber(isset($range[$endKey]) ? $range[$endKey] : null, $path . '.' . $endKey);
        if ($end <= $start) {
            $this->fail('INVALID_EDITOR_RANGE', 'Range end must be greater than start.', $path . '.' . $endKey);
        }

        return array(
            $startKey => $start,
            $endKey => $end,
        );
    }

    /**
     * Normalize item layout.
     *
     * @param array $layout
     * @param float $canvasWidth
     * @param float $canvasHeight
     * @param bool $textLayer
     *
     * @return array
     */
    protected function normalizeLayout(array $layout, $canvasWidth, $canvasHeight, $textLayer)
    {
        if ($textLayer) {
            return array(
                'x' => isset($layout['x']) ? (float) $layout['x'] : 80.0,
                'y' => isset($layout['y']) ? (float) $layout['y'] : ($canvasHeight - 360.0),
                'width' => isset($layout['width']) ? (float) $layout['width'] : ($canvasWidth - 160.0),
                'height' => isset($layout['height']) ? (float) $layout['height'] : 220.0,
                'alignment' => isset($layout['alignment']) ? $layout['alignment'] : 'BottomCenter',
                'adaptMode' => isset($layout['adaptMode']) ? $layout['adaptMode'] : 'Cover',
            );
        }

        return array(
            'x' => isset($layout['x']) ? (float) $layout['x'] : 0.0,
            'y' => isset($layout['y']) ? (float) $layout['y'] : 0.0,
            'width' => isset($layout['width']) ? (float) $layout['width'] : $canvasWidth,
            'height' => isset($layout['height']) ? (float) $layout['height'] : $canvasHeight,
            'alignment' => isset($layout['alignment']) ? $layout['alignment'] : 'Center',
            'adaptMode' => isset($layout['adaptMode']) ? $layout['adaptMode'] : 'Cover',
        );
    }

    /**
     * Normalize text style.
     *
     * @param array $style
     * @param bool $textLayer
     *
     * @return array
     */
    protected function normalizeStyle(array $style, $textLayer)
    {
        $defaults = $textLayer
            ? array(
                'font' => 'Alibaba PuHuiTi 2.0',
                'fontSize' => 48,
                'fontColor' => '#FFFFFF',
                'boxColor' => null,
                'boxOpacity' => 0.35,
                'boxBord' => 20,
                'autoWrap' => true,
                'fixedFontSize' => true,
            )
            : array();

        foreach ($style as $key => $value) {
            if ($value !== null) {
                $defaults[$key] = $value;
            }
        }

        return $defaults;
    }

    /**
     * Normalize transition data.
     *
     * @param mixed $transition
     *
     * @return array|null
     */
    protected function normalizeTransition($transition)
    {
        if ($transition === null || $transition === '' || $transition === array()) {
            return null;
        }

        if (is_string($transition)) {
            return array(
                'type' => $transition,
                'duration' => 0.4,
            );
        }

        if (!is_array($transition) || empty($transition['type'])) {
            return null;
        }

        return array(
            'type' => (string) $transition['type'],
            'duration' => isset($transition['duration']) ? (float) $transition['duration'] : 0.4,
        );
    }

    /**
     * Normalize item animations.
     *
     * @param array $animations
     * @param string $path
     *
     * @return array
     */
    protected function normalizeAnimations(array $animations, $path)
    {
        $normalized = array();
        foreach ($animations as $index => $animation) {
            if (!is_array($animation)) {
                $this->fail('INVALID_EDITOR_ANIMATION', 'Animation must be an array.', $path . '[' . $index . ']');
            }

            if (empty($animation['preset'])) {
                $this->fail('INVALID_EDITOR_ANIMATION', 'Animation preset is required.', $path . '[' . $index . '].preset');
            }

            $normalized[] = array(
                'phase' => isset($animation['phase']) ? (string) $animation['phase'] : 'in',
                'preset' => (string) $animation['preset'],
                'duration' => isset($animation['duration']) ? (float) $animation['duration'] : 0.3,
            );
        }

        return $normalized;
    }

    /**
     * Normalize a positive number.
     *
     * @param mixed $value
     * @param string $path
     *
     * @return float
     */
    protected function normalizePositiveNumber($value, $path)
    {
        if (!is_numeric($value)) {
            $this->fail('INVALID_EDITOR_NUMBER', 'Numeric value expected.', $path);
        }

        $number = (float) $value;
        if ($number <= 0) {
            $this->fail('INVALID_EDITOR_NUMBER', 'Numeric value must be greater than 0.', $path);
        }

        return $number;
    }

    /**
     * Normalize a non-negative number.
     *
     * @param mixed $value
     * @param string $path
     *
     * @return float
     */
    protected function normalizeNonNegativeNumber($value, $path)
    {
        if (!is_numeric($value)) {
            $this->fail('INVALID_EDITOR_NUMBER', 'Numeric value expected.', $path);
        }

        $number = (float) $value;
        if ($number < 0) {
            $this->fail('INVALID_EDITOR_NUMBER', 'Numeric value must not be negative.', $path);
        }

        return $number;
    }

    /**
     * Get default item type for one layer type.
     *
     * @param string $layerType
     *
     * @return string
     */
    protected function defaultItemTypeForLayer($layerType)
    {
        if ($layerType === 'background') {
            return 'image';
        }

        if ($layerType === 'audio') {
            return 'audio';
        }

        if ($layerType === 'text') {
            return 'text';
        }

        return 'video';
    }

    /**
     * Throw a payload exception.
     *
     * @param string $code
     * @param string $message
     * @param string|null $path
     *
     * @return void
     */
    protected function fail($code, $message, $path = null)
    {
        throw new InvalidEditorProjectException($code, $message, $path);
    }
}
