<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

//this script may only be included - so its better to die if called directly.
if (strpos($_SERVER["SCRIPT_NAME"], basename(__FILE__)) !== false) {
    header("location: index.php");
    exit;
}

class NotificationLib extends TikiLib
{
    public function list_mail_events($offset, $maxRecords, $sort_mode, $find)
    {
        if ($find) {
            $findesc = '%' . $find . '%';
            $mid = " where (`event` like ? or `email` like ?)";
            $bindvars = [$findesc, $findesc];
        } else {
            $mid = " ";
            $bindvars = [];
        }

        $query = "select * from `tiki_user_watches` $mid order by " . $this->convertSortMode($sort_mode);
        $query_cant = "select count(*) from `tiki_user_watches` $mid";
        $result = $this->query($query, $bindvars, $maxRecords, $offset);
        $cant = $this->getOne($query_cant, $bindvars);
        $ret = [];

        while ($res = $result->fetchRow()) {
            $ret[] = $res;
        }

        $retval = [];
        $retval["data"] = $ret;
        $retval["cant"] = $cant;

        return $retval;
    }

    public function update_mail_address($user, $oldMail, $newMail)
    {
        $query = "update `tiki_user_watches` set `email`=? where `user`=? and `email`=?";
        $result = $this->query($query, [$user, $newMail, $oldMail]);
    }

    public function get_mail_events($event, $object)
    {
        global $tikilib;
        $objectlib = TikiLib::lib('object');
        $query = "select * from `tiki_user_watches` where `event`=? and (`object`=? or `object`='*')";
        $result = $this->query($query, [$event, $object]);
        $ret = [];
        $map = ObjectLib::map_object_type_to_permission();
        while ($res = $result->fetchRow()) {
            if (empty($res['user']) || $tikilib->user_has_perm_on_object($res['user'], $object, $res['type'], $map[$res['type']])) {
                $ret[] = $res['email'];
            }
        }

        return $ret;
    }

    /**
     * Returns an array of notification types
     *
     * @param boolean $checkPermission If enabled, only return types for which the user has the permission needed so that they are effective.
     * @return A string-indexed bidimensional array of watch types. The first index is the watch event name.
     *   Second-level array are also string-indexed with elements label (description of the event),
     *   type (usually the type of watched objects) and url (a relevant script to access when an event happens, if any).
     */
    public function get_global_watch_types($checkPermission = false)
    {
        global $prefs, $tiki_p_admin, $tiki_p_admin_file_galleries;
        $watches['user_registers'] = [
                'label' => tra('A user registers') ,
                'type' => 'users',
                'url' => 'tiki-adminusers.php',
                'available' => $prefs['allowRegister'] == 'y',
                'permission' => $tiki_p_admin == 'y'
        ];

        $watches['article_submitted'] = [
                'label' => tra('A user submits an article') ,
                'type' => 'article',
                'url' => 'tiki-list_submissions.php',
                'available' => $prefs['feature_articles'] == 'y'
        ];

        $watches['article_edited'] = [
                'label' => tra('A user edits an article') ,
                'type' => 'article',
                'url' => 'tiki-list_articles.php',
                'available' => $prefs['feature_articles'] == 'y'
        ];

        $watches['article_deleted'] = [
                'label' => tra('A user deletes an article') ,
                'type' => 'article',
                'url' => 'tiki-list_submissions.php',
                'available' => $prefs['feature_articles'] == 'y'
        ];

        $watches['article_*'] = [
                'label' => tra('An article is submitted, edited, deleted or commented on.') ,
                'type' => 'article',
                'url' => '',
                'available' => $prefs['feature_articles'] == 'y'
        ];

        $watches['blog_post'] = [
                'label' => tra('A new blog post is published') ,
                'type' => 'blog',
                'url' => '',
                'available' => $prefs['feature_blogs'] == 'y',
                'object' => '*'
        ];// Blog comment mail
        $watches['blog_comment_changes'] = [
            'label' => tra('A blog post comment is posted or edited') ,
            'type' => 'blog',
            'url' => '',
            'available' => $prefs['feature_blogs'] == 'y',
            'object' => '*'
        ];

        $watches['wiki_page_changes'] = [
                'label' => tra('A wiki page is created, deleted or edited, except for minor changes.') ,
                'type' => 'wiki page',
                'url' => 'tiki-lastchanges.php',
                'available' => $prefs['feature_wiki'] == 'y'
        ];

        $watches['wiki_page_changes_incl_minor'] = [
                'label' => tra('A wiki page is created, deleted or edited, even for minor changes.') ,
                'type' => 'wiki page',
                'url' => 'tiki-lastchanges.php',
                'available' => $prefs['feature_wiki'] == 'y'
        ];

        $watches['wiki_comment_changes'] = [
                'label' => tra('A wiki page comment is posted or edited') ,
                'type' => 'wiki page',
                'url' => '',
                'available' => $prefs['feature_wiki'] == 'y' && $prefs['feature_wiki_comments'] == 'y'
        ];

        $watches['article_commented'] = [
                'label' => tra('An article comment is posted or edited') ,
                'type' => 'article',
                'url' => '',
                'available' => $prefs['feature_articles'] == 'y' && $prefs['feature_article_comments'] == 'y'
        ];

        $watches['fgal_quota_exceeded'] = [
                'label' => tra('File gallery quota exceeded') ,
                'type' => 'file gallery',
                'url' => '',
                'available' => $prefs['feature_file_galleries'] == 'y',
                'permission' => $tiki_p_admin == 'y'
        ];

        $watches['auth_token_called'] = [
                'label' => tra('Token is called') ,
                'type' => 'security',
                'url' => '',
                'object' => '*'
        ];

        $watches['user_joins_group'] = [
                'label' => tra('User joins a group') ,
                'type' => 'users',
                'url' => '',
                'object' => '*'
        ];

        $watches['thread_comment_replied'] = [
                'label' => tra('User\'s comment is replied') ,
                'type' => 'comment',
                'url' => '',
                'object' => '*'
        ];

        $watches['category_changed_in_lang'] = [
            'label' => tr('Category change in a language') ,
            'type' => '',
            'url' => '',
            'available' => $prefs['feature_user_watches_languages'] == 'y',
        ];

        $watches['wiki_page_in_lang_created'] = [
            'label' => tr('A new page is created in a language') ,
            'type' => 'wiki page',
            'url' => 'tiki-user_watches.php',
            'available' => $prefs['feature_user_watches_translations'] == 'y',
        ];

        foreach ($watches as $key => $watch) {
            if (array_key_exists('available', $watch) && ! $watch['available']) {
                unset($watches[$key]);
            } else {
                $watches[$key]['object'] = '*';
                unset($watches['available']);
                if ($checkPermission && array_key_exists('permission', $watch) && ! $watch['permission']) {
                    unset($watches[$key]);
                }
            }
        }

        return $watches;
    }
}
