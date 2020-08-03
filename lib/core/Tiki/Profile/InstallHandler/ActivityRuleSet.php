<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class Tiki_Profile_InstallHandler_ActivityRuleSet extends Tiki_Profile_InstallHandler
{
    private $rules = [];

    public function fetchData()
    {
        $data = $this->obj->getData();

        if (isset($data['rules']) && is_array($data['rules'])) {
            $this->rules = $data['rules'];
        }
    }

    public function canInstall()
    {
        $this->fetchData();

        return true;
    }

    public function _install()
    {
        $this->fetchData();
        $this->replaceReferences($this->rules);

        $activitylib = TikiLib::lib('activity');
        $activitylib->preserveRules($this->rules);

        return true;
    }

    public static function export($writer)
    {
        $activitylib = TikiLib::lib('activity');
        $rules = $activitylib->getRules();

        $ids = [];
        foreach ($rules as $rule) {
            if (Tiki_Profile_InstallHandler_ActivityStreamRule::export($writer, $rule)) {
                $ids[] = $rule['ruleId'];
            }
        }

        $writer->addObject(
            'activity_rule_set',
            'set',
            [
                'rules' => array_map(function ($id) use ($writer) {
                    return $writer->getReference('activity_stream_rule', $id);
                }, $ids),
            ]
        );

        return true;
    }
}
