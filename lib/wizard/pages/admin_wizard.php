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
class AdminWizard extends Wizard
{
    public function pageTitle()
    {
        return tra('Tiki Setup');
    }

    public function isEditable()
    {
        return false;
    }


    public function onSetupPage($homepageUrl)
    {
        $smarty = TikiLib::lib('smarty');
        // Run the parent first
        parent::onSetupPage($homepageUrl);

        // If the user steps back after having selected, "Use defaults",
        //	then starts the wizard steps (presses "Start"), the default prefs should no longer be used.
        if (isset($_REQUEST['use-default-prefs'])) {
            $smarty->clear_assign('useDefaultPrefs');
        }

        // If the user steps back after having selected, "Use Changes Wizard",
        //	then starts the wizard steps (presses "Start"), the Changes Wizard should no longer be used.
        if (isset($_REQUEST['use-changes-wizard'])) {
            $smarty->clear_assign('useChangesWizard');
        }

        // Assign the page template
        $smarty->assign('pageTitle', $this->pageTitle());

        return true;
    }

    public function getTemplate()
    {
        $wizardTemplate = 'wizard/admin_wizard.tpl';

        return $wizardTemplate;
    }

    public function onContinue($homepageUrl)
    {
        // Run the parent first
        parent::onContinue($homepageUrl);

        $wizardlib = TikiLib::lib('wizard');

        // User selected to skip the wizard and hide it on login
        //	Save the "Show on login" setting, and no other preferences
        //	Set preference to hide on login
        if (isset($_REQUEST['skip'])) {
            // Save "Show on login" setting
            $showOnLogin = false;
            $wizardlib->showOnLogin($showOnLogin);

            //	Then exit, by returning the specified URL
            $accesslib = TikiLib::lib('access');
            $accesslib->redirect($homepageUrl);
        }
    }
}
