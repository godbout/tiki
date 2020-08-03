<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

require_once('lib/wizard/wizard.php');

/**
 * The Wizard's first page and frame handler
 */
class UserWizard extends Wizard
{
    public function pageTitle()
    {
        return tra('Welcome to the User Wizard');
    }

    public function isEditable()
    {
        return false;
    }

    public function onSetupPage($homepageUrl)
    {
        global $TWV;
        $smarty = TikiLib::lib('smarty');
        // Run the parent first
        parent::onSetupPage($homepageUrl);

        $smarty->assign('tikiMajorVersion', substr($TWV->version, 0, 2));

        // Assign the page template
        $smarty->assign('pageTitle', $this->pageTitle());

        return true;
    }

    public function getTemplate()
    {
        $wizardTemplate = 'wizard/user_wizard.tpl';

        return $wizardTemplate;
    }

    public function onContinue($homepageUrl)
    {
        // Run the parent first
        parent::onContinue($homepageUrl);
    }
}
