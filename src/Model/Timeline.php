<?php

namespace Hunjian\AliyunImsMixcut\Model;

/**
 * Class Timeline
 *
 * Root timeline object aligned with official JSON keys.
 */
class Timeline extends BaseStructure
{
    /**
     * @var array
     */
    protected $videoTracks = array();

    /**
     * @var array
     */
    protected $audioTracks = array();

    /**
     * @var array
     */
    protected $subtitleTracks = array();

    /**
     * @var array
     */
    protected $effectTracks = array();

    /**
     * 添加视频轨道。
     *
     * @param VideoTrack $track
     *
     * @return $this
     */
    public function addVideoTrack(VideoTrack $track)
    {
        $this->videoTracks[] = $track;

        return $this;
    }

    /**
     * 添加音频轨道。
     *
     * @param AudioTrack $track
     *
     * @return $this
     */
    public function addAudioTrack(AudioTrack $track)
    {
        $this->audioTracks[] = $track;

        return $this;
    }

    /**
     * Add a subtitle track.
     *
     * @param SubtitleTrack $track
     *
     * @return $this
     */
    public function addSubtitleTrack(SubtitleTrack $track)
    {
        $this->subtitleTracks[] = $track;

        return $this;
    }

    /**
     * 添加效果轨道。
     *
     * @param EffectTrack $track
     *
     * @return $this
     */
    public function addEffectTrack(EffectTrack $track)
    {
        $this->effectTracks[] = $track;

        return $this;
    }

    /**
     * Set FECanvas used for preview-compatible subtitle sizing.
     *
     * @param int $width
     * @param int $height
     *
     * @return $this
     */
    public function setFECanvas($width, $height)
    {
        return $this->setExtraField('FECanvas', array(
            'Width' => (int) $width,
            'Height' => (int) $height,
        ));
    }

    /**
     * 将对象转换为数组。
     *
     * @return array
     */
    public function toArray()
    {
        return $this->finalize(array(
            'VideoTracks' => $this->videoTracks,
            'AudioTracks' => $this->audioTracks,
            'SubtitleTracks' => $this->subtitleTracks,
            'EffectTracks' => $this->effectTracks,
        ));
    }
}
