<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tracker\Tabular\Source;

class CsvSource implements SourceInterface
{
    private $schema;
    private $file;

    public function __construct(\Tracker\Tabular\Schema $schema, $fileName, $delimiter = ',')
    {
        ini_set('auto_detect_line_endings', true);
        $this->schema = $schema->getPlainOutputSchema();
        $this->file = new \SplFileObject($fileName, 'r');
        $this->file->setCsvControl($delimiter);
    }

    public function getEntries()
    {
        $this->file->fseek(0);

        $headers = $this->file->fgetcsv();
        $this->schema->validateAgainstHeaders($headers);

        while (! $this->file->eof()) {
            $row = $this->file->fgetcsv();

            if (count($row) == 1 && empty($row[0])) {
                continue;
            }

            $data = [];
            foreach ($this->schema->getColumns() as $i => $column) {
                $data[spl_object_hash($column)] = $row[$i];
            }

            yield new CsvSourceEntry($data);
        }
    }

    public function getSchema()
    {
        return $this->schema;
    }
}
