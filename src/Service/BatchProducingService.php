<?php

namespace Hunjian\AliyunImsMixcut\Service;

use Hunjian\AliyunImsMixcut\Contracts\TemplateInterface;
use Hunjian\AliyunImsMixcut\Model\BatchTask;
use Hunjian\AliyunImsMixcut\Model\OutputMediaConfig;
use Hunjian\AliyunImsMixcut\Model\Timeline;

/**
 * Class BatchProducingService
 *
 * Batch orchestration around repeated SubmitMediaProducingJob calls.
 */
class BatchProducingService
{
    /**
     * @var MediaProducingService
     */
    protected $service;

    /**
     * Create batch service.
     *
     * @param MediaProducingService $service
     */
    public function __construct(MediaProducingService $service)
    {
        $this->service = $service;
    }

    /**
     * Submit a batch of timeline requests.
     *
     * Supported item forms:
     * - ['timeline' => Timeline, 'outputMediaConfig' => OutputMediaConfig, 'options' => []]
     * - ['template' => TemplateInterface, 'context' => [], 'options' => []]
     *
     * @param array $batch
     *
     * @return array
     */
    public function submitBatch(array $batch)
    {
        $results = array();

        foreach ($batch as $item) {
            if ($item instanceof BatchTask) {
                $results[] = $this->service->submitLocalTemplate(
                    $item->getTemplate(),
                    $item->getContext(),
                    $item->getOptions()
                );
                continue;
            }

            if (isset($item['timeline']) && $item['timeline'] instanceof Timeline) {
                $results[] = $this->service->submitTimeline(
                    $item['timeline'],
                    $item['outputMediaConfig'],
                    isset($item['options']) ? $item['options'] : array()
                );
                continue;
            }

            if (isset($item['template']) && $item['template'] instanceof TemplateInterface) {
                $results[] = $this->service->submitLocalTemplate(
                    $item['template'],
                    isset($item['context']) ? $item['context'] : array(),
                    isset($item['options']) ? $item['options'] : array()
                );
            }
        }

        return $results;
    }

    /**
     * Submit a template multiple times using different contexts.
     *
     * @param TemplateInterface $template
     * @param array             $contexts
     * @param array             $options
     *
     * @return array
     */
    public function submitTemplate(TemplateInterface $template, array $contexts, array $options = array())
    {
        $batch = array();

        foreach ($contexts as $context) {
            $batch[] = array(
                'template' => $template,
                'context' => $context,
                'options' => $options,
            );
        }

        return $this->submitBatch($batch);
    }
}
