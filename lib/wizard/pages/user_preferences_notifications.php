<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

require_once('lib/wizard/wizard.php');

/**
 * Set up the wysiwyg editor, including inline editing
 */
class UserWizardPreferencesNotifications extends Wizard
{
    public function pageTitle()
    {
        return tra('User Watches:') . ' ' . tra('Notification Preferences');
    }

    public function isEditable()
    {
        return true;
    }

    public function isVisible()
    {
        global	$prefs;

        return $prefs['feature_user_watches'] === 'y';
    }

    public function onSetupPage($homepageUrl)
    {
        global	$user, $prefs;

        $tikilib = TikiLib::lib('tiki');
        $smarty = TikiLib::lib('smarty');

        // Run the parent first
        parent::onSetupPage($homepageUrl);

        $showPage = false;

        // Show if option is selected
        if ($prefs['feature_user_watches'] === 'y') {
            $showPage = true;
        }

        // Setup initial wizard screen
        $smarty->assign('user_calendar_watch_editor', $tikilib->get_user_preference($user, 'user_calendar_watch_editor'));
        $smarty->assign('user_article_watch_editor', $tikilib->get_user_preference($user, 'user_article_watch_editor'));
        $smarty->assign('user_wiki_watch_editor', $tikilib->get_user_preference($user, 'user_wiki_watch_editor'));
        $smarty->assign('user_blog_watch_editor', $tikilib->get_user_preference($user, 'user_blog_watch_editor'));
        $smarty->assign('user_tracker_watch_editor', $tikilib->get_user_preference($user, 'user_tracker_watch_editor'));
        $smarty->assign('user_comment_watch_editor', $tikilib->get_user_preference($user, 'user_comment_watch_editor'));
        $smarty->assign('user_category_watch_editor', $tikilib->get_user_preference($user, 'user_category_watch_editor'));
        $smarty->assign('user_plugin_approval_watch_editor', $tikilib->get_user_preference($user, 'user_plugin_approval_watch_editor'));

        return $showPage;
    }

    public function getTemplate()
    {
        $wizardTemplate = 'wizard/user_preferences_notifications.tpl';

        return $wizardTemplate;
    }

    public function onContinue($homepageUrl)
    {
        global $user, $prefs;

        $tikilib = TikiLib::lib('tiki');

        // Run the parent first
        parent::onContinue($homepageUrl);

        if (isset($_REQUEST['user_calendar_watch_editor']) && $_REQUEST['user_calendar_watch_editor'] == 'on') {
            $tikilib->set_user_preference($user, 'user_calendar_watch_editor', 'y');
        } else {
            $tikilib->set_user_preference($user, 'user_calendar_watch_editor', 'n');
        }

        if (isset($_REQUEST['user_article_watch_editor']) && $_REQUEST['user_article_watch_editor'] == 'on') {
            $tikilib->set_user_preference($user, 'user_article_watch_editor', 'y');
        } else {
            $tikilib->set_user_preference($user, 'user_article_watch_editor', 'n');
        }

        if (isset($_REQUEST['user_wiki_watch_editor']) && $_REQUEST['user_wiki_watch_editor'] == 'on') {
            $tikilib->set_user_preference($user, 'user_wiki_watch_editor', 'y');
        } else {
            $tikilib->set_user_preference($user, 'user_wiki_watch_editor', 'n');
        }

        if (isset($_REQUEST['user_blog_watch_editor']) && $_REQUEST['user_blog_watch_editor'] == 'on') {
            $tikilib->set_user_preference($user, 'user_blog_watch_editor', 'y');
        } else {
            $tikilib->set_user_preference($user, 'user_blog_watch_editor', 'n');
        }

        if (isset($_REQUEST['user_tracker_watch_editor']) && $_REQUEST['user_tracker_watch_editor'] == 'on') {
            $tikilib->set_user_preference($user, 'user_tracker_watch_editor', 'y');
        } else {
            $tikilib->set_user_preference($user, 'user_tracker_watch_editor', 'n');
        }

        if (isset($_REQUEST['user_comment_watch_editor']) && $_REQUEST['user_comment_watch_editor'] == 'on') {
            $tikilib->set_user_preference($user, 'user_comment_watch_editor', 'y');
        } else {
            $tikilib->set_user_preference($user, 'user_comment_watch_editor', 'n');
        }

        if (isset($_REQUEST['user_category_watch_editor']) && $_REQUEST['user_category_watch_editor'] == 'on') {
            $tikilib->set_user_preference($user, 'user_category_watch_editor', 'y');
        } else {
            $tikilib->set_user_preference($user, 'user_category_watch_editor', 'n');
        }

        if (isset($_REQUEST['user_plugin_approval_watch_editor']) && $_REQUEST['user_plugin_approval_watch_editor'] == 'on') {
            $tikilib->set_user_preference($user, 'user_plugin_approval_watch_editor', 'y');
        } else {
            $tikilib->set_user_preference($user, 'user_plugin_approval_watch_editor', 'n');
        }
    }
}
