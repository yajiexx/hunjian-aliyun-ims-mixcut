<?php

namespace Hunjian\AliyunImsMixcut\Scene;

/**
 * 从规范化的场景负载中解析最终场景时长。
 */
class SceneDurationResolver
{
    /**
     * @var float
     */
    protected $defaultSceneDuration;

    /**
     * @param float $defaultSceneDuration
     */
    public function __construct($defaultSceneDuration = 3.0)
    {
        $this->defaultSceneDuration = (float) $defaultSceneDuration;
    }

    /**
     * 解析场景时长。
     *
     * @param array $context
     *
     * @return array
     */
    public function resolve(array $context)
    {
        $totalDuration = 0.0;

        foreach ($context['scenes'] as $index => $scene) {
            $resolvedDuration = $this->resolveSceneDuration($scene);
            $context['scenes'][$index]['resolvedDuration'] = $resolvedDuration;
            $totalDuration += $resolvedDuration;
        }

        $context['totalDuration'] = $totalDuration;

        return $context;
    }

    /**
     * 解析单个场景时长。
     *
     * @param array $scene
     *
     * @return float
     */
    protected function resolveSceneDuration(array $scene)
    {
        if (isset($scene['sceneDuration']) && $scene['sceneDuration'] !== null) {
            return (float) $scene['sceneDuration'];
        }

        if (!empty($scene['dubbing']) && isset($scene['dubbing']['duration']) && $scene['dubbing']['duration'] !== null) {
            return (float) $scene['dubbing']['duration'];
        }

        $materialDuration = 0.0;
        foreach ($scene['materials'] as $material) {
            if (isset($material['duration']) && $material['duration'] !== null) {
                $materialDuration += (float) $material['duration'];
                continue;
            }

            if (!empty($material['sceneRange'])) {
                $materialDuration += (float) ($material['sceneRange']['end'] - $material['sceneRange']['start']);
            }
        }

        if ($materialDuration > 0.0) {
            return $materialDuration;
        }

        return $this->defaultSceneDuration;
    }
}
