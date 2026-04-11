<?php

namespace Hunjian\AliyunImsMixcut\Model;

/**
 * Class CampaignPlan
 *
 * Top-level persistable plan containing many episode plans.
 */
class CampaignPlan extends BaseStructure
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var ThemeConfig|null
     */
    protected $theme;

    /**
     * @var array
     */
    protected $episodes = array();

    /**
     * @var array
     */
    protected $metadata = array();

    /**
     * @var string|null
     */
    protected $outputPattern;

    /**
     * Create campaign.
     *
     * @param string $name
     */
    public function __construct($name)
    {
        $this->name = $name;
    }

    /**
     * Set theme.
     *
     * @param ThemeConfig $theme
     *
     * @return $this
     */
    public function setTheme(ThemeConfig $theme)
    {
        $this->theme = $theme;

        return $this;
    }

    /**
     * Add episode plan.
     *
     * @param EpisodePlan $episode
     *
     * @return $this
     */
    public function addEpisode(EpisodePlan $episode)
    {
        $this->episodes[] = $episode;

        return $this;
    }

    /**
     * Merge metadata.
     *
     * @param array $metadata
     *
     * @return $this
     */
    public function setMetadata(array $metadata)
    {
        $this->metadata = array_merge($this->metadata, $metadata);

        return $this;
    }

    /**
     * Set output pattern.
     *
     * @param string $outputPattern
     *
     * @return $this
     */
    public function setOutputPattern($outputPattern)
    {
        $this->outputPattern = $outputPattern;

        return $this;
    }

    /**
     * Get campaign name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Get theme.
     *
     * @return ThemeConfig|null
     */
    public function getTheme()
    {
        return $this->theme;
    }

    /**
     * Get episodes.
     *
     * @return array
     */
    public function getEpisodes()
    {
        return $this->episodes;
    }

    /**
     * Get metadata.
     *
     * @return array
     */
    public function getMetadata()
    {
        return $this->metadata;
    }

    /**
     * Resolve output URL for an episode index.
     *
     * @param string $episodeName
     * @param int    $n
     *
     * @return string|null
     */
    public function resolveOutputMediaUrl($episodeName, $n)
    {
        $pattern = $this->outputPattern;
        if ($pattern === null && $this->theme !== null) {
            $pattern = $this->theme->getOutputPattern();
        }

        if ($pattern === null) {
            return null;
        }

        return str_replace(
            array('{campaign}', '{episode}', '{theme}', '{n}'),
            array($this->name, $episodeName, $this->theme ? $this->theme->getName() : null, (string) $n),
            $pattern
        );
    }

    /**
     * Convert to array.
     *
     * @return array
     */
    public function toArray()
    {
        return $this->finalize(array(
            'name' => $this->name,
            'theme' => $this->theme,
            'episodes' => $this->episodes,
            'metadata' => $this->metadata,
            'outputPattern' => $this->outputPattern,
        ));
    }
}
