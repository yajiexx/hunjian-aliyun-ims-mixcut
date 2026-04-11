<?php

namespace Hunjian\AliyunImsMixcut\Template;

use Hunjian\AliyunImsMixcut\Contracts\TemplateInterface;
use Hunjian\AliyunImsMixcut\Model\OutputMediaConfig;
use Hunjian\AliyunImsMixcut\Scene\SceneDurationResolver;
use Hunjian\AliyunImsMixcut\Scene\ScenePayloadNormalizer;
use Hunjian\AliyunImsMixcut\Scene\SceneTimelineAssembler;

/**
 * Scene-based mixcut template for editor payloads.
 */
class SceneMixcutTemplate implements TemplateInterface
{
    /**
     * @var ScenePayloadNormalizer
     */
    protected $normalizer;

    /**
     * @var SceneDurationResolver
     */
    protected $durationResolver;

    /**
     * @var SceneTimelineAssembler
     */
    protected $assembler;

    /**
     * @param ScenePayloadNormalizer|null $normalizer
     * @param SceneDurationResolver|null  $durationResolver
     * @param SceneTimelineAssembler|null $assembler
     */
    public function __construct(
        ScenePayloadNormalizer $normalizer = null,
        SceneDurationResolver $durationResolver = null,
        SceneTimelineAssembler $assembler = null
    ) {
        $this->normalizer = $normalizer ?: new ScenePayloadNormalizer();
        $this->durationResolver = $durationResolver ?: new SceneDurationResolver();
        $this->assembler = $assembler ?: new SceneTimelineAssembler();
    }

    /**
     * Build scene mixcut timeline.
     *
     * @param array $context
     *
     * @return array
     */
    public function build(array $context = array())
    {
        $normalized = $this->normalizer->normalize($context);
        $resolved = $this->durationResolver->resolve($normalized);
        $assembled = $this->assembler->assemble($resolved);

        $output = $normalized['outputMediaConfig'] instanceof OutputMediaConfig
            ? clone $normalized['outputMediaConfig']
            : OutputMediaConfig::oss(
                $normalized['outputMediaURL'] ? $normalized['outputMediaURL'] : 'oss://demo-bucket/mixcut/scene-mixcut.mp4'
            );
        $output->setSize($normalized['canvas']['width'], $normalized['canvas']['height']);

        return array(
            'timeline' => $assembled['timeline'],
            'outputMediaConfig' => $output,
        );
    }
}
