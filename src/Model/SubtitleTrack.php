<?php

namespace Hunjian\AliyunImsMixcut\Model;

/**
 * 字幕轨道类
 *
 * 官方字幕轨道结构。
 */
class SubtitleTrack extends BaseStructure
{
    /**
     * @var array
     */
    protected $clips = array();

    /**
     * 添加字幕片段。
     *
     * @param SubtitleTrackClip $clip
     *
     * @return $this
     */
    public function addClip(SubtitleTrackClip $clip)
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
            'SubtitleTrackClips' => $this->clips,
        ));
    }
}
