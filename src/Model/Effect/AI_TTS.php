<?php

namespace Hunjian\AliyunImsMixcut\Model\Effect;

use Hunjian\AliyunImsMixcut\Model\BaseStructure;

/**
 * Class AI_TTS
 *
 * TTS clip payload. It is intentionally separated from Effect because
 * official IMS treats AI_TTS as an audio clip type, not a clip effect.
 */
class AI_TTS extends BaseStructure
{
    /**
     * @var string|null
     */
    protected $content;

    /**
     * @var string|null
     */
    protected $ssml;

    /**
     * @var string|null
     */
    protected $voice;

    /**
     * @var int|null
     */
    protected $speechRate;

    /**
     * @var int|null
     */
    protected $pitchRate;

    /**
     * @var int|null
     */
    protected $volume;

    /**
     * Create TTS payload from plain text.
     *
     * @param string $content
     * @param string $voice
     *
     * @return self
     */
    public static function fromText($content, $voice)
    {
        $tts = new self();
        $tts->setContent($content);
        $tts->setVoice($voice);

        return $tts;
    }

    /**
     * Create TTS payload from SSML.
     *
     * @param string $ssml
     * @param string $voice
     *
     * @return self
     */
    public static function fromSsml($ssml, $voice)
    {
        $tts = new self();
        $tts->setSsml($ssml);
        $tts->setVoice($voice);

        return $tts;
    }

    /**
     * Set plain text content.
     *
     * @param string $content
     *
     * @return $this
     */
    public function setContent($content)
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Set SSML content.
     *
     * @param string $ssml
     *
     * @return $this
     */
    public function setSsml($ssml)
    {
        $this->ssml = $ssml;

        return $this;
    }

    /**
     * Set voice.
     *
     * @param string $voice
     *
     * @return $this
     */
    public function setVoice($voice)
    {
        $this->voice = $voice;

        return $this;
    }

    /**
     * Set speech rate.
     *
     * @param int $speechRate
     *
     * @return $this
     */
    public function setSpeechRate($speechRate)
    {
        $this->speechRate = (int) $speechRate;

        return $this;
    }

    /**
     * Set pitch rate.
     *
     * @param int $pitchRate
     *
     * @return $this
     */
    public function setPitchRate($pitchRate)
    {
        $this->pitchRate = (int) $pitchRate;

        return $this;
    }

    /**
     * Set volume.
     *
     * @param int $volume
     *
     * @return $this
     */
    public function setVolume($volume)
    {
        $this->volume = (int) $volume;

        return $this;
    }

    /**
     * Convert payload to audio clip fields.
     *
     * @return array
     */
    public function toClipFields()
    {
        return $this->toArray();
    }

    /**
     * Convert payload to array.
     *
     * @return array
     */
    public function toArray()
    {
        $data = array(
            'Type' => 'AI_TTS',
            'Content' => $this->content,
            'SSML' => $this->ssml,
            'Voice' => $this->voice,
            'SpeechRate' => $this->speechRate,
            'PitchRate' => $this->pitchRate,
            'Volume' => $this->volume,
        );

        return $this->finalize($data);
    }
}
