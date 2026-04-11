<?php

namespace Hunjian\AliyunImsMixcut\Builder;

use Hunjian\AliyunImsMixcut\Model\BatchTask;
use Hunjian\AliyunImsMixcut\Model\CampaignPlan;
use Hunjian\AliyunImsMixcut\Model\OutputMediaConfig;

/**
 * Class CampaignTaskListBuilder
 *
 * Expands campaign/episode plans into executable batch tasks.
 */
class CampaignTaskListBuilder
{
    /**
     * @var CampaignPlan|null
     */
    protected $campaign;

    /**
     * Set campaign.
     *
     * @param CampaignPlan $campaign
     *
     * @return $this
     */
    public function fromCampaign(CampaignPlan $campaign)
    {
        $this->campaign = $campaign;

        return $this;
    }

    /**
     * Build tasks.
     *
     * @return array
     */
    public function build()
    {
        $tasks = array();
        $campaign = $this->campaign;
        $theme = $campaign->getTheme();

        foreach ($campaign->getEpisodes() as $episode) {
            for ($i = 1; $i <= $episode->getCount(); $i++) {
                $context = $episode->getContext();
                $context['seed'] = array_key_exists('seed', $context) ? $context['seed'] + ($i - 1) : $i;

                if ($theme !== null) {
                    $context = $theme->applyToContext($context);
                }

                if ($episode->getPool() !== null) {
                    $context['pool'] = $episode->getPool()->toTemplatePool();
                }

                if ($episode->getStrategy() !== null) {
                    $context['strategy'] = $episode->getStrategy();
                }

                if ($episode->getSceneCount() !== null) {
                    $context['sceneCount'] = $episode->getSceneCount();
                }

                $outputUrl = $episode->resolveOutputMediaUrl(
                    $campaign->getName(),
                    $theme ? $theme->getName() : null,
                    $i
                );

                if ($outputUrl === null) {
                    $outputUrl = $campaign->resolveOutputMediaUrl($episode->getName(), $i);
                }

                if ($outputUrl !== null) {
                    $context['outputMediaConfig'] = OutputMediaConfig::oss($outputUrl);
                }

                $metadata = array_merge(
                    $campaign->getMetadata(),
                    $episode->getMetadata(),
                    array(
                        'campaign' => $campaign->getName(),
                        'episode' => $episode->getName(),
                        'sequence' => $i,
                        'theme' => $theme ? $theme->getName() : null,
                    )
                );

                $tasks[] = new BatchTask(
                    $episode->getTemplate(),
                    $context,
                    $episode->getOptions(),
                    $metadata
                );
            }
        }

        return $tasks;
    }
}
