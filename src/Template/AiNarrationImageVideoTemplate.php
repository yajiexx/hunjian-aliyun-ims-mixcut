<?php

namespace Hunjian\AliyunImsMixcut\Template;

use Hunjian\AliyunImsMixcut\Builder\AudioBuilder;
use Hunjian\AliyunImsMixcut\Builder\MixcutTemplateBuilder;
use Hunjian\AliyunImsMixcut\Builder\SubtitleBuilder;
use Hunjian\AliyunImsMixcut\Contracts\TemplateInterface;
use Hunjian\AliyunImsMixcut\Model\Effect\KenBurns;
use Hunjian\AliyunImsMixcut\Model\Effect\Transition;
use Hunjian\AliyunImsMixcut\Model\OutputMediaConfig;
use Hunjian\AliyunImsMixcut\Model\ThemeConfig;
use Hunjian\AliyunImsMixcut\Model\VideoTrackClip;

/**
 * Class AiNarrationImageVideoTemplate
 *
 * Mixed image/video storytelling template using AI_TTS and AI_ASR.
 */
class AiNarrationImageVideoTemplate implements TemplateInterface
{
    /**
     * Build narration template.
     *
     * @param array $context
     *
     * @return array
     */
    public function build(array $context = array())
    {
        $theme = isset($context['theme']) && $context['theme'] instanceof ThemeConfig ? $context['theme'] : null;
        $builder = new MixcutTemplateBuilder();
        $builder->portraitCanvas(1080, 1920);

        $clips = array();
        $cursor = 0.0;

        foreach ($context['materials'] as $index => $material) {
            $duration = isset($material['duration']) ? $material['duration'] : 3.5;
            $clip = !empty($material['type']) && $material['type'] === 'image'
                ? VideoTrackClip::image($material['url'], $duration)
                : VideoTrackClip::fromMediaUrl($material['url'])->setDuration($duration);

            $clip->setClipId('scene-' . $index)
                ->setTimelineRange($cursor, $cursor + $duration)
                ->setLayout(0, 0, 1080, 1920, 'Cover');

            if (!empty($material['type']) && $material['type'] === 'image') {
                $clip->addEffect(KenBurns::make('center', 'right'));
            }

            if (!empty($material['transition'])) {
                $clip->addEffect(Transition::make($material['transition'], 0.4));
            }

            $clips[] = $clip;
            $cursor += $duration;
        }

        $builder->addVideoClips($clips, true);

        $audioBuilder = new AudioBuilder();
        $asrParams = array(
            'ReferenceClipId' => isset($context['referenceClipId']) ? $context['referenceClipId'] : 'scene-0',
        );
        $highlight = !empty($context['highlight']) ? $context['highlight'] : ($theme ? $theme->getHighlight() : array());
        if (!empty($highlight)) {
            $asrParams['HighlightConfig'] = $highlight;
        }

        $audioBuilder->addAiNarration(
            isset($context['narrationText']) ? $context['narrationText'] : '',
            isset($context['voice']) ? $context['voice'] : ($theme && $theme->getVoice() ? $theme->getVoice() : 'zhitian_emo'),
            0.0,
            $cursor,
            array(
                'speechRate' => isset($context['speechRate']) ? $context['speechRate'] : 0,
                'pitchRate' => isset($context['pitchRate']) ? $context['pitchRate'] : 0,
                'ssml' => isset($context['ssml']) ? $context['ssml'] : null,
                'asr' => $asrParams,
                'language' => isset($context['language']) ? $context['language'] : 'zh-CN',
            )
        );

        $bgm = !empty($context['bgm']) ? $context['bgm'] : ($theme ? $theme->getBgm() : null);
        if (!empty($bgm)) {
            $audioBuilder->addBgm($bgm, $cursor, true, -14.0);
        }

        $builder->addAudio($audioBuilder);

        if (!empty($context['manualSubtitleSegments'])) {
            $themeSubtitleStyle = $theme ? $theme->getSubtitleStyle() : array();
            $subtitleBuilder = new SubtitleBuilder();
            $subtitleBuilder
                ->style(
                    isset($themeSubtitleStyle['font']) ? $themeSubtitleStyle['font'] : 'Alibaba PuHuiTi 2.0',
                    isset($themeSubtitleStyle['fontSize']) ? $themeSubtitleStyle['fontSize'] : 50,
                    isset($themeSubtitleStyle['fontColor']) ? $themeSubtitleStyle['fontColor'] : '#FFFFFF'
                )
                ->box(isset($themeSubtitleStyle['boxColor']) ? $themeSubtitleStyle['boxColor'] : '#101820', 0.42, 24)
                ->outline('#111111', 4)
                ->layout(80, 1500, 920, 250, 'BottomCenter')
                ->extra(array(
                    'HighlightConfig' => $highlight,
                ));
            $builder->addSubtitles($subtitleBuilder->buildTrack($context['manualSubtitleSegments']));
        }

        $watermark = !empty($context['watermark']) ? $context['watermark'] : ($theme ? $theme->getWatermark() : null);
        if (!empty($watermark)) {
            $builder->addWatermark($watermark, 850, 90, 160, 64);
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
            : OutputMediaConfig::oss(isset($context['outputMediaURL']) ? $context['outputMediaURL'] : 'oss://demo-bucket/mixcut/ai-narration.mp4');
        $output->setSize(1080, 1920);
        $builder->output($output);

        return $builder->build();
    }
}
