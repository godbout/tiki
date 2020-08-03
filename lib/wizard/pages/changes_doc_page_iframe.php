<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

require_once('lib/wizard/wizard.php');

/**
 * The Wizard's language handler
 */
class ChangesWizardDocPageIframe extends Wizard
{
    public function pageTitle()
    {
        return tra('Related doc.tiki.org pages');
    }

    public function isEditable()
    {
        return false;
    }

    public function onSetupPage($homepageUrl)
    {
        // Run the parent first
        parent::onSetupPage($homepageUrl);

        $showPage = true;

        return $showPage;
    }

    public function getTemplate()
    {
        $wizardTemplate = 'wizard/changes_doc_page_iframe.tpl';

        return $wizardTemplate;
    }

    public function onContinue($homepageUrl)
    {
        // Run the parent first
        parent::onContinue($homepageUrl);
    }
}
