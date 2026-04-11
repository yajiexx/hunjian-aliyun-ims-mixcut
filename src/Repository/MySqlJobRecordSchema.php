<?php

namespace Hunjian\AliyunImsMixcut\Repository;

/**
 * Class MySqlJobRecordSchema
 *
 * Provides MySQL DDL for job record storage.
 */
class MySqlJobRecordSchema
{
    /**
     * Build CREATE TABLE SQL.
     *
     * @param string $table
     *
     * @return string
     */
    public function createTableSql($table = 'ims_job_records')
    {
        return "CREATE TABLE `{$table}` (
  `job_id` varchar(128) NOT NULL,
  `status` varchar(32) DEFAULT NULL,
  `media_url` text,
  `output_media_url` text,
  `campaign` varchar(128) DEFAULT NULL,
  `episode` varchar(128) DEFAULT NULL,
  `theme` varchar(128) DEFAULT NULL,
  `sequence` int DEFAULT NULL,
  `attempts` int DEFAULT 0,
  `elapsed_seconds` decimal(10,3) DEFAULT 0,
  `submitted_at` varchar(64) DEFAULT NULL,
  `finished_at` varchar(64) DEFAULT NULL,
  `metadata_json` longtext,
  `request_payload_json` longtext,
  `raw_json` longtext,
  PRIMARY KEY (`job_id`),
  KEY `idx_campaign_episode` (`campaign`, `episode`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    }
}
