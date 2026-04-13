<?php

namespace Hunjian\AliyunImsMixcut\Scene;

use Hunjian\AliyunImsMixcut\Exception\InvalidSceneMixcutException;
use Hunjian\AliyunImsMixcut\Model\OutputMediaConfig;

/**
 * 将编辑器风格的场景负载规范化为稳定的内部结构。
 */
class ScenePayloadNormalizer
{
    /**
     * 规范化顶层上下文。
     *
     * @param array $context
     *
     * @return array
     */
    public function normalize(array $context)
    {
        $scenes = isset($context['scenes']) ? $this->normalizeCollection($context['scenes']) : array();
        if (empty($scenes)) {
            $this->fail('INVALID_SCENE_LIST', 'Scene mixcut payload must contain at least one scene.', 'scenes');
        }

        $canvas = isset($context['canvas']) && is_array($context['canvas']) ? $context['canvas'] : array();
        $width = isset($canvas['width']) ? (int) $canvas['width'] : 1080;
        $height = isset($canvas['height']) ? (int) $canvas['height'] : 1920;
        if ($width <= 0) {
            $this->fail('INVALID_CANVAS_SIZE', 'Canvas width must be greater than 0.', 'canvas.width');
        }
        if ($height <= 0) {
            $this->fail('INVALID_CANVAS_SIZE', 'Canvas height must be greater than 0.', 'canvas.height');
        }

        $global = isset($context['global']) && is_array($context['global']) ? $context['global'] : array();
        $globalSubtitleStyle = $this->normalizeSubtitleStyle(isset($global['subtitleStyle']) ? $global['subtitleStyle'] : array(), false);
        $globalWordArtStyle = $this->normalizeSubtitleStyle(
            isset($global['wordArtStyle']) ? $global['wordArtStyle'] : array(),
            true
        );

        $normalizedScenes = array();
        foreach ($scenes as $sceneIndex => $scene) {
            if (!is_array($scene)) {
                $this->fail('INVALID_SCENE', 'Each scene must be an array.', 'scenes[' . $sceneIndex . ']');
            }

            $scenePath = 'scenes[' . $sceneIndex . ']';
            $sceneId = isset($scene['sceneId']) && $scene['sceneId'] !== ''
                ? (string) $scene['sceneId']
                : 'scene-' . ($sceneIndex + 1);

            $sceneDuration = isset($scene['sceneDuration']) ? $this->normalizeNumber($scene['sceneDuration'], $scenePath . '.sceneDuration') : null;
            $sceneSubtitleStyle = $this->normalizeSubtitleStyle(
                $this->mergeAssoc($globalSubtitleStyle, isset($scene['subtitleStyle']) && is_array($scene['subtitleStyle']) ? $scene['subtitleStyle'] : array()),
                false
            );
            $sceneWordArtStyle = $this->normalizeSubtitleStyle(
                $this->mergeAssoc($globalWordArtStyle, isset($scene['wordArtStyle']) && is_array($scene['wordArtStyle']) ? $scene['wordArtStyle'] : array()),
                true
            );

            $materials = isset($scene['materials']) ? $this->normalizeCollection($scene['materials']) : array();
            if (empty($materials)) {
                $this->fail('INVALID_SCENE_MATERIALS', 'Each scene must contain at least one material.', $scenePath . '.materials');
            }

            $normalizedMaterials = array();
            $materialIds = array();
            foreach ($materials as $materialIndex => $material) {
                if (!is_array($material)) {
                    $this->fail('INVALID_SCENE_MATERIAL', 'Each material must be an array.', $scenePath . '.materials[' . $materialIndex . ']');
                }

                $materialPath = $scenePath . '.materials[' . $materialIndex . ']';
                $type = isset($material['type']) ? strtolower((string) $material['type']) : null;
                if (!in_array($type, array('video', 'image'), true)) {
                    $this->fail('INVALID_SCENE_MATERIAL_TYPE', 'Material type must be video or image.', $materialPath . '.type');
                }
                if (empty($material['url'])) {
                    $this->fail('INVALID_SCENE_MATERIAL_URL', 'Material url is required.', $materialPath . '.url');
                }

                $materialId = isset($material['materialId']) && $material['materialId'] !== ''
                    ? (string) $material['materialId']
                    : $sceneId . '-material-' . ($materialIndex + 1);

                $duration = isset($material['duration']) ? $this->normalizeNumber($material['duration'], $materialPath . '.duration') : null;
                $sceneRange = $this->normalizeRange(isset($material['sceneRange']) ? $material['sceneRange'] : null, $materialPath . '.sceneRange');
                $sourceRange = $this->normalizeRange(isset($material['sourceRange']) ? $material['sourceRange'] : null, $materialPath . '.sourceRange', 'in', 'out');
                $layout = $this->normalizeLayout(
                    isset($material['layout']) && is_array($material['layout']) ? $material['layout'] : array(),
                    $width,
                    $height,
                    false
                );

                $normalizedMaterials[] = array(
                    'materialId' => $materialId,
                    'type' => $type,
                    'url' => $material['url'],
                    'duration' => $duration,
                    'zOrder' => $this->normalizeZOrder($material, $materialPath),
                    'sceneRange' => $sceneRange,
                    'sourceRange' => $sourceRange,
                    'layout' => $layout,
                    'transition' => $this->normalizeTransition(isset($material['transition']) ? $material['transition'] : null),
                    'raw' => isset($material['raw']) && is_array($material['raw']) ? $material['raw'] : array(),
                );
                $materialIds[$materialId] = true;
            }

            $subtitles = $this->normalizeSubtitleItems(
                isset($scene['subtitles']) ? $this->normalizeCollection($scene['subtitles']) : array(),
                $scenePath . '.subtitles',
                $materialIds,
                $sceneSubtitleStyle,
                false,
                $width,
                $height
            );
            $wordArts = $this->normalizeSubtitleItems(
                isset($scene['wordArts']) ? $this->normalizeCollection($scene['wordArts']) : array(),
                $scenePath . '.wordArts',
                $materialIds,
                $sceneWordArtStyle,
                true,
                $width,
                $height
            );

            $normalizedScenes[] = array(
                'sceneId' => $sceneId,
                'sceneDuration' => $sceneDuration,
                'transition' => $this->normalizeTransition(
                    isset($scene['transition']) ? $scene['transition'] : (isset($global['sceneTransition']) ? $global['sceneTransition'] : null)
                ),
                'materials' => $normalizedMaterials,
                'dubbing' => $this->normalizeDubbing(
                    isset($scene['dubbing']) && is_array($scene['dubbing']) ? $scene['dubbing'] : array(),
                    $scenePath . '.dubbing'
                ),
                'subtitles' => $subtitles,
                'wordArts' => $wordArts,
                'subtitleStyle' => $sceneSubtitleStyle,
                'wordArtStyle' => $sceneWordArtStyle,
                'raw' => isset($scene['raw']) && is_array($scene['raw']) ? $scene['raw'] : array(),
            );
        }

        return array(
            'canvas' => array(
                'width' => $width,
                'height' => $height,
            ),
            'global' => array(
                'bgm' => isset($global['bgm']) ? $global['bgm'] : null,
                'watermark' => isset($global['watermark']) ? $global['watermark'] : null,
                'subtitleStyle' => $globalSubtitleStyle,
                'wordArtStyle' => $globalWordArtStyle,
                'sceneTransition' => $this->normalizeTransition(isset($global['sceneTransition']) ? $global['sceneTransition'] : null),
            ),
            'scenes' => $normalizedScenes,
            'outputMediaConfig' => isset($context['outputMediaConfig']) && $context['outputMediaConfig'] instanceof OutputMediaConfig
                ? $context['outputMediaConfig']
                : null,
            'outputMediaURL' => isset($context['outputMediaURL']) ? $context['outputMediaURL'] : null,
        );
    }

