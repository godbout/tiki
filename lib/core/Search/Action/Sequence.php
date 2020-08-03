<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class Search_Action_Sequence
{
    private $name;
    private $steps = [];
    private $fields = [];
    private $requiredGroup;

    public function __construct($name)
    {
        $this->name = $name;
    }

    public function setRequiredGroup($groupName)
    {
        $this->requiredGroup = $groupName;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getFields()
    {
        return $this->fields;
    }

    public function isAllowed(array $groups)
    {
        return empty($this->requiredGroup) || in_array($this->requiredGroup, $groups);
    }

    public function addStep(Search_Action_Step $step)
    {
        $this->steps[] = $step;
        $this->fields = array_merge($this->fields, $step->getFields());
    }

    public function execute(array $entry)
    {
        foreach ($this->steps as $step) {
            if (! $step->validate($entry)) {
                return false;
            }
        }

        $success = true;
        foreach ($this->steps as $step) {
            $success = $step->execute($entry) && $success;
            if (method_exists($step, 'changeObject')) {
                $entry = $step->changeObject($entry);
            }
        }

        return $success;
    }

    public function requiresInput()
    {
        $params = [];

        foreach ($this->steps as $step) {
            $params[] = $step->requiresInput();
        }
        if (empty(array_filter($params))) {
            return false;
        }

        return json_encode($params);
    }

    public function getSteps()
    {
        return $this->steps;
    }
}
