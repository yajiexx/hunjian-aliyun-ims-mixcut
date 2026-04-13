<?php

namespace Hunjian\AliyunImsMixcut\Editor;

use Hunjian\AliyunImsMixcut\Builder\EffectTrackBuilder;
use Hunjian\AliyunImsMixcut\Builder\TimelineBuilder;
use Hunjian\AliyunImsMixcut\Model\AudioTrack;
use Hunjian\AliyunImsMixcut\Model\AudioTrackClip;
use Hunjian\AliyunImsMixcut\Model\Effect\Transition;
use Hunjian\AliyunImsMixcut\Model\Effect\VFX;
use Hunjian\AliyunImsMixcut\Model\Effect\Volume;
use Hunjian\AliyunImsMixcut\Model\EffectTrackItem;
use Hunjian\AliyunImsMixcut\Model\SubtitleEffect;
use Hunjian\AliyunImsMixcut\Model\SubtitleTrack;
use Hunjian\AliyunImsMixcut\Model\SubtitleTrackClip;
use Hunjian\AliyunImsMixcut\Model\VideoTrack;
use Hunjian\AliyunImsMixcut\Model\VideoTrackClip;

/**
 * Compile normalized editor projects into IMS timelines.
 */
class EditorProjectCompiler
{
    /**
     * Compile one normalized editor project.
     *
     * @param array $project
     *
     * @return array
     */
    public function compile(array $project)
    {
        $timelineBuilder = TimelineBuilder::make()->canvas(
            $project['canvas']['width'],
            $project['canvas']['height']
        );

        $mainTrackAssigned = false;
        foreach ($project['sequence']['layers'] as $layer) {
            if ($layer['type'] === 'background') {
                $this->compileBackgroundLayer($timelineBuilder, $layer, $project['sequence']['duration']);
                continue;
            }

            if ($layer['type'] === 'video' || $layer['type'] === 'element') {
                $track = $this->compileVisualLayer($layer, !$mainTrackAssigned && $layer['type'] === 'video');
                if ($track !== null) {
                    $timelineBuilder->addVideoTrack($track);
                    if ($layer['type'] === 'video' && !$mainTrackAssigned) {
                        $mainTrackAssigned = true;
                    }
                }
                continue;
            }

            if ($layer['type'] === 'audio') {
                foreach ($this->compileAudioLayer($layer) as $track) {
                    $timelineBuilder->addAudioTrack($track);
                }
                continue;
            }

            if ($layer['type'] === 'text') {
                $track = $this->compileTextLayer($layer);
                if ($track !== null) {
                    $timelineBuilder->addSubtitleTrack($track);
                }
            }
        }

        return array(
            'timeline' => $timelineBuilder->buildTimeline(),
            'totalDuration' => $project['sequence']['duration'],
        );
    }

    /**
     * Compile a background layer.
     *
     * @param TimelineBuilder $timelineBuilder
     * @param array $layer
     * @param float $totalDuration
     *
     * @return void
     */
    protected function compileBackgroundLayer(TimelineBuilder $timelineBuilder, array $layer, $totalDuration)
    {
        foreach ($layer['items'] as $item) {
            $timelineOut = $item['start'] + $item['duration'];
            if ($item['type'] === 'color') {
                $builder = new EffectTrackBuilder();
                $effect = new EffectTrackItem('Background', 'Color', array(
                    'Color' => $item['color'],
                ));
                $effect->setRange($item['start'], $timelineOut > 0.0 ? $timelineOut : $totalDuration);
                $builder->addItem($effect);
                $timelineBuilder->addEffectTrack($builder->build());
                continue;
            }

            $timelineBuilder->withGlobalImage(
                $item['url'],
                $item['layout']['x'],
                $item['layout']['y'],
                $item['layout']['width'],
                $item['layout']['height'],
                $item['start'],
                $timelineOut
            );
        }
    }

