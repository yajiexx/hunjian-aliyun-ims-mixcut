<?php

namespace Hunjian\AliyunImsMixcut\Scene;

use Hunjian\AliyunImsMixcut\Builder\AudioBuilder;
use Hunjian\AliyunImsMixcut\Builder\TimelineBuilder;
use Hunjian\AliyunImsMixcut\Exception\InvalidSceneMixcutException;
use Hunjian\AliyunImsMixcut\Model\AudioTrack;
use Hunjian\AliyunImsMixcut\Model\AudioTrackClip;
use Hunjian\AliyunImsMixcut\Model\Effect\AEqualize;
use Hunjian\AliyunImsMixcut\Model\Effect\ADenoise;
use Hunjian\AliyunImsMixcut\Model\Effect\AFade;
use Hunjian\AliyunImsMixcut\Model\Effect\AI_TTS;
use Hunjian\AliyunImsMixcut\Model\Effect\ALoudNorm;
use Hunjian\AliyunImsMixcut\Model\Effect\Transition;
use Hunjian\AliyunImsMixcut\Model\SubtitleEffect;
use Hunjian\AliyunImsMixcut\Model\SubtitleTrack;
use Hunjian\AliyunImsMixcut\Model\SubtitleTrackClip;
use Hunjian\AliyunImsMixcut\Model\VideoTrack;
use Hunjian\AliyunImsMixcut\Model\VideoTrackClip;

/**
 * Convert normalized scene payloads into IMS timeline objects.
 */
class SceneTimelineAssembler
{
    /**
     * Assemble timeline from normalized, duration-resolved scene payload.
     *
     * @param array $context
     *
     * @return array
     */
    public function assemble(array $context)
    {
        $timelineBuilder = TimelineBuilder::make()->canvas(
            $context['canvas']['width'],
            $context['canvas']['height']
        );

        $videoTrack = (new VideoTrack())
            ->setMainTrack(true)
            ->setTrackShortenMode('ShortenFromEnd');
        $narrationTrack = new AudioTrack();
        $subtitleTrack = new SubtitleTrack();
        $wordArtTrack = new SubtitleTrack();

        $cursor = 0.0;
        foreach ($context['scenes'] as $sceneIndex => $scene) {
            $sceneStart = $cursor;
            $sceneEnd = $sceneStart + $scene['resolvedDuration'];
            $placements = $this->resolveMaterialPlacements($scene, $sceneIndex, $sceneStart);
            $referenceMap = array();

            foreach ($placements as $materialIndex => $placement) {
                $material = $placement['material'];
                $clipId = 'scene-' . ($sceneIndex + 1) . '-material-' . ($materialIndex + 1);

                $clip = $material['type'] === 'image'
                    ? VideoTrackClip::image($material['url'], $placement['duration'])
                    : VideoTrackClip::fromMediaUrl($material['url'])->setDuration($placement['duration']);

                $clip->setClipId($clipId)
                    ->setTimelineRange($placement['start'], $placement['end'])
                    ->setLayout(
                        $material['layout']['x'],
                        $material['layout']['y'],
                        $material['layout']['width'],
                        $material['layout']['height'],
                        isset($material['layout']['adaptMode']) ? $material['layout']['adaptMode'] : 'Cover'
                    )
                    ->withExtraFields($material['raw']);

                if (isset($material['zOrder']) && $material['zOrder'] !== null) {
                    $clip->setZOrder($material['zOrder']);
                }

                if (!empty($material['sourceRange'])) {
                    $clip->setSourceRange($material['sourceRange']['in'], $material['sourceRange']['out']);
                }

                $transition = $this->resolveClipTransition($context, $scene, $sceneIndex, $materialIndex, $material);
                if ($transition !== null) {
                    if ($transition['duration'] > $placement['duration']) {
                        $this->fail(
                            'INVALID_SCENE_TRANSITION',
                            'Transition duration must not exceed clip duration.',
                            'scenes[' . $sceneIndex . '].materials[' . $materialIndex . '].transition.duration'
                        );
                    }

                    $clip->addEffect(Transition::make($transition['type'], $transition['duration']));
                }

                $videoTrack->addClip($clip);
                $referenceMap[$material['materialId']] = array(
                    'clipId' => $clipId,
                    'start' => $placement['start'],
                    'end' => $placement['end'],
                );
            }

            foreach ($scene['subtitles'] as $subtitleIndex => $item) {
                $subtitleTrack->addClip($this->buildSubtitleClip(
                    $item,
                    $sceneIndex,
                    $subtitleIndex,
                    $sceneStart,
                    $sceneEnd,
                    $referenceMap,
                    false
                ));
            }

            foreach ($scene['wordArts'] as $wordArtIndex => $item) {
                $wordArtTrack->addClip($this->buildSubtitleClip(
                    $item,
                    $sceneIndex,
                    $wordArtIndex,
                    $sceneStart,
                    $sceneEnd,
                    $referenceMap,
                    true
                ));
            }

            $this->appendNarrationClip($narrationTrack, $scene, $sceneStart, $sceneEnd);
            $cursor = $sceneEnd;
        }

        $timelineBuilder->addVideoTrack($videoTrack);
        if (!empty($narrationTrack->toArray()['AudioTrackClips'])) {
            $timelineBuilder->addAudioTrack($narrationTrack);
        }
        if (!empty($subtitleTrack->toArray()['SubtitleTrackClips'])) {
            $timelineBuilder->addSubtitleTrack($subtitleTrack);
        }
        if (!empty($wordArtTrack->toArray()['SubtitleTrackClips'])) {
            $timelineBuilder->addSubtitleTrack($wordArtTrack);
        }

        if (!empty($context['global']['bgm'])) {
            $audioBuilder = new AudioBuilder();
            $audioBuilder->addBgm($context['global']['bgm'], $cursor, true, -10.0);
            $timelineBuilder->addAudioTrack($audioBuilder->build());
        }

        if (!empty($context['global']['watermark'])) {
            $timelineBuilder->withWatermark($context['global']['watermark'], 860, 100, 150, 58, 0.0, $cursor);
        }

        return array(
            'timeline' => $timelineBuilder->buildTimeline(),
            'totalDuration' => $cursor,
        );
    }

