<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tiki\MailIn\Provider;

use Tiki\MailIn\Action;

class BasicWikiProvider implements ProviderInterface
{
    private $type;
    private $label;
    private $class;

    public function __construct($type, $label, $class)
    {
        $this->type = $type;
        $this->label = $label;
        $this->class = $class;
    }

    public function isEnabled()
    {
        global $prefs;

        return $prefs['feature_wiki'] == 'y';
    }

    public function getType()
    {
        return $this->type;
    }

    public function getLabel()
    {
        /* Catch strings
        tr('Create or update wiki page')
        tr('Send page to user')
        tr('Append to wiki page')
        tr('Prepend to wiki page')
        */
        return tr($this->label);
    }

    public function getActionFactory(array $acc)
    {
        $wikiParams = [
            'namespace' => $acc['namespace'],
            'structure_routing' => $acc['routing'] == 'y',
        ];

        return new Action\DirectFactory($this->class, $wikiParams);
    }
}
