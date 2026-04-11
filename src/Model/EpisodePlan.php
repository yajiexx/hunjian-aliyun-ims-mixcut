<?php

namespace Hunjian\AliyunImsMixcut\Model;

use Hunjian\AliyunImsMixcut\Builder\StrategyBuilder;
use Hunjian\AliyunImsMixcut\Contracts\TemplateInterface;

/**
 * Class EpisodePlan
 *
 * One producible episode slice inside a campaign.
 */
class EpisodePlan extends BaseStructure
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var TemplateInterface
     */
    protected $template;

    /**
     * @var MaterialPool|null
     */
    protected $pool;

    /**
     * @var StrategyBuilder|null
     */
    protected $strategy;

    /**
     * @var int
     */
    protected $count = 1;

    /**
     * @var int|null
     */
    protected $sceneCount;

    /**
     * @var array
     */
    protected $context = array();

    /**
     * @var array
     */
    protected $options = array();

    /**
     * @var array
     */
    protected $metadata = array();

    /**
     * @var string|null
     */
    protected $outputPattern;

    /**
     * Create plan.
     *
     * @param string            $name
     * @param TemplateInterface $template
     */
    public function __construct($name, TemplateInterface $template)
    {
        $this->name = $name;
        $this->template = $template;
    }

    /**
     * Set material pool.
     *
     * @param MaterialPool $pool
     *
     * @return $this
     */
    public function setPool(MaterialPool $pool)
    {
        $this->pool = $pool;

        return $this;
    }

    /**
     * Set strategy.
     *
     * @param StrategyBuilder $strategy
     *
     * @return $this
     */
    public function setStrategy(StrategyBuilder $strategy)
    {
        $this->strategy = $strategy;

        return $this;
    }

    /**
     * Set count.
     *
     * @param int $count
     *
     * @return $this
     */
    public function setCount($count)
    {
        $this->count = (int) $count;

        return $this;
    }

    /**
     * Set scene count.
     *
     * @param int $sceneCount
     *
     * @return $this
     */
    public function setSceneCount($sceneCount)
    {
        $this->sceneCount = (int) $sceneCount;

        return $this;
    }

    /**
     * Merge context.
     *
     * @param array $context
     *
     * @return $this
     */
    public function setContext(array $context)
    {
        $this->context = array_merge($this->context, $context);

        return $this;
    }

    /**
     * Merge submit options.
     *
     * @param array $options
     *
     * @return $this
     */
    public function setOptions(array $options)
    {
        $this->options = array_merge($this->options, $options);

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
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Get template.
     *
     * @return TemplateInterface
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * Get pool.
     *
     * @return MaterialPool|null
     */
    public function getPool()
    {
        return $this->pool;
    }

    /**
     * Get strategy.
     *
     * @return StrategyBuilder|null
     */
    public function getStrategy()
    {
        return $this->strategy;
    }

    /**
     * Get count.
     *
     * @return int
     */
    public function getCount()
    {
        return $this->count;
    }

    /**
     * Get scene count.
     *
     * @return int|null
     */
    public function getSceneCount()
    {
        return $this->sceneCount;
    }

    /**
     * Get context.
     *
     * @return array
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * Get options.
     *
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
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
     * Resolve output URL.
     *
     * @param string      $campaignName
     * @param string|null $themeName
     * @param int         $n
     *
     * @return string|null
     */
    public function resolveOutputMediaUrl($campaignName, $themeName, $n)
    {
        if ($this->outputPattern === null) {
            return null;
        }

        return str_replace(
            array('{campaign}', '{episode}', '{theme}', '{n}'),
            array($campaignName, $this->name, $themeName, (string) $n),
            $this->outputPattern
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
            'count' => $this->count,
            'sceneCount' => $this->sceneCount,
            'context' => $this->context,
            'options' => $this->options,
            'metadata' => $this->metadata,
            'outputPattern' => $this->outputPattern,
        ));
    }
}