    /**
     * 规范化配音输入。
     *
     * @param array  $dubbing
     * @param string $path
     *
     * @return array|null
     */
    protected function normalizeDubbing(array $dubbing, $path)
    {
        if (empty($dubbing)) {
            return null;
        }

        if (isset($dubbing['audio']) && !isset($dubbing['audioUrl'])) {
            $dubbing['audioUrl'] = $dubbing['audio'];
        }
        if (isset($dubbing['voiceName']) && !isset($dubbing['voice'])) {
            $dubbing['voice'] = $dubbing['voiceName'];
        }

        $hasAudioUrl = !empty($dubbing['audioUrl']);
        $hasTts = !empty($dubbing['text']) && !empty($dubbing['voice']);
        if (!$hasAudioUrl && !$hasTts) {
            $this->fail('INVALID_SCENE_DUBBING', 'Dubbing must provide audioUrl or text + voice.', $path);
        }

        return array(
            'audioUrl' => $hasAudioUrl ? $dubbing['audioUrl'] : null,
            'text' => isset($dubbing['text']) ? $dubbing['text'] : null,
            'voice' => isset($dubbing['voice']) ? $dubbing['voice'] : null,
            'duration' => isset($dubbing['duration']) ? $this->normalizeNumber($dubbing['duration'], $path . '.duration') : null,
            'speechRate' => isset($dubbing['speechRate']) ? (int) $dubbing['speechRate'] : 0,
            'pitchRate' => isset($dubbing['pitchRate']) ? (int) $dubbing['pitchRate'] : 0,
            'ssml' => isset($dubbing['ssml']) ? $dubbing['ssml'] : null,
        );
    }

