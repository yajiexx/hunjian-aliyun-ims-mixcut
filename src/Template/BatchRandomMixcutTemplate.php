<?php

namespace Hunjian\AliyunImsMixcut\Template;

use Hunjian\AliyunImsMixcut\Builder\AudioBuilder;
use Hunjian\AliyunImsMixcut\Builder\MixcutTemplateBuilder;
use Hunjian\AliyunImsMixcut\Builder\Randomizer;
use Hunjian\AliyunImsMixcut\Builder\StrategyBuilder;
use Hunjian\AliyunImsMixcut\Builder\SubtitleBuilder;
use Hunjian\AliyunImsMixcut\Contracts\TemplateInterface;
use Hunjian\AliyunImsMixcut\Model\Effect\Filter;
use Hunjian\AliyunImsMixcut\Model\Effect\KenBurns;
use Hunjian\AliyunImsMixcut\Model\Effect\Transition;
use Hunjian\AliyunImsMixcut\Model\Effect\VFX;
use Hunjian\AliyunImsMixcut\Model\OutputMediaConfig;
use Hunjian\AliyunImsMixcut\Model\ThemeConfig;
use Hunjian\AliyunImsMixcut\Model\VideoTrackClip;

/**
 * Class BatchRandomMixcutTemplate
 *
 * Randomized template generator for one-to-many theme variants.
 */
class BatchRandomMixcutTemplate implements TemplateInterface
{
    /**
     * Build one randomized timeline.
     *
     * @param array $context
     *
     * @return array
     */
    public function build(array $context = array())
    {
        $theme = isset($context['theme']) && $context['theme'] instanceof ThemeConfig ? $context['theme'] : null;
        $randomizer = new Randomizer(isset($context['seed']) ? $context['seed'] : null);
        $strategy = isset($context['strategy']) && $context['strategy'] instanceof StrategyBuilder
            ? $context['strategy']->pick($randomizer)
            : $this->buildDefaultStrategy($theme)->pick($randomizer);

        $builder = new MixcutTemplateBuilder();
        $builder->portraitCanvas(1080, 1920);

        $materials = $this->pickMaterials($context['pool'], $randomizer, isset($context['sceneCount']) ? $context['sceneCount'] : 4);
        $clips = array();
        $subtitles = array();
        $cursor = 0.0;

        foreach ($materials as $index => $material) {
            $duration = isset($material['duration'])
                ? $material['duration']
                : (isset($strategy['clipDuration']) ? $strategy['clipDuration'] : 3.0);

            $clip = !empty($material['type']) && $material['type'] === 'image'
                ? VideoTrackClip::image($material['url'], $duration)
                : VideoTrackClip::fromMediaUrl($material['url'])->setDuration($duration);

            $clip->setClipId('random-scene-' . $index)
                ->setTimelineRange($cursor, $cursor + $duration)
                ->setLayout(0, 0, 1080, 1920, isset($material['adaptMode']) ? $material['adaptMode'] : 'Cover');

            if (!empty($material['type']) && $material['type'] === 'image') {
                $clip->addEffect(KenBurns::make('left', 'center'));
            }

            if (!empty($strategy['transition'])) {
                $clip->addEffect(Transition::make($strategy['transition'], 0.35));
            }

            if (!empty($strategy['filter'])) {
                $clip->addEffect(Filter::make($strategy['filter']));
            }

            if (!empty($strategy['vfx'])) {
                $clip->addEffect(VFX::make($strategy['vfx']));
            }

            $trim = $this->randomTrim($material, $duration, $randomizer);
            if ($trim !== null) {
                $clip->setSourceRange($trim['in'], $trim['out']);
            }

            $clips[] = $clip;
            $subtitles[] = array(
                'text' => isset($material['subtitle']) ? $material['subtitle'] : '主题片段 ' . ($index + 1),
                'start' => $cursor,
                'end' => $cursor + $duration,
                'referenceClipId' => 'random-scene-' . $index,
            );
            $cursor += $duration;
        }

        $builder->addVideoClips($clips, true);

        $audioBuilder = new AudioBuilder();
        if (!empty($strategy['bgm'])) {
            $audioBuilder->addBgm($strategy['bgm'], $cursor, true, -10.0);
        }
        $builder->addAudio($audioBuilder);

        $subtitleBuilder = $this->resolveSubtitleBuilder(isset($strategy['subtitleStyle']) ? $strategy['subtitleStyle'] : array());
        $builder->addSubtitles($subtitleBuilder->buildTrack($subtitles));

        $watermark = !empty($context['watermark']) ? $context['watermark'] : ($theme ? $theme->getWatermark() : null);
        if (!empty($watermark)) {
            $builder->addWatermark($watermark, 860, 100, 150, 58);
        }

        $globalFilter = !empty($context['globalFilter']) ? $context['globalFilter'] : ($theme ? $theme->getGlobalFilter() : null);
        if (!empty($globalFilter)) {
            $builder->addGlobalFilter($globalFilter);
        }

        $globalVfx = !empty($context['globalVfx']) ? $context['globalVfx'] : ($theme ? $theme->getGlobalVfx() : null);
        if (!empty($globalVfx)) {
            $builder->addGlobalVfx($globalVfx);
        }

        $output = isset($context['outputMediaConfig']) && $context['outputMediaConfig'] instanceof OutputMediaConfig
            ? $context['outputMediaConfig']
            : OutputMediaConfig::oss(isset($context['outputMediaURL']) ? $context['outputMediaURL'] : 'oss://demo-bucket/mixcut/batch-random.mp4');
        $output->setSize(1080, 1920);
        $builder->output($output);

        return $builder->build();
    }

