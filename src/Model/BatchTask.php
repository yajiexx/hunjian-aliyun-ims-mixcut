<?php

namespace Hunjian\AliyunImsMixcut\Model;

use Hunjian\AliyunImsMixcut\Contracts\TemplateInterface;

/**
 * Class BatchTask
 *
 * One local template execution unit with resolved context and options.
 */
class BatchTask
{
    /**
     * @var TemplateInterface
     */
    protected $template;

    /**
     * @var array
     */
    protected $context = array();

    /**
     * @var array
     */
    protected $options = array();

    /**
     * @var array|null
     */
    protected $built;

    /**
     * @var array
     */
    protected $metadata = array();

    /**
     * Create task.
     *
     * @param TemplateInterface $template
     * @param array             $context
     * @param array             $options
     * @param array             $metadata
     */
    public function __construct(TemplateInterface $template, array $context = array(), array $options = array(), array $metadata = array())
    {
        $this->template = $template;
        $this->context = $context;
        $this->options = $options;
        $this->metadata = $metadata;
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
     * Get context.
     *
     * @return array
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * Get submit options.
     *
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * Get task metadata.
     *
     * @return array
     */
    public function getMetadata()
    {
        return $this->metadata;
    }

    /**
     * Build payload once.
     *
     * @return array
     */
    public function build()
    {
        if ($this->built === null) {
            $this->built = $this->template->build($this->context);
        }

        return $this->built;
    }

    /**
     * Get built output config.
     *
     * @return OutputMediaConfig
     */
    public function getBuiltOutputMediaConfig()
    {
        $built = $this->build();

        return $built['outputMediaConfig'];
    }
}
