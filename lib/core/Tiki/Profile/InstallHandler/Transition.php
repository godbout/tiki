<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

use Symfony\Component\Yaml\Yaml;

class Tiki_Profile_InstallHandler_Transition extends Tiki_Profile_InstallHandler
{
    public function getData()
    {
        if ($this->data) {
            return $this->data;
        }

        $defaults = ['preserve' => 'n', 'guards' => []];

        $data = array_merge($defaults, $this->obj->getData());

        foreach ($data['guards'] as & $guard) {
            if (is_string($guard[2])) {
                $guard[2] = reset(Yaml::parse("- " . $guard[2]));
            }
        }

        $data = Tiki_Profile::convertYesNo($data);

        return $this->data = $data;
    }

    public function canInstall()
    {
        $data = $this->getData();
        if (! isset($data['type'], $data['name'], $data['from'], $data['to'])) {
            return false;
        }
        if (! is_array($data['guards'])) {
            return false;
        }

        return true;
    }

    public function _install()
    {
        require_once 'lib/transitionlib.php';

        $data = $this->getData();

        $this->replaceReferences($data);

        $transitionlib = new TransitionLib($data['type']);
        $id = $transitionlib->addTransition($data['from'], $data['to'], $data['name'], $data['preserve'] == 'y', $data['guards']);

        return $id;
    }
}
