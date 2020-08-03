<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tracker\Tabular\Schema;

class Column
{
    const HEADER_PATTERN = '/\[(\*?)(\w+):([^\]]+)\]$/';

    private $permName;
    private $label;
    private $mode;
    private $isPrimary = false;
    private $isReadOnly = false;
    private $isExportOnly = false;
    private $isUniqueKey = false;
    private $displayAlign = 'left';
    private $renderTransform;
    private $parseIntoTransform;
    private $querySources = [];
    private $incompatibilities = [];
    private $plainReplacement = null;

    public function __construct($permName, $mode)
    {
        $this->permName = $permName;
        $this->mode = $mode;
        $this->parseIntoTransform = function (& $info, $value) {
        };
    }

    public function getLabel()
    {
        return $this->label;
    }

    public function setLabel($label)
    {
        $this->label = $label;

        return $this;
    }

    public function getDisplayAlign()
    {
        return $this->displayAlign;
    }

    public function setDisplayAlign($align)
    {
        $this->displayAlign = $align;

        return $this;
    }

    public function addIncompatibility($field, $mode)
    {
        $this->incompatibilities[] = [$field, $mode];

        return $this;
    }

    public function setRenderTransform(callable $transform)
    {
        $this->renderTransform = $transform;

        return $this;
    }

    public function setParseIntoTransform(callable $transform)
    {
        $this->parseIntoTransform = $transform;

        return $this;
    }

    public function setPrimaryKey($pk)
    {
        $this->isPrimary = (bool) $pk;

        return $this;
    }

    public function setReadOnly($readOnly)
    {
        $this->isReadOnly = (bool) $readOnly;

        return $this;
    }

    public function setExportOnly($exportOnly)
    {
        $this->isExportOnly = (bool) $exportOnly;

        return $this;
    }

    public function setUniqueKey($uniqueKey)
    {
        $this->isUniqueKey = (bool) $uniqueKey;

        return $this;
    }

    public function setPlainReplacement($replacement)
    {
        $this->plainReplacement = $replacement;

        return $this;
    }

    public function is($field, $mode)
    {
        return $field == $this->permName && $mode == $this->mode;
    }

    public function isPrimaryKey()
    {
        return $this->isPrimary;
    }

    public function isReadOnly()
    {
        return $this->isReadOnly;
    }

    public function isExportOnly()
    {
        return $this->isExportOnly;
    }

    public function isUniqueKey()
    {
        return $this->isUniqueKey;
    }

    public function getField()
    {
        return $this->permName;
    }

    public function getMode()
    {
        return $this->mode;
    }

    public function getEncodedHeader()
    {
        if ($this->isReadOnly) {
            return $this->label;
        }
        $pk = $this->isPrimary ? '*' : '';

        return "{$this->label} [$pk{$this->permName}:{$this->mode}]";
    }

    public function getPlainReplacement()
    {
        return $this->plainReplacement;
    }

    public function render($value)
    {
        return call_user_func_array($this->renderTransform, func_get_args());
    }

    public function parseInto(& $info, $value)
    {
        $c = $this->parseIntoTransform;
        if (! $this->isReadOnly) {
            $c($info, $value);
        }
    }

    public function addQuerySource($name, $field)
    {
        $this->querySources[$name] = $field;

        return $this;
    }

    public function getQuerySources()
    {
        return $this->querySources;
    }

    public function validateAgainst(\Tracker\Tabular\Schema $schema)
    {
        if ($this->isPrimary && $this->isReadOnly) {
            throw new \Exception(tr('Primary Key fields cannot be read-only.'));
        }

        $selfCount = 0;

        foreach ($schema->getColumns() as $column) {
            if ($column->is($this->permName, $this->mode)) {
                $selfCount++;
            }

            foreach ($this->incompatibilities as $entry) {
                list($field, $mode) = $entry;

                if ($column->is($field, $mode)) {
                    // Skip incompatibility if either field is read-only
                    if ($this->isReadOnly() || $column->isReadOnly()) {
                        continue;
                    }

                    throw new \Exception(tr(
                        'Column "%0" cannot co-exist with "%1".',
                        $column->getEncodedHeader(),
                        $this->getEncodedHeader()
                    ));
                }
            }
        }

        if ($selfCount > 1) {
            throw new \Exception(tr('Column "%0:%1" found multiple times.', $this->permName, $this->mode));
        }
    }

    public function withWrappedRenderTransform(callable $callback)
    {
        $column = new self($this->permName, $this->mode);
        $column->label = $this->label;
        $column->isPrimary = $this->isPrimary;
        $column->isReadOnly = $this->isReadOnly;
        $column->isExportOnly = $this->isExportOnly;
        $column->isUniqueKey = $this->isUniqueKey;
        $column->displayAlign = $this->displayAlign;
        $column->parseIntoTransform = $this->parseIntoTransform;
        $column->querySources = $this->querySources;
        $column->incompatibilities = $this->incompatibilities;
        $column->plainReplacement = $this->plainReplacement;

        $column->renderTransform = function () use ($callback) {
            $value = call_user_func_array($this->renderTransform, func_get_args());

            return $callback($value);
        };

        return $column;
    }
}
