<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tracker\Filter\Control;

class DropDown implements Control
{
    private $fieldName;
    private $options;
    private $value = '';

    public function __construct($name, array $options)
    {
        $this->fieldName = $name;
        $this->options = $options;
    }

    public function applyInput(\JitFilter $input)
    {
        $this->value = $input->{$this->fieldName}->text();
    }

    public function getQueryArguments()
    {
        if ($this->value) {
            return [$this->fieldName => $this->value];
        }

        return [];
    }

    public function getDescription()
    {
        if ($this->value) {
            return $this->options[$this->value];
        }
    }

    public function getId()
    {
        return $this->fieldName;
    }

    public function isUsable()
    {
        return count($this->options) > 0;
    }

    public function hasValue()
    {
        return ! empty($this->value);
    }

    public function getValue()
    {
        return $this->value;
    }

    public function __toString()
    {
        $smarty = \TikiLib::lib('smarty');
        $smarty->assign('control', [
            'field' => $this->fieldName,
            'options' => $this->options,
            'value' => $this->value,
        ]);

        return $smarty->fetch('filter_control/drop_down.tpl');
    }
}
