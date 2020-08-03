<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class Tiki_Profile_Writer_Queue
{
    private $entries = [];

    public function add(array $data)
    {
        if ($info = $this->findInfo($data)) {
            $hash = "{$info['type']}:{$info['object']}";

            if ($info['remove']) {
                unset($this->entries[$hash]);
            } else {
                $this->entries[$hash] = $info;
            }
        }
    }

    public function filterIncluded(Tiki_Profile_Writer $writer)
    {
        array_walk(
            $this->entries,
            function (& $entry) use ($writer) {
                $timestamp = $writer->getInclusionTimestamp($entry['type'], $entry['object']);
                $entry['stored'] = $timestamp;
                $entry['status'] = $timestamp ? 'MODIFIED' : 'NEW';
            }
        );

        $this->entries = array_filter(
            $this->entries,
            function ($entry) use ($writer) {
                return $entry['timestamp'] > $entry['stored'];
            }
        );
    }

    public function filterInstalled(Tiki_Profile_Writer_ProfileFinder $finder)
    {
        $this->entries = array_filter(
            $this->entries,
            function ($entry) use ($finder) {
                $finder->lookup($entry['type'], $entry['object']);

                return ! $finder->checkProfileAndFlush();
            }
        );
    }

    private function findInfo(array $data)
    {
        if ($data['type'] == 'wiki page') {
            return [
                'type' => 'wiki_page',
                'object' => $data['object'],
                'timestamp' => $data['timestamp'],
                'remove' => $data['action'] == 'Removed',
            ];
        } elseif ($data['type'] == 'category') {
            return [
                'type' => 'category',
                'object' => $data['object'],
                'timestamp' => $data['timestamp'],
                'remove' => $data['action'] == 'Removed',
            ];
        } elseif ($data['action'] == 'feature') {
            return [
                'type' => 'preference',
                'object' => $data['object'],
                'timestamp' => $data['timestamp'],
                'remove' => false,
            ];
        } elseif ($data['type'] == 'tracker') {
            $extra = parse_str($data['detail'], $parts);
            if (isset($parts['fieldId'])) {
                return [
                    'type' => 'tracker_field',
                    'object' => $parts['fieldId'],
                    'timestamp' => $data['timestamp'],
                    'remove' => $parts['operation'] == 'remove_field',
                ];
            }

            return [
                    'type' => 'tracker',
                    'object' => $data['object'],
                    'timestamp' => $data['timestamp'],
                    'remove' => $data['action'] == 'Removed',
                ];
        }
    }

    public function __toString()
    {
        $entries = $this->entries;
        usort(
            $entries,
            function ($a, $b) {
                return $a['timestamp'] - $b['timestamp'];
            }
        );

        array_walk(
            $entries,
            function (& $entry) {
                $entry['timestamp'] = date('Y-m-d H:i:s (D)', $entry['timestamp']);
            },
            $entries
        );

        $columns = ['timestamp', 'type', 'object', 'status'];
        $widths = array_fill_keys($columns, 0);

        array_unshift(
            $entries,
            [
                'type' => 'Type',
                'object' => 'Object',
                'timestamp' => 'Last Modification',
                'status' => 'Status',
            ]
        );

        foreach ($entries as $entry) {
            foreach ($columns as $column) {
                $widths[$column] = max($widths[$column], mb_strlen($entry[$column]));
            }
        }

        $out = '';
        foreach ($entries as $entry) {
            foreach ($columns as $column) {
                $out .= str_pad($entry[$column], $widths[$column] + 3);
            }
            $out .= PHP_EOL;
        }

        return $out;
    }
}
