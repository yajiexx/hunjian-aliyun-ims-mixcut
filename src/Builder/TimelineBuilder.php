<?php

namespace Hunjian\AliyunImsMixcut\Builder;

use Hunjian\AliyunImsMixcut\Model\AudioTrack;
use Hunjian\AliyunImsMixcut\Model\EffectTrack;
use Hunjian\AliyunImsMixcut\Model\OutputMediaConfig;
use Hunjian\AliyunImsMixcut\Model\SubtitleTrack;
use Hunjian\AliyunImsMixcut\Model\Timeline;
use Hunjian\AliyunImsMixcut\Model\VideoTrack;

/**
 * 时光机构建器
 *
 * 围绕时间线模型对象的流式组合门面。
 */
class TimelineBuilder
{
    /**
     * @var Timeline
     */
    protected $timeline;

    /**
     * @var OutputMediaConfig
     */
    protected $outputMediaConfig;

    /**
     * Create builder.
     */
    public function __construct()
    {
        $this->timeline = new Timeline();
        $this->outputMediaConfig = new OutputMediaConfig();
    }

    /**
     * Create a builder instance.
     *
     * @return self
     */
    public static function make()
    {
        return new self();
    }

    /**
     * Set portrait canvas and output size.
     *
     * @param int $width
     * @param int $height
     *
     * @return $this
     */
    public function portrait($width = 1080, $height = 1920)
    {
        $this->timeline->setFECanvas($width, $height);
        $this->outputMediaConfig->setSize($width, $height);

        return $this;
    }

    /**
     * 设置自定义画布尺寸。
     *
     * @param int $width
     * @param int $height
     *
     * @return $this
     */
    public function canvas($width, $height)
    {
        return $this->portrait($width, $height);
    }

    /**
     * 设置输出配置。
     *
     * @param OutputMediaConfig $outputMediaConfig
     *
     * @return $this
     */
    public function output(OutputMediaConfig $outputMediaConfig)
    {
        $this->outputMediaConfig = $outputMediaConfig;

        return $this;
    }

    /**
     * 添加视频轨道。
     *
     * @param VideoTrack $track
     *
     * @return $this
     */
    public function addVideoTrack(VideoTrack $track)
    {
        $this->timeline->addVideoTrack($track);

        return $this;
    }

    /**
     * Add audio track.
     *
     * @param AudioTrack $track
     *
     * @return $this
     */
    public function addAudioTrack(AudioTrack $track)
    {
        $this->timeline->addAudioTrack($track);

        return $this;
    }

    /**
     * 添加字幕轨道。
     *
     * @param SubtitleTrack $track
     *
     * @return $this
     */
    public function addSubtitleTrack(SubtitleTrack $track)
    {
        $this->timeline->addSubtitleTrack($track);

        return $this;
    }

    /**
     * Add effect track.
     *
     * @param EffectTrack $track
     *
     * @return $this
     */
    public function addEffectTrack(EffectTrack $track)
    {
        $this->timeline->addEffectTrack($track);

        return $this;
    }

    /**
     * Add global background image.
     *
     * @param string     $mediaURL
     * @param float      $x
     * @param float      $y
     * @param float      $width
     * @param float      $height
     * @param float|null $timelineIn
     * @param float|null $timelineOut
     *
     * @return $this
     */
    public function withGlobalImage($mediaURL, $x, $y, $width, $height, $timelineIn = 0.0, $timelineOut = null)
    {
        $builder = new EffectTrackBuilder();
        $builder->addGlobalImage($mediaURL, $x, $y, $width, $height, $timelineIn, $timelineOut, array(
            'Role' => 'Background',
        ));
        $this->timeline->addEffectTrack($builder->build());

        return $this;
    }

    /**
     * 添加全局水印。
     *
     * @param string     $mediaURL
     * @param float      $x
     * @param float      $y
     * @param float      $width
     * @param float      $height
     * @param float|null $timelineIn
     * @param float|null $timelineOut
     *
     * @return $this
     */
    public function withWatermark($mediaURL, $x, $y, $width, $height, $timelineIn = 0.0, $timelineOut = null)
    {
        $builder = new EffectTrackBuilder();
        $builder->addWatermark($mediaURL, $x, $y, $width, $height, $timelineIn, $timelineOut, array(
            'Role' => 'Watermark',
        ));
        $this->timeline->addEffectTrack($builder->build());

        return $this;
    }

    /**
     * 添加全局滤镜。
     *
     * @param string     $subType
     * @param float|null $timelineIn
     * @param float|null $timelineOut
     * @param array      $params
     *
     * @return $this
     */
    public function withGlobalFilter($subType, $timelineIn = 0.0, $timelineOut = null, array $params = array())
    {
        $builder = new EffectTrackBuilder();
        $builder->addFilter($subType, $timelineIn, $timelineOut, $params);
        $this->timeline->addEffectTrack($builder->build());

        return $this;
    }

    /**
     * Add global VFX.
     *
     * @param string     $subType
     * @param float|null $timelineIn
     * @param float|null $timelineOut
     * @param array      $params
     *
     * @return $this
     */
    public function withGlobalVfx($subType, $timelineIn = 0.0, $timelineOut = null, array $params = array())
    {
        $builder = new EffectTrackBuilder();
        $builder->addVfx($subType, $timelineIn, $timelineOut, $params);
        $this->timeline->addEffectTrack($builder->build());

        return $this;
    }

    /**
     * Add raw root-level fields to timeline.
     *
     * @param array $fields
     *
     * @return $this
     */
    public function extra(array $fields)
    {
        $this->timeline->withExtraFields($fields);

        return $this;
    }

    /**
     * Get timeline object.
     *
     * @return Timeline
     */
    public function buildTimeline()
    {
        return $this->timeline;
    }

    /**
     * 获取输出配置。
     *
     * @return OutputMediaConfig
     */
    public function buildOutputMediaConfig()
    {
        return $this->outputMediaConfig;
    }

    /**
     * 构建结果负载。
     *
     * @return array
     */
    public function build()
    {
        return array(
            'timeline' => $this->timeline,
            'outputMediaConfig' => $this->outputMediaConfig,
        );
    }
}
