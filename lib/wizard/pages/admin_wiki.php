<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

require_once('lib/wizard/wizard.php');

/**
 * Set up the wiki settings
 */
class AdminWizardWiki extends Wizard
{
    public function pageTitle()
    {
        return tra('Set up Wiki environment');
    }
    public function isEditable()
    {
        return true;
    }

    public function onSetupPage($homepageUrl)
    {
        global $prefs;
        // Run the parent first
        parent::onSetupPage($homepageUrl);

        return true;
    }

    public function getTemplate()
    {
        $wizardTemplate = 'wizard/admin_wiki.tpl';

        return $wizardTemplate;
    }

    public function onContinue($homepageUrl)
    {
        global $tikilib;

        // Run the parent first
        parent::onContinue($homepageUrl);

        // Configure detail preferences in own page
    }
}
