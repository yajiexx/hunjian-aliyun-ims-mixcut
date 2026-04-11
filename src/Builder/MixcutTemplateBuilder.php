<?php

namespace Hunjian\AliyunImsMixcut\Builder;

use Hunjian\AliyunImsMixcut\Model\Effect\AI_ASR;
use Hunjian\AliyunImsMixcut\Model\OutputMediaConfig;
use Hunjian\AliyunImsMixcut\Model\SubtitleTrack;
use Hunjian\AliyunImsMixcut\Model\VideoTrack;
use Hunjian\AliyunImsMixcut\Model\VideoTrackClip;

/**
 * 混剪模板构建器
 *
 * 可复用模板使用的高级辅助类。
 */
class MixcutTemplateBuilder
{
    /**
     * @var TimelineBuilder
     */
    protected $timelineBuilder;

    /**
     * Create builder.
     *
     * @param TimelineBuilder|null $timelineBuilder
     */
    public function __construct(TimelineBuilder $timelineBuilder = null)
    {
        $this->timelineBuilder = $timelineBuilder ?: TimelineBuilder::make();
    }

    /**
     * 设置竖屏画布。
     *
     * @param int $width
     * @param int $height
     *
     * @return $this
     */
    public function portraitCanvas($width = 1080, $height = 1920)
    {
        $this->timelineBuilder->portrait($width, $height);

        return $this;
    }

    /**
     * Set output config.
     *
     * @param OutputMediaConfig $outputMediaConfig
     *
     * @return $this
     */
    public function output(OutputMediaConfig $outputMediaConfig)
    {
        $this->timelineBuilder->output($outputMediaConfig);

        return $this;
    }

    /**
     * Add a video track.
     *
     * @param array $clips
     * @param bool  $mainTrack
     *
     * @return $this
     */
    public function addVideoClips(array $clips, $mainTrack = false)
    {
        $track = new VideoTrack();
        $track->setMainTrack($mainTrack);

        foreach ($clips as $clip) {
            $track->addClip($clip);
        }

        $this->timelineBuilder->addVideoTrack($track);

        return $this;
    }

    /**
     * 添加字幕。
     *
     * @param SubtitleTrack $track
     *
     * @return $this
     */
    public function addSubtitles(SubtitleTrack $track)
    {
        $this->timelineBuilder->addSubtitleTrack($track);

        return $this;
    }

    /**
     * 添加音频轨道构建器结果。
     *
     * @param AudioBuilder $audioBuilder
     *
     * @return $this
     */
    public function addAudio(AudioBuilder $audioBuilder)
    {
        $this->timelineBuilder->addAudioTrack($audioBuilder->build());

        return $this;
    }

    /**
     * 向片段应用 ASR 效果。
     *
     * @param VideoTrackClip $clip
     * @param string         $language
     * @param array          $params
     *
     * @return $this
     */
    public function attachAsr(VideoTrackClip $clip, $language = 'zh-CN', array $params = array())
    {
        $clip->addEffect(AI_ASR::make($language, $params));

        return $this;
    }

    /**
     * Add background image.
     *
     * @param string $mediaURL
     * @param float  $x
     * @param float  $y
     * @param float  $width
     * @param float  $height
     *
     * @return $this
     */
    public function addGlobalBackground($mediaURL, $x, $y, $width, $height)
    {
        $this->timelineBuilder->withGlobalImage($mediaURL, $x, $y, $width, $height);

        return $this;
    }

    /**
     * Add watermark.
     *
     * @param string $mediaURL
     * @param float  $x
     * @param float  $y
     * @param float  $width
     * @param float  $height
     *
     * @return $this
     */
    public function addWatermark($mediaURL, $x, $y, $width, $height)
    {
        $this->timelineBuilder->withWatermark($mediaURL, $x, $y, $width, $height);

        return $this;
    }

    /**
     * 添加全局滤镜。
     *
     * @param string $subType
     * @param array  $params
     *
     * @return $this
     */
    public function addGlobalFilter($subType, array $params = array())
    {
        $this->timelineBuilder->withGlobalFilter($subType, 0.0, null, $params);

        return $this;
    }

    /**
     * 添加全局视觉特效。
     *
     * @param string $subType
     * @param array  $params
     *
     * @return $this
     */
    public function addGlobalVfx($subType, array $params = array())
    {
        $this->timelineBuilder->withGlobalVfx($subType, 0.0, null, $params);

        return $this;
    }

    /**
     * 构建最终负载。
     *
     * @return array
     */
    public function build()
    {
        return $this->timelineBuilder->build();
    }
}
