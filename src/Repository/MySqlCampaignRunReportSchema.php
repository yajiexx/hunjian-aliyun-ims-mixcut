<?php

namespace Hunjian\AliyunImsMixcut\Repository;

/**
 * Class MySqlCampaignRunReportSchema
 *
 * Provides MySQL DDL for campaign report storage.
 */
class MySqlCampaignRunReportSchema
{
    /**
     * Build CREATE TABLE SQL.
     *
     * @param string $table
     *
     * @return string
     */
    public function createTableSql($table = 'ims_campaign_reports')
    {
        return "CREATE TABLE `{$table}` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `campaign_name` varchar(128) NOT NULL,
  `theme_name` varchar(128) DEFAULT NULL,
  `total_jobs` int DEFAULT 0,
  `finished_jobs` int DEFAULT 0,
  `failed_jobs` int DEFAULT 0,
  `pending_jobs` int DEFAULT 0,
  `started_at` varchar(64) DEFAULT NULL,
  `finished_at` varchar(64) DEFAULT NULL,
  `metadata_json` longtext,
  `summary_json` longtext,
  `report_json` longtext,
  PRIMARY KEY (`id`),
  KEY `idx_campaign_name` (`campaign_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    }
}
