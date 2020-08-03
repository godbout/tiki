<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

require_once('lib/wizard/wizard.php');

/**
 * The Admin Profiles last screen
 */
class AdminWizardProfilesCompleted extends Wizard
{
    public function pageTitle()
    {
        return tra('Configuration Profiles Wizard Completed!');
    }
    public function isEditable()
    {
        return false;
    }

    public function onSetupPage($homepageUrl)
    {
        // Run the parent first
        parent::onSetupPage($homepageUrl);

        return true;
    }

    public function getTemplate()
    {
        $wizardTemplate = 'wizard/profiles_completed.tpl';

        return $wizardTemplate;
    }

    public function onContinue($homepageUrl)
    {
        // Run the parent first
        parent::onContinue($homepageUrl);
    }
}