    /**
     * Compile a video or element layer.
     *
     * @param array $layer
     * @param bool $mainTrack
     *
     * @return VideoTrack|null
     */
    protected function compileVisualLayer(array $layer, $mainTrack)
    {
        $track = (new VideoTrack())
            ->setMainTrack($mainTrack)
            ->setTrackShortenMode('ShortenFromEnd');

        foreach ($layer['items'] as $item) {
            $timelineOut = $item['start'] + $item['duration'];
            $clip = $item['type'] === 'image'
                ? VideoTrackClip::image($item['url'], $item['duration'])
                : VideoTrackClip::fromMediaUrl($item['url'])->setDuration($item['duration']);

            $clip->setClipId($item['clipId'])
                ->setTimelineRange($item['start'], $timelineOut)
                ->setLayout(
                    $item['layout']['x'],
                    $item['layout']['y'],
                    $item['layout']['width'],
                    $item['layout']['height'],
                    $item['layout']['adaptMode']
                )
                ->withExtraFields($item['raw']);

            if ($item['zIndex'] !== null) {
                $clip->setZOrder($item['zIndex']);
            }

            if ($item['sourceRange'] !== null) {
                $clip->setSourceRange($item['sourceRange']['in'], $item['sourceRange']['out']);
            }

            if ($item['transition'] !== null) {
                $clip->addEffect(Transition::make($item['transition']['type'], $item['transition']['duration']));
            }

            foreach ($item['animations'] as $animation) {
                $clip->addEffect(VFX::make($animation['preset'], array(
                    'Phase' => $animation['phase'],
                    'Duration' => $animation['duration'],
                )));
            }

            $track->addClip($clip);
        }

        return !empty($track->toArray()['VideoTrackClips']) ? $track : null;
    }

    /**
     * Compile an audio layer into one track per item.
     *
     * @param array $layer
     *
     * @return array
     */
    protected function compileAudioLayer(array $layer)
    {
        $tracks = array();

        foreach ($layer['items'] as $item) {
            $track = new AudioTrack();
            $clip = AudioTrackClip::fromMediaUrl($item['url'])
                ->setDuration($item['duration'])
                ->setTimelineRange($item['start'], $item['start'] + $item['duration']);

            if ($item['loop']) {
                $clip->setLoopMode('Loop');
            }

            if ($item['volume'] !== null) {
                $clip->addEffect(Volume::gain($item['volume']));
            }

            $track->addClip($clip);
            $tracks[] = $track;
        }

        return $tracks;
    }

    /**
     * Compile a text layer.
     *
     * @param array $layer
     *
     * @return SubtitleTrack|null
     */
    protected function compileTextLayer(array $layer)
    {
        $track = new SubtitleTrack();

        foreach ($layer['items'] as $item) {
            $timelineOut = $item['start'] + $item['duration'];
            $clip = SubtitleTrackClip::text($item['text'], $item['start'], $timelineOut)
                ->setClipId($item['clipId'])
                ->setStyle($item['style']['font'], $item['style']['fontSize'], $item['style']['fontColor'])
                ->setLayout(
                    $item['layout']['x'],
                    $item['layout']['y'],
                    $item['layout']['width'],
                    $item['layout']['height'],
                    $item['layout']['alignment']
                )
                ->setAutoWrap($item['style']['autoWrap'])
                ->setFixedFontSize($item['style']['fixedFontSize'])
                ->withExtraFields($item['raw']);

            foreach ($item['animations'] as $animation) {
                $clip->addSubtitleEffect(new SubtitleEffect('Animation', array(
                    'Phase' => $animation['phase'],
                    'Preset' => $animation['preset'],
                    'Duration' => $animation['duration'],
                )));
            }

            if (!empty($item['style']['boxColor'])) {
                $clip->addSubtitleEffect(SubtitleEffect::box(
                    $item['style']['boxColor'],
                    isset($item['style']['boxOpacity']) ? $item['style']['boxOpacity'] : 0.35,
                    isset($item['style']['boxBord']) ? $item['style']['boxBord'] : 20
                ));
            }

            $track->addClip($clip);
        }

        return !empty($track->toArray()['SubtitleTrackClips']) ? $track : null;
    }
}
