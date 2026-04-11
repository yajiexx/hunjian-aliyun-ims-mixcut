<?php

namespace Hunjian\AliyunImsMixcut\Model;

/**
 * 音频轨道类
 *
 * 官方音频轨道结构。
 */
class AudioTrack extends BaseStructure
{
    /**
     * @var array
     */
    protected $clips = array();

    /**
     * 添加音频片段。
     *
     * @param AudioTrackClip $clip
     *
     * @return $this
     */
    public function addClip(AudioTrackClip $clip)
    {
        $this->clips[] = $clip;

        return $this;
    }

    /**
     * 将对象转换为数组。
     *
     * @return array
     */
    public function toArray()
    {
        return $this->finalize(array(
            'AudioTrackClips' => $this->clips,
        ));
    }
}