    /**
     * 规范化字幕或艺术字集合。
     *
     * @param array  $items
     * @param string $path
     * @param array  $materialIds
     * @param array  $defaultStyle
     * @param bool   $wordArt
     * @param int    $canvasWidth
     * @param int    $canvasHeight
     *
     * @return array
     */
    protected function normalizeSubtitleItems(array $items, $path, array $materialIds, array $defaultStyle, $wordArt, $canvasWidth, $canvasHeight)
    {
        $normalized = array();
        foreach ($items as $index => $item) {
            if (!is_array($item)) {
                $this->fail('INVALID_SCENE_ITEM', 'Subtitle items must be arrays.', $path . '[' . $index . ']');
            }

            $itemPath = $path . '[' . $index . ']';
            if (!isset($item['text']) || $item['text'] === '') {
                $this->fail('INVALID_SCENE_ITEM_TEXT', 'Subtitle text is required.', $itemPath . '.text');
            }

            $start = $this->normalizeNumber(isset($item['start']) ? $item['start'] : null, $itemPath . '.start');
            $end = $this->normalizeNumber(isset($item['end']) ? $item['end'] : null, $itemPath . '.end');
            if ($end <= $start) {
                $this->fail('INVALID_SCENE_ITEM_RANGE', 'Subtitle end time must be greater than start time.', $itemPath . '.end');
            }

            $referenceMaterialId = isset($item['referenceMaterialId']) ? $item['referenceMaterialId'] : null;
            if ($referenceMaterialId !== null && !isset($materialIds[$referenceMaterialId])) {
                $this->fail('INVALID_SCENE_REFERENCE', 'referenceMaterialId must exist in the current scene.', $itemPath . '.referenceMaterialId');
            }

            $style = $this->normalizeSubtitleStyle(
                $this->mergeAssoc($defaultStyle, isset($item['style']) && is_array($item['style']) ? $item['style'] : array()),
                $wordArt
            );

            $normalized[] = array(
                'text' => $item['text'],
                'start' => $start,
                'end' => $end,
                'referenceMaterialId' => $referenceMaterialId,
                'layout' => $this->normalizeLayout(
                    isset($item['layout']) && is_array($item['layout']) ? $item['layout'] : array(),
                    $canvasWidth,
                    $canvasHeight,
                    $wordArt
                ),
                'style' => $style,
                'preset' => isset($item['preset']) ? $item['preset'] : null,
                'raw' => isset($item['raw']) && is_array($item['raw']) ? $item['raw'] : array(),
            );
        }

        return $normalized;
    }

    /**
     * 规范化范围数组。
     *
     * @param array|null $range
     * @param string     $path
     * @param string     $startKey
     * @param string     $endKey
     *
     * @return array|null
     */
    protected function normalizeRange($range, $path, $startKey = 'start', $endKey = 'end')
    {
        if ($range === null || $range === array()) {
            return null;
        }

        if (!is_array($range)) {
            $this->fail('INVALID_SCENE_RANGE', 'Range must be an array.', $path);
        }

        $start = $this->normalizeNumber(isset($range[$startKey]) ? $range[$startKey] : null, $path . '.' . $startKey);
        $end = $this->normalizeNumber(isset($range[$endKey]) ? $range[$endKey] : null, $path . '.' . $endKey);
        if ($end <= $start) {
            $this->fail('INVALID_SCENE_RANGE', 'Range end must be greater than start.', $path . '.' . $endKey);
        }

        return array(
            $startKey => $start,
            $endKey => $end,
        );
    }

