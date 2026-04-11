<?php

namespace Hunjian\AliyunImsMixcut\Storage;

/**
 * Class CsvJobRecordExporter
 *
 * Exports many job records to CSV text.
 */
class CsvJobRecordExporter
{
    /**
     * Export records to CSV.
     *
     * @param array $records
     *
     * @return string
     */
    public function export(array $records)
    {
        $mapper = new JobRecordRowMapper();
        $rows = array();

        foreach ($records as $record) {
            $rows[] = $mapper->map($record);
        }

        if (empty($rows)) {
            return '';
        }

        $headers = array('job_id', 'status', 'campaign', 'episode', 'sequence');
        $lines = array(implode(',', $headers));

        foreach ($rows as $row) {
            $lines[] = implode(',', array(
                $this->escape(isset($row['job_id']) ? $row['job_id'] : null),
                $this->escape(isset($row['status']) ? $row['status'] : null),
                $this->escape(isset($row['campaign']) ? $row['campaign'] : null),
                $this->escape(isset($row['episode']) ? $row['episode'] : null),
                $this->escape(isset($row['sequence']) ? $row['sequence'] : null),
            ));
        }

        return implode("\n", $lines);
    }

    /**
     * Escape one CSV value.
     *
     * @param mixed $value
     *
     * @return string
     */
    protected function escape($value)
    {
        $value = (string) $value;
        $value = str_replace('"', '""', $value);

        if (strpos($value, ',') !== false || strpos($value, '"') !== false || strpos($value, "\n") !== false) {
            return '"' . $value . '"';
        }

        return $value;
    }
}
