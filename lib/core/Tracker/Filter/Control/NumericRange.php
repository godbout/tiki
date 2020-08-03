<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tracker\Filter\Control;

class NumericRange implements Control
{
    private $fieldName;
    private $from = '';
    private $to = '';
    private $decimals = 0;

    public function __construct($name, $decimals)
    {
        $this->fieldName = $name;
        $this->decimals = $decimals;
    }

    public function applyInput(\JitFilter $input)
    {
        $this->from = $input->{$this->fieldName . '_from'}->float() ?: '';
        $this->to = $input->{$this->fieldName . '_to'}->float() ?: '';
    }

    public function getQueryArguments()
    {
        if ($this->from && $this->to) {
            return [
                $this->fieldName . '_from' => $this->from,
                $this->fieldName . '_to' => $this->to,
            ];
        }

        return [];
    }

    public function getDescription()
    {
        if ($this->hasValue()) {
            $tikilib = \TikiLib::lib('tiki');

            return tr(
                'From %0 to %1',
                $this->from,
                $this->to
            );
        }

        return '';
    }

    public function getId()
    {
        return $this->fieldName . '_from';
    }

    public function isUsable()
    {
        return true;
    }

    public function hasValue()
    {
        return ! empty($this->from) && ! empty($this->to);
    }

    public function getFrom()
    {
        return $this->from;
    }

    public function getTo()
    {
        return $this->to;
    }

    public function __toString()
    {
        $smarty = \TikiLib::lib('smarty');
        $smarty->assign('control', [
            'field' => $this->fieldName,
            'from' => $this->from,
            'to' => $this->to,
            'step' => ($this->decimals > 0 ? 1 / pow(10, $this->decimals) : 0),
        ]);

        return $smarty->fetch('filter_control/numeric_range.tpl');
    }
}