    /**
     * 规范化场景/项目布局。
     *
     * @param array $layout
     * @param int   $canvasWidth
     * @param int   $canvasHeight
     * @param bool  $wordArt
     *
     * @return array
     */
    protected function normalizeLayout(array $layout, $canvasWidth, $canvasHeight, $wordArt)
    {
        if ($wordArt) {
            return array(
                'x' => isset($layout['x']) ? (float) $layout['x'] : 120.0,
                'y' => isset($layout['y']) ? (float) $layout['y'] : 360.0,
                'width' => isset($layout['width']) ? (float) $layout['width'] : 420.0,
                'height' => isset($layout['height']) ? (float) $layout['height'] : 140.0,
                'alignment' => isset($layout['alignment']) ? $layout['alignment'] : 'Center',
            );
        }

        return array(
            'x' => isset($layout['x']) ? (float) $layout['x'] : 0.0,
            'y' => isset($layout['y']) ? (float) $layout['y'] : 0.0,
            'width' => isset($layout['width']) ? (float) $layout['width'] : (float) $canvasWidth,
            'height' => isset($layout['height']) ? (float) $layout['height'] : (float) $canvasHeight,
            'adaptMode' => isset($layout['adaptMode']) ? $layout['adaptMode'] : 'Cover',
            'alignment' => isset($layout['alignment']) ? $layout['alignment'] : 'BottomCenter',
        );
    }

    /**
     * 规范化转场数据。
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

        $duration = isset($transition['duration']) ? (float) $transition['duration'] : 0.4;
        if ($duration < 0) {
            $duration = 0.0;
        }

        return array(
            'type' => $transition['type'],
            'duration' => $duration,
        );
    }

    /**
     * 规范化合并样式数组。
     *
     * @param array $style
     * @param bool  $wordArt
     *
     * @return array
     */
    protected function normalizeSubtitleStyle(array $style, $wordArt)
    {
        $defaults = $wordArt
            ? array(
                'font' => 'Alibaba PuHuiTi 2.0',
                'fontSize' => 56,
                'fontColor' => '#FF9F1C',
                'boxColor' => '#101820',
                'boxOpacity' => 0.0,
                'boxBord' => 16,
                'outlineColor' => '#111111',
                'outlineBord' => 5,
                'shadowColor' => null,
                'shadowOffsetX' => 0,
                'shadowOffsetY' => 0,
                'autoWrap' => true,
                'fixedFontSize' => true,
            )
            : array(
                'font' => 'Alibaba PuHuiTi 2.0',
                'fontSize' => 48,
                'fontColor' => '#FFFFFF',
                'boxColor' => '#000000',
                'boxOpacity' => 0.35,
                'boxBord' => 22,
                'outlineColor' => '#000000',
                'outlineBord' => 4,
                'shadowColor' => null,
                'shadowOffsetX' => 0,
                'shadowOffsetY' => 0,
                'autoWrap' => true,
                'fixedFontSize' => true,
            );

        return $this->mergeAssoc($defaults, $style);
    }

    /**
     * 规范化集合，同时接受单个项目。
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

        return $this->isAssoc($value) ? array($value) : $value;
    }

    /**
     * 规范化数值并确保非负。
     *
     * @param mixed  $value
     * @param string $path
     *
     * @return float
     */
    protected function normalizeNumber($value, $path)
    {
        if (!is_numeric($value)) {
            $this->fail('INVALID_SCENE_NUMBER', 'Numeric value expected.', $path);
        }

        $number = (float) $value;
        if ($number < 0) {
            $this->fail('INVALID_SCENE_NUMBER', 'Numeric value must not be negative.', $path);
        }

        return $number;
    }

    /**
     * 规范化素材的 z-order / 图层。
     *
     * @param array  $material
     * @param string $path
     *
     * @return int|null
     */
    protected function normalizeZOrder(array $material, $path)
    {
        $candidate = null;
        if (array_key_exists('zOrder', $material)) {
            $candidate = $material['zOrder'];
        } elseif (array_key_exists('layer', $material)) {
            $candidate = $material['layer'];
        }

        if ($candidate === null || $candidate === '') {
            return null;
        }

        if (!is_numeric($candidate)) {
            $this->fail('INVALID_SCENE_ZORDER', 'zOrder or layer must be numeric.', $path . '.zOrder');
        }

        return (int) $candidate;
    }

    /**
     * 判断数组是否为关联数组。
     *
     * @param array $data
     *
     * @return bool
     */
    protected function isAssoc(array $data)
    {
        return array_keys($data) !== range(0, count($data) - 1);
    }

    /**
     * 递归合并关联数组。
     *
     * @param array $base
     * @param array $overrides
     *
     * @return array
     */
    protected function mergeAssoc(array $base, array $overrides)
    {
        foreach ($overrides as $key => $value) {
            if ($value !== null) {
                $base[$key] = $value;
            }
        }

        return $base;
    }

    /**
     * 抛出负载异常。
     *
     * @param string      $code
     * @param string      $message
     * @param string|null $path
     *
     * @return void
     */
    protected function fail($code, $message, $path = null)
    {
        throw new InvalidSceneMixcutException($code, $message, $path);
    }
}
