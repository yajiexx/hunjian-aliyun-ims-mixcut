<?php

namespace Hunjian\AliyunImsMixcut\Builder;

use Hunjian\AliyunImsMixcut\Contracts\TemplateInterface;
use Hunjian\AliyunImsMixcut\Model\BatchTask;
use Hunjian\AliyunImsMixcut\Model\MaterialPool;
use Hunjian\AliyunImsMixcut\Model\OutputMediaConfig;
use Hunjian\AliyunImsMixcut\Model\ThemeConfig;

/**
 * Class BatchTaskListBuilder
 *
 * Builds repeatable batch task lists for local templates.
 */
class BatchTaskListBuilder
{
    /**
     * @var TemplateInterface|null
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
     * @var array
     */
    protected $baseContext = array();

    /**
     * @var array
     */
    protected $options = array();

    /**
     * @var int
     */
    protected $count = 1;

    /**
     * @var int
     */
    protected $seed = 1;

    /**
     * @var int|null
     */
    protected $sceneCount;

    /**
     * @var string|null
     */
    protected $outputPattern;

    /**
     * @var ThemeConfig|null
     */
    protected $theme;

    /**
     * Set template.
     *
     * @param TemplateInterface $template
     *
     * @return $this
     */
    public function template(TemplateInterface $template)
    {
        $this->template = $template;

        return $this;
    }

    /**
     * Set material pool.
     *
     * @param MaterialPool $pool
     *
     * @return $this
     */
    public function pool(MaterialPool $pool)
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
    public function strategy(StrategyBuilder $strategy)
    {
        $this->strategy = $strategy;

        return $this;
    }

    /**
     * Set base context.
     *
     * @param array $context
     *
     * @return $this
     */
    public function context(array $context)
    {
        $this->baseContext = array_merge($this->baseContext, $context);

        return $this;
    }

    /**
     * Set submit options.
     *
     * @param array $options
     *
     * @return $this
     */
    public function options(array $options)
    {
        $this->options = array_merge($this->options, $options);

        return $this;
    }

    /**
     * Set task count.
     *
     * @param int $count
     *
     * @return $this
     */
    public function count($count)
    {
        $this->count = (int) $count;

        return $this;
    }

    /**
     * Set base seed.
     *
     * @param int $seed
     *
     * @return $this
     */
    public function seed($seed)
    {
        $this->seed = (int) $seed;

        return $this;
    }

    /**
     * Set scene count.
     *
     * @param int $sceneCount
     *
     * @return $this
     */
    public function sceneCount($sceneCount)
    {
        $this->sceneCount = (int) $sceneCount;

        return $this;
    }

    /**
     * Set output media pattern with {n} placeholder.
     *
     * @param string $outputPattern
     *
     * @return $this
     */
    public function outputPattern($outputPattern)
    {
        $this->outputPattern = $outputPattern;

        return $this;
    }

    /**
     * Set theme defaults.
     *
     * @param ThemeConfig $theme
     *
     * @return $this
     */
    public function theme(ThemeConfig $theme)
    {
        $this->theme = $theme;

        return $this;
    }

    /**
     * Build task list.
     *
     * @return array
     */
    public function build()
    {
        $tasks = array();

        for ($i = 1; $i <= $this->count; $i++) {
            $context = $this->baseContext;
            $context['seed'] = $this->seed + ($i - 1);

            if ($this->pool !== null) {
                $context['pool'] = $this->pool->toTemplatePool();
            }

            if ($this->strategy !== null) {
                $context['strategy'] = $this->strategy;
            }

            if ($this->sceneCount !== null) {
                $context['sceneCount'] = $this->sceneCount;
            }

            if ($this->theme !== null) {
                $context = $this->theme->applyToContext($context);
            }

            if ($this->outputPattern !== null) {
                $context['outputMediaConfig'] = OutputMediaConfig::oss(str_replace('{n}', (string) $i, $this->outputPattern));
            } elseif ($this->theme !== null && $this->theme->resolveOutputMediaUrl($i) !== null) {
                $context['outputMediaConfig'] = OutputMediaConfig::oss($this->theme->resolveOutputMediaUrl($i));
            }

            $tasks[] = new BatchTask($this->template, $context, $this->options);
        }

        return $tasks;
    }
}