    /**
     * Build multiple randomized timelines.
     *
     * @param array $context
     * @param int   $count
     *
     * @return array
     */
    public function buildBatch(array $context, $count)
    {
        $results = array();

        for ($i = 0; $i < $count; $i++) {
            $context['seed'] = isset($context['seed']) ? $context['seed'] + $i : mt_rand(1, 999999);
            $context['outputMediaConfig'] = isset($context['outputMediaConfig']) && $context['outputMediaConfig'] instanceof OutputMediaConfig
                ? clone $context['outputMediaConfig']
                : OutputMediaConfig::oss('oss://demo-bucket/mixcut/batch-random-' . ($i + 1) . '.mp4');
            $results[] = $this->build($context);
        }

        return $results;
    }

    /**
     * Build default strategy pools.
     *
     * @return StrategyBuilder
     */
    protected function buildDefaultStrategy(ThemeConfig $theme = null)
    {
        $builder = (new StrategyBuilder())
            ->transitions(array('fade', 'directional-left', 'directional-up'))
            ->filters(array('warm', 'cool', 'movie'))
            ->vfx(array('shake', 'glow', 'chromatic'))
            ->bgms(array())
            ->subtitleStyles(array(
                array('fontColor' => '#FFFFFF', 'boxColor' => '#000000'),
                array('fontColor' => '#F8E16C', 'boxColor' => '#101820'),
                array('fontColor' => '#FFFFFF', 'boxColor' => '#1B4332'),
            ))
            ->clipDurations(array(2.5, 3.0, 3.5, 4.0))
            ->layouts(array('single'));

        if ($theme !== null) {
            if ($theme->getBgmPool()) {
                $builder->bgms($theme->getBgmPool());
            }

            if ($theme->getSubtitleStyle()) {
                $builder->subtitleStyles(array($theme->getSubtitleStyle()));
            }
        }

        return $builder;
    }

    /**
     * Pick materials from the pool.
     *
     * @param array      $pool
     * @param Randomizer $randomizer
     * @param int        $sceneCount
     *
     * @return array
     */
    protected function pickMaterials(array $pool, Randomizer $randomizer, $sceneCount)
    {
        $all = array_merge(
            isset($pool['videos']) ? $pool['videos'] : array(),
            isset($pool['images']) ? $pool['images'] : array()
        );

        $all = $randomizer->shuffle($all);

        return array_slice($all, 0, $sceneCount);
    }

    /**
     * Build subtitle builder from style config.
     *
     * @param array $style
     *
     * @return SubtitleBuilder
     */
    protected function resolveSubtitleBuilder(array $style)
    {
        $builder = new SubtitleBuilder();
        $builder
            ->style('Alibaba PuHuiTi 2.0', 48, isset($style['fontColor']) ? $style['fontColor'] : '#FFFFFF')
            ->box(isset($style['boxColor']) ? $style['boxColor'] : '#000000', 0.35, 22)
            ->outline('#000000', 4)
            ->layout(90, 1520, 900, 220, 'BottomCenter');

        return $builder;
    }

    /**
     * Build random trim range if the material is a long video.
     *
     * @param array      $material
     * @param float      $duration
     * @param Randomizer $randomizer
     *
     * @return array|null
     */
    protected function randomTrim(array $material, $duration, Randomizer $randomizer)
    {
        if (empty($material['sourceDuration']) || $material['sourceDuration'] <= $duration) {
            return null;
        }

        $start = $randomizer->float(0, $material['sourceDuration'] - $duration);

        return array(
            'in' => round($start, 3),
            'out' => round($start + $duration, 3),
        );
    }
}
