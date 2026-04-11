<?php

namespace Hunjian\AliyunImsMixcut\Builder;

use Hunjian\AliyunImsMixcut\Model\AudioTrack;
use Hunjian\AliyunImsMixcut\Model\AudioTrackClip;
use Hunjian\AliyunImsMixcut\Model\Effect\ADenoise;
use Hunjian\AliyunImsMixcut\Model\Effect\AFade;
use Hunjian\AliyunImsMixcut\Model\Effect\AI_ASR;
use Hunjian\AliyunImsMixcut\Model\Effect\AI_TTS;
use Hunjian\AliyunImsMixcut\Model\Effect\AEqualize;
use Hunjian\AliyunImsMixcut\Model\Effect\ALoudNorm;
use Hunjian\AliyunImsMixcut\Model\Effect\Volume;

/**
 * Class AudioBuilder
 *
 * Builder for audio tracks, BGM and AI narration.
 */
class AudioBuilder
{
    /**
     * @var AudioTrack
     */
    protected $track;

    /**
     * Create builder.
     */
    public function __construct()
    {
        $this->track = new AudioTrack();
    }

    /**
     * Add a raw audio clip.
     *
     * @param AudioTrackClip $clip
     *
     * @return $this
     */
    public function addClip(AudioTrackClip $clip)
    {
        $this->track->addClip($clip);

        return $this;
    }

    /**
     * Add background music.
     *
     * @param string     $mediaURL
     * @param float|null $duration
     * @param bool       $loop
     * @param float      $gain
     *
     * @return $this
     */
    public function addBgm($mediaURL, $duration = null, $loop = true, $gain = -10.0)
    {
        $clip = AudioTrackClip::fromMediaUrl($mediaURL)
            ->setDuration($duration)
            ->setLoopMode($loop ? 'Loop' : null)
            ->addEffect(Volume::gain($gain))
            ->addEffect(ALoudNorm::make());

        $this->track->addClip($clip);

        return $this;
    }

    /**
     * Add AI narration clip.
     *
     * @param string     $content
     * @param string     $voice
     * @param float|null $timelineIn
     * @param float|null $timelineOut
     * @param array      $options
     *
     * @return AudioTrackClip
     */
    public function addAiNarration($content, $voice, $timelineIn = 0.0, $timelineOut = null, array $options = array())
    {
        $tts = AI_TTS::fromText($content, $voice)
            ->setSpeechRate(isset($options['speechRate']) ? $options['speechRate'] : 0)
            ->setPitchRate(isset($options['pitchRate']) ? $options['pitchRate'] : 0)
            ->setVolume(isset($options['ttsVolume']) ? $options['ttsVolume'] : 50);

        if (isset($options['ssml']) && $options['ssml']) {
            $tts->setSsml($options['ssml']);
        }

        $clip = AudioTrackClip::fromTts($tts)
            ->setTimelineRange($timelineIn, $timelineOut)
            ->addEffect(AFade::make('In', isset($options['fadeIn']) ? $options['fadeIn'] : 0.3))
            ->addEffect(AFade::make('Out', isset($options['fadeOut']) ? $options['fadeOut'] : 0.4))
            ->addEffect(ADenoise::make(isset($options['denoiseMode']) ? $options['denoiseMode'] : 'off'))
            ->addEffect(ALoudNorm::make())
            ->addEffect(AEqualize::make(1200, 200, 2.0));

        if (!empty($options['asr'])) {
            $clip->addEffect(AI_ASR::make(
                isset($options['language']) ? $options['language'] : 'zh-CN',
                $options['asr']
            ));
        }

        $this->track->addClip($clip);

        return $clip;
    }

    /**
     * Build audio track.
     *
     * @return AudioTrack
     */
    public function build()
    {
        return $this->track;
    }
}
