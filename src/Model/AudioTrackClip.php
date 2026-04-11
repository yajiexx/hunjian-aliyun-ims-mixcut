<?php

namespace Hunjian\AliyunImsMixcut\Model;

use Hunjian\AliyunImsMixcut\Model\Effect\AI_TTS;
use Hunjian\AliyunImsMixcut\Model\Effect\Effect;

/**
 * Class AudioTrackClip
 *
 * Official audio clip structure.
 */
class AudioTrackClip extends BaseStructure
{
    /**
     * @var string|null
     */
    protected $type = 'Audio';

    /**
     * @var string|null
     */
    protected $mediaURL;

    /**
     * @var string|null
     */
    protected $mediaId;

    /**
     * @var float|null
     */
    protected $in;

    /**
     * @var float|null
     */
    protected $out;

    /**
     * @var float|null
     */
    protected $duration;

    /**
     * @var float|null
     */
    protected $timelineIn;

    /**
     * @var float|null
     */
    protected $timelineOut;

    /**
     * @var string|null
     */
    protected $loopMode;

    /**
     * @var array
     */
    protected $effects = array();

    /**
     * @var AI_TTS|null
     */
    protected $aiTts;

    /**
     * Create audio clip from media URL.
     *
     * @param string $mediaURL
     *
     * @return self
     */
    public static function fromMediaUrl($mediaURL)
    {
        $clip = new self();
        $clip->setMediaURL($mediaURL);

        return $clip;
    }

    /**
     * Create audio clip from AI TTS payload.
     *
     * @param AI_TTS $tts
     *
     * @return self
     */
    public static function fromTts(AI_TTS $tts)
    {
        $clip = new self();
        $clip->setAiTts($tts);

        return $clip;
    }

    /**
     * Set media URL.
     *
     * @param string $mediaURL
     *
     * @return $this
     */
    public function setMediaURL($mediaURL)
    {
        $this->mediaURL = $mediaURL;

        return $this;
    }

    /**
     * Set media ID.
     *
     * @param string $mediaId
     *
     * @return $this
     */
    public function setMediaId($mediaId)
    {
        $this->mediaId = $mediaId;

        return $this;
    }

    /**
     * Set source trim range.
     *
     * @param float|null $in
     * @param float|null $out
     *
     * @return $this
     */
    public function setSourceRange($in, $out)
    {
        $this->in = $in;
        $this->out = $out;

        return $this;
    }

    /**
     * Set timeline range.
     *
     * @param float|null $timelineIn
     * @param float|null $timelineOut
     *
     * @return $this
     */
    public function setTimelineRange($timelineIn, $timelineOut)
    {
        $this->timelineIn = $timelineIn;
        $this->timelineOut = $timelineOut;

        return $this;
    }

    /**
     * Set duration.
     *
     * @param float $duration
     *
     * @return $this
     */
    public function setDuration($duration)
    {
        $this->duration = $duration;

        return $this;
    }

    /**
     * Set loop mode.
     *
     * @param string $loopMode
     *
     * @return $this
     */
    public function setLoopMode($loopMode)
    {
        $this->loopMode = $loopMode;

        return $this;
    }

    /**
     * Bind AI TTS payload.
     *
     * @param AI_TTS $aiTts
     *
     * @return $this
     */
    public function setAiTts(AI_TTS $aiTts)
    {
        $this->aiTts = $aiTts;
        $this->type = 'AI_TTS';

        return $this;
    }

    /**
     * Add a clip-level effect.
     *
     * @param Effect $effect
     *
     * @return $this
     */
    public function addEffect(Effect $effect)
    {
        $this->effects[] = $effect;

        return $this;
    }

    /**
     * Convert object to array.
     *
     * @return array
     */
    public function toArray()
    {
        $data = array(
            'Type' => $this->type,
            'MediaURL' => $this->mediaURL,
            'MediaId' => $this->mediaId,
            'In' => $this->in,
            'Out' => $this->out,
            'Duration' => $this->duration,
            'TimelineIn' => $this->timelineIn,
            'TimelineOut' => $this->timelineOut,
            'LoopMode' => $this->loopMode,
            'Effects' => $this->effects,
        );

        if ($this->aiTts !== null) {
            $data = array_merge($data, $this->aiTts->toClipFields());
        }

        return $this->finalize($data);
    }
}
