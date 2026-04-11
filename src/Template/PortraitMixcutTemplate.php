<?php

namespace Hunjian\AliyunImsMixcut\Template;

use Hunjian\AliyunImsMixcut\Builder\AudioBuilder;
use Hunjian\AliyunImsMixcut\Builder\MixcutTemplateBuilder;
use Hunjian\AliyunImsMixcut\Builder\SubtitleBuilder;
use Hunjian\AliyunImsMixcut\Contracts\TemplateInterface;
use Hunjian\AliyunImsMixcut\Model\Effect\Background;
use Hunjian\AliyunImsMixcut\Model\Effect\Volume;
use Hunjian\AliyunImsMixcut\Model\OutputMediaConfig;
use Hunjian\AliyunImsMixcut\Model\ThemeConfig;
use Hunjian\AliyunImsMixcut\Model\VideoTrackClip;

/**
 * Class PortraitMixcutTemplate
 *
 * Landscape-to-portrait template with blur background and optional split layout.
 */
class PortraitMixcutTemplate implements TemplateInterface
{
    /**
     * Build portrait mixcut.
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

        $mainVideo = $context['mainVideo'];
        $duration = isset($context['duration']) ? $context['duration'] : null;

        $backgroundClip = VideoTrackClip::fromMediaUrl($mainVideo)
            ->setDuration($duration)
            ->setLayout(0, 0, 1080, 1920, 'Cover')
            ->addEffect(Background::blur(isset($context['blurRadius']) ? $context['blurRadius'] : 25));
        $backgroundClip->addEffect(Volume::gain(-90));

        $foregroundClip = VideoTrackClip::fromMediaUrl($mainVideo)
            ->setClipId('main-video')
            ->setDuration($duration)
            ->setLayout(0, 220, 1080, 1480, 'Contain');

        $builder->addVideoClips(array($backgroundClip), false);

        $layout = isset($context['layout']) ? $context['layout'] : 'single';
        if ($layout === 'three_split') {
            $sources = isset($context['splitVideos']) ? $context['splitVideos'] : array($mainVideo, $mainVideo, $mainVideo);
            $clips = array();
            $positions = array(
                array(0, 220, 360, 1480),
                array(360, 220, 360, 1480),
                array(720, 220, 360, 1480),
            );

            foreach ($positions as $index => $rect) {
                $clips[] = VideoTrackClip::fromMediaUrl(isset($sources[$index]) ? $sources[$index] : $mainVideo)
                    ->setDuration($duration)
                    ->setLayout($rect[0], $rect[1], $rect[2], $rect[3], 'Cover');
            }

            $builder->addVideoClips($clips, true);
        } else {
            $builder->addVideoClips(array($foregroundClip), true);
        }

        $bgm = !empty($context['bgm']) ? $context['bgm'] : ($theme ? $theme->getBgm() : null);
        if (!empty($bgm)) {
            $audioBuilder = new AudioBuilder();
            $audioBuilder->addBgm($bgm, $duration, true, isset($context['bgmGain']) ? $context['bgmGain'] : -12.0);
            $builder->addAudio($audioBuilder);
        }

        if (!empty($context['subtitleSegments'])) {
            $themeSubtitleStyle = $theme ? $theme->getSubtitleStyle() : array();
            $subtitleBuilder = new SubtitleBuilder();
            $subtitleBuilder
                ->style(
                    isset($themeSubtitleStyle['font']) ? $themeSubtitleStyle['font'] : 'Alibaba PuHuiTi 2.0',
                    isset($themeSubtitleStyle['fontSize']) ? $themeSubtitleStyle['fontSize'] : 52,
                    isset($themeSubtitleStyle['fontColor']) ? $themeSubtitleStyle['fontColor'] : '#FFFFFF'
                )
                ->box(isset($themeSubtitleStyle['boxColor']) ? $themeSubtitleStyle['boxColor'] : '#000000', 0.38, 22)
                ->outline('#000000', 4)
                ->shadow('#000000', 3, 3)
                ->layout(80, 1540, 920, 220, 'BottomCenter');
            $builder->addSubtitles($subtitleBuilder->buildTrack($context['subtitleSegments']));
        }

        $watermark = !empty($context['watermark']) ? $context['watermark'] : ($theme ? $theme->getWatermark() : null);
        if (!empty($watermark)) {
            $builder->addWatermark($watermark, 840, 80, 180, 72);
        }

        $globalFilter = !empty($context['globalFilter']) ? $context['globalFilter'] : ($theme ? $theme->getGlobalFilter() : null);
        if (!empty($globalFilter)) {
            $builder->addGlobalFilter($globalFilter);
        }

        $output = isset($context['outputMediaConfig']) && $context['outputMediaConfig'] instanceof OutputMediaConfig
            ? $context['outputMediaConfig']
            : OutputMediaConfig::oss(isset($context['outputMediaURL']) ? $context['outputMediaURL'] : 'oss://demo-bucket/mixcut/portrait.mp4');
        $output->setSize(1080, 1920);
        $builder->output($output);

        return $builder->build();
    }
}