    /**
     * Resolve material placements for one scene.
     *
     * @param array $scene
     * @param int   $sceneIndex
     * @param float $sceneStart
     *
     * @return array
     */
    protected function resolveMaterialPlacements(array $scene, $sceneIndex, $sceneStart)
    {
        $placements = array();
        $sceneDuration = $scene['resolvedDuration'];
        $manual = false;

        foreach ($scene['materials'] as $material) {
            if (!empty($material['sceneRange'])) {
                $manual = true;
                break;
            }
        }

        if ($manual) {
            foreach ($scene['materials'] as $materialIndex => $material) {
                if (empty($material['sceneRange'])) {
                    $this->fail(
                        'INVALID_SCENE_MATERIAL_RANGE',
                        'All materials must define sceneRange when manual scene timing is used.',
                        'scenes[' . $sceneIndex . '].materials[' . $materialIndex . '].sceneRange'
                    );
                }

                if ($material['sceneRange']['end'] > $sceneDuration) {
                    $this->fail(
                        'INVALID_SCENE_ITEM_RANGE',
                        'Material timing must stay inside resolved scene duration.',
                        'scenes[' . $sceneIndex . '].materials[' . $materialIndex . '].sceneRange.end'
                    );
                }

                $placements[] = array(
                    'material' => $material,
                    'start' => $sceneStart + $material['sceneRange']['start'],
                    'end' => $sceneStart + $material['sceneRange']['end'],
                    'duration' => $material['sceneRange']['end'] - $material['sceneRange']['start'],
                );
            }
            return $placements;
        }

        $placementCursor = $sceneStart;
        $remainingDuration = $sceneDuration;
        $remainingCount = count($scene['materials']);
        foreach ($scene['materials'] as $material) {
            $duration = isset($material['duration']) && $material['duration'] !== null
                ? (float) $material['duration']
                : ($remainingCount > 0 ? $remainingDuration / $remainingCount : 0.0);

            $placements[] = array(
                'material' => $material,
                'start' => $placementCursor,
                'end' => $placementCursor + $duration,
                'duration' => $duration,
            );

            $placementCursor += $duration;
            $remainingDuration -= $duration;
            $remainingCount--;
        }

        if ($placementCursor > ($sceneStart + $sceneDuration + 0.00001)) {
            $this->fail(
                'INVALID_SCENE_ITEM_RANGE',
                'Sequential material placement must stay inside resolved scene duration.',
                'scenes[' . $sceneIndex . '].materials'
            );
        }

        return $placements;
    }

