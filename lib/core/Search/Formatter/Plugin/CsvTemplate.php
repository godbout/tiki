<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class Search_Formatter_Plugin_CsvTemplate extends Search_Formatter_Plugin_AbstractTableTemplate
{
    public function getFormat()
    {
        return self::FORMAT_CSV;
    }

    public function prepareEntry($valueFormatter)
    {
        $entry = [];
        $searchRow = $valueFormatter->getPlainValues();
        foreach ($this->fields as $field => $arguments) {
            if (isset($arguments['format'])) {
                $format = $arguments['format'];
            } else {
                $format = 'plain';
            }
            unset($arguments['format']);
            unset($arguments['name']);
            unset($arguments['field']);
            $entry[str_replace('tracker_field_', '', $field)] = trim($valueFormatter->$format($field, $arguments));
        }

        return $entry;
    }

    public function renderEntries(Search_ResultSet $entries)
    {
        $fh = fopen('php://temp', 'rw');
        $header = [];
        foreach ($this->fields as $field => $arguments) {
            $header[] = ! empty($arguments['label']) ? $arguments['label'] : '';
        }
        fputcsv($fh, $header);
        foreach ($entries as $entry) {
            fputcsv($fh, $entry);
        }
        rewind($fh);
        $csv = stream_get_contents($fh);
        fclose($fh);

        return $csv;
    }
}
