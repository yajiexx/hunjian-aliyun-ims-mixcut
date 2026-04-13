<?php

namespace Hunjian\AliyunImsMixcut\Template;

use Hunjian\AliyunImsMixcut\Contracts\TemplateInterface;
use Hunjian\AliyunImsMixcut\Editor\EditorProjectCompiler;
use Hunjian\AliyunImsMixcut\Editor\EditorProjectNormalizer;
use Hunjian\AliyunImsMixcut\Editor\EditorProjectValidator;
use Hunjian\AliyunImsMixcut\Model\OutputMediaConfig;

/**
 * Editor project template for timeline-based clipping payloads.
 */
class EditorProjectTemplate implements TemplateInterface
{
    /**
     * @var EditorProjectNormalizer
     */
    protected $normalizer;

    /**
     * @var EditorProjectValidator
     */
    protected $validator;

    /**
     * @var EditorProjectCompiler
     */
    protected $compiler;

    /**
     * @param EditorProjectNormalizer|null $normalizer
     * @param EditorProjectValidator|null $validator
     * @param EditorProjectCompiler|null $compiler
     */
    public function __construct(
        EditorProjectNormalizer $normalizer = null,
        EditorProjectValidator $validator = null,
        EditorProjectCompiler $compiler = null
    ) {
        $this->normalizer = $normalizer ?: new EditorProjectNormalizer();
        $this->validator = $validator ?: new EditorProjectValidator();
        $this->compiler = $compiler ?: new EditorProjectCompiler();
    }

    /**
     * Build editor project timeline.
     *
     * @param array $context
     *
     * @return array
     */
    public function build(array $context = array())
    {
        $normalized = $this->normalizer->normalize($context);
        $this->validator->validate($normalized);
        $compiled = $this->compiler->compile($normalized);

        $output = $normalized['outputMediaConfig'] instanceof OutputMediaConfig
            ? clone $normalized['outputMediaConfig']
            : OutputMediaConfig::oss(
                $normalized['outputMediaURL'] ? $normalized['outputMediaURL'] : 'oss://demo-bucket/mixcut/editor-project.mp4'
            );
        $output->setSize($normalized['canvas']['width'], $normalized['canvas']['height']);

        return array(
            'timeline' => $compiled['timeline'],
            'outputMediaConfig' => $output,
        );
    }
}