    /**
     * Build one subtitle or word art clip.
     *
     * @param array $item
     * @param int   $sceneIndex
     * @param int   $itemIndex
     * @param float $sceneStart
     * @param float $sceneEnd
     * @param array $referenceMap
     * @param bool  $wordArt
     *
     * @return SubtitleTrackClip
     */
    protected function buildSubtitleClip(array $item, $sceneIndex, $itemIndex, $sceneStart, $sceneEnd, array $referenceMap, $wordArt)
    {
        $absoluteStart = $sceneStart + $item['start'];
        $absoluteEnd = $sceneStart + $item['end'];
        $basePath = 'scenes[' . $sceneIndex . '].' . ($wordArt ? 'wordArts' : 'subtitles') . '[' . $itemIndex . ']';

        if ($absoluteEnd > $sceneEnd + 0.00001) {
            $this->fail(
                'INVALID_SCENE_ITEM_RANGE',
                'Scene item timing must stay inside resolved scene duration.',
                $basePath . '.end'
            );
        }

        $clip = SubtitleTrackClip::text($item['text'], $absoluteStart, $absoluteEnd)
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

        if (!empty($item['preset'])) {
            $clip->setExtraField('Preset', $item['preset']);
        }

        if (!empty($item['referenceMaterialId'])) {
            $reference = $referenceMap[$item['referenceMaterialId']];
            if ($absoluteStart < $reference['start'] || $absoluteEnd > $reference['end']) {
                $this->fail(
                    'INVALID_SCENE_REFERENCE_RANGE',
                    'Referenced material must overlap the subtitle or word art timing.',
                    $basePath . '.end'
                );
            }

            $clip->setReferenceClipId($reference['clipId']);
        }

        foreach ($this->buildSubtitleEffects($item['style']) as $effect) {
            $clip->addSubtitleEffect($effect);
        }

        return $clip;
    }

    /**
     * Add one narration clip if needed.
     *
     * @param AudioTrack $track
     * @param array      $scene
     * @param float      $sceneStart
     * @param float      $sceneEnd
     *
     * @return void
     */
    protected function appendNarrationClip(AudioTrack $track, array $scene, $sceneStart, $sceneEnd)
    {
        if (empty($scene['dubbing'])) {
            return;
        }

        $dubbing = $scene['dubbing'];
        if (!empty($dubbing['audioUrl'])) {
            $track->addClip(
                AudioTrackClip::fromMediaUrl($dubbing['audioUrl'])
                    ->setDuration($sceneEnd - $sceneStart)
                    ->setTimelineRange($sceneStart, $sceneEnd)
            );

            return;
        }

        $tts = AI_TTS::fromText($dubbing['text'], $dubbing['voice'])
            ->setSpeechRate(isset($dubbing['speechRate']) ? $dubbing['speechRate'] : 0)
            ->setPitchRate(isset($dubbing['pitchRate']) ? $dubbing['pitchRate'] : 0)
            ->setVolume(50);

        if (!empty($dubbing['ssml'])) {
            $tts->setSsml($dubbing['ssml']);
        }

        $track->addClip(
            AudioTrackClip::fromTts($tts)
                ->setTimelineRange($sceneStart, $sceneEnd)
                ->addEffect(AFade::make('In', 0.3))
                ->addEffect(AFade::make('Out', 0.4))
                ->addEffect(ADenoise::make('off'))
                ->addEffect(ALoudNorm::make())
                ->addEffect(AEqualize::make(1200, 200, 2.0))
        );
    }

    /**
     * Build subtitle effects from style array.
     *
     * @param array $style
     *
     * @return array
     */
    protected function buildSubtitleEffects(array $style)
    {
        $effects = array();

        if (isset($style['boxColor']) && $style['boxColor'] !== null) {
            $effects[] = SubtitleEffect::box(
                $style['boxColor'],
                isset($style['boxOpacity']) ? $style['boxOpacity'] : 0.35,
                isset($style['boxBord']) ? $style['boxBord'] : 22
            );
        }

        if (!empty($style['outlineColor']) && !empty($style['outlineBord'])) {
            $effects[] = new SubtitleEffect('Outline', array(
                'Color' => $style['outlineColor'],
                'Bord' => $style['outlineBord'],
            ));
        }

        if (!empty($style['shadowColor'])) {
            $effects[] = new SubtitleEffect('Shadow', array(
                'Color' => $style['shadowColor'],
                'OffsetX' => isset($style['shadowOffsetX']) ? $style['shadowOffsetX'] : 0,
                'OffsetY' => isset($style['shadowOffsetY']) ? $style['shadowOffsetY'] : 0,
            ));
        }

        return $effects;
    }

    /**
     * Resolve transition precedence for one material clip.
     *
     * @param array $context
     * @param array $scene
     * @param int   $sceneIndex
     * @param int   $materialIndex
     * @param array $material
     *
     * @return array|null
     */
    protected function resolveClipTransition(array $context, array $scene, $sceneIndex, $materialIndex, array $material)
    {
        if (!empty($material['transition'])) {
            return $material['transition'];
        }

        if ($materialIndex === 0 && $sceneIndex > 0 && !empty($scene['transition'])) {
            return $scene['transition'];
        }

        if ($materialIndex === 0 && $sceneIndex > 0 && !empty($context['global']['sceneTransition'])) {
            return $context['global']['sceneTransition'];
        }

        return null;
    }
    /**
     * Throw a scene mixcut exception.
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
