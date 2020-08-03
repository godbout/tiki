<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

//this script may only be included - so its better to die if called directly.
if (strpos($_SERVER['SCRIPT_NAME'], basename(__FILE__)) !== false) {
    header('location: index.php');
    exit;
}

if (! defined('PLUGINS_DIR')) {
    define('PLUGINS_DIR', 'lib/wiki-plugins');
}


class WikiLib extends TikiLib
{

    //Special parsing for multipage articles
    public function get_number_of_pages($data)
    {
        global $prefs;
        // Temporary remove <PRE></PRE> secions to protect
        // from broke <PRE> tags and leave well known <PRE>
        // behaviour (i.e. type all text inside AS IS w/o
        // any interpretation)
        $preparsed = [];

        preg_match_all("/(<[Pp][Rr][Ee]>)(.*?)(<\/[Pp][Rr][Ee]>)/s", $data, $preparse);
        $idx = 0;

        foreach (array_unique($preparse[2]) as $pp) {
            $key = md5($this->genPass());

            $aux['key'] = $key;
            $aux['data'] = $pp;
            $preparsed[] = $aux;
            $data = str_replace($preparse[1][$idx] . $pp . $preparse[3][$idx], $key, $data);
            $idx = $idx + 1;
        }

        $parts = explode($prefs['wiki_page_separator'], $data);

        return count($parts);
    }

    public function get_page($data, $i)
    {
        // Get slides
        global $prefs;
        $parts = explode($prefs['wiki_page_separator'], $data);
        $ret = $parts[$i - 1];

        if (substr($parts[$i - 1], 1, 5) == '<br/>') {
            $ret = substr($parts[$i - 1], 6);
        }

        if (substr($parts[$i - 1], 1, 6) == '<br />') {
            $ret = substr($parts[$i - 1], 7);
        }

        return $ret;
    }

    public function get_page_by_slug($slug)
    {
        $pages = TikiDb::get()->table('tiki_pages');
        $found = $pages->fetchOne('pageName', ['pageSlug' => $slug]);

        if ($found) {
            return $found;
        }

        if (function_exists('utf8_encode')) {
            $slug_utf8 = utf8_encode($slug);
            if ($slug != $slug_utf8) {
                $found = $pages->fetchOne('pageName', ['pageSlug' => $slug_utf8]);
                if ($found) {
                    return $found;
                }
            }
        }

        return $slug;
    }

    /**
     * Return a Slug, if set, or the page name supplied as result
     *
     * @param string $page
     * @return string
     */
    public function get_slug_by_page($page)
    {
        $pages = TikiDb::get()->table('tiki_pages');
        $slug = $pages->fetchOne('pageSlug', ['pageName' => $page]);
        if ($slug) {
            return $slug;
        }

        return $page;
    }

    public function get_creator($name)
    {
        return $this->getOne('select `creator` from `tiki_pages` where `pageName`=?', [$name]);
    }

    /**
     * Get the contributors for page
     * the returned array does not contain the user $last (usually the current or last user)
     * @param mixed $page
     * @param mixed $last
     */
    public function get_contributors($page, $last = '')
    {
        static $cache_page_contributors;
        if ($cache_page_contributors['page'] == $page) {
            if (empty($last)) {
                return $cache_page_contributors['contributors'];
            }
            $ret = [];
            if (is_array($cache_page_contributors['contributors'])) {
                foreach ($cache_page_contributors['contributors'] as $res) {
                    if (isset($res['user']) && $res['user'] != $last) {
                        $ret[] = $res;
                    }
                }
            }

            return $ret;
        }

        $query = 'select `user`, MAX(`version`) as `vsn` from `tiki_history` where `pageName`=? group by `user` order by `vsn` desc';
        // jb fixed 110115 - please check intended behaviour remains
        // was: $query = "select `user` from `tiki_history` where `pageName`=? group by `user` order by MAX(`version`) desc";
        $result = $this->query($query, [$page]);
        $cache_page_contributors = [];
        $cache_page_contributors['contributors'] = [];
        $ret = [];

        while ($res = $result->fetchRow()) {
            if ($res['user'] != $last) {
                $ret[] = $res['user'];
            }
            $cache_page_contributors['contributors'][] = $res['user'];
        }
        $cache_page_contributors['page'] = $page;

        return $ret;
    }

    // Returns all pages that links from here or to here, without distinction
    // This is used by wiki mindmap, to make the graph
    public function wiki_get_neighbours($page)
    {
        $neighbours = [];
        $already = [];

        $query = "select `toPage` from `tiki_links` where `fromPage`=? and `fromPage` not like 'objectlink:%'";
        $result = $this->query($query, [$page]);
        while ($row = $result->fetchRow(DB_FETCHMODE_ASSOC)) {
            $neighbour = $row['toPage'];
            $neighbours[] = $neighbour;
            $already[$neighbour] = 1;
        }

        $query = "select `fromPage` from `tiki_links` where `toPage`=? and `fromPage` not like 'objectlink:%'";
        $result = $this->query($query, [$page]);
        while ($row = $result->fetchRow(DB_FETCHMODE_ASSOC)) {
            $neighbour = $row['fromPage'];
            if (! isset($already[$neighbour])) {
                $neighbours[] = $neighbour;
            }
        }

        return $neighbours;
    }

    // Returns a string containing all characters considered bad in page names
    public function get_badchars()
    {
        return "/?#[]@$&+;=<>";
    }

    // Returns a boolean indicating whether the given page name contains "bad characters"
    // See http://dev.tiki.org/Bad+characters
    public function contains_badchars($name)
    {
        if (preg_match('/^tiki\-(\w+)\-(\w+)$/', $name)) {
            return true;
        }

        $badchars = $this->get_badchars();
        $badchars = preg_quote($badchars, '/');

        return preg_match("/[$badchars]/", $name);
    }

    public function remove_badchars($page)
    {
        if ($this->contains_badchars($page)) {
            $badChars = $this->get_badchars();

            // Replace bad characters with a '_'
            $iStrlenBadChars = strlen($badChars);
            for ($j = 0; $j < $iStrlenBadChars; $j++) {
                $char = $badChars[$j];
                $page = str_replace($char, "_", $page);
            }
        }

        return $page;
    }

    /**
     * Duplicate an existing page
     *
     * @param string $name
     * @param string $copyName
     * @param mixed $dupCateg
     * @param mixed $dupTags
     * @return bool
     */
    public function wiki_duplicate_page($name, $copyName = null, $dupCateg = true, $dupTags = true)
    {
        global $user;
        global $prefs;

        $tikilib = TikiLib::lib('tiki');
        $userlib = TikiLib::lib('user');
        $globalperms = Perms::get();

        $info = $tikilib->get_page_info($name);
        $ip = $tikilib->get_ip_address();
        $version = $info['version'];
        $comment = tr("Initial content copied from version %0 of page %1", $version, $name);

        if (! $info) {
            return false;
        }

        if (! $copyName) {
            $copyName = $name . ' (' . $tikilib->now . ')';
        }

        $copyPage = $tikilib->create_page(
            $copyName,
            0,
            $info['data'],
            $tikilib->now,
            $comment,
            $user,
            $ip,
            $info['description'],
            $info['lang'],
            $info['is_html']
        );

        if ($copyPage) {
            $warnings = [];
            if ($dupCateg && $prefs['feature_categories'] === 'y') {
                if ($globalperms->modify_object_categories) {
                    $categlib = TikiLib::lib('categ');
                    $categories = $categlib->get_object_categories('wiki page', $name);
                    Perms::bulk([ 'type' => 'category' ], 'object', $categories);

                    foreach ($categories as $catId) {
                        $perms = Perms::get([ 'type' => 'category', 'object' => $catId]);

                        if ($perms->add_object) {
                            $categlib->categorizePage($copyName, $catId);
                        } else {
                            $warnings[] = tr("You don't have permission to use category '%0'.", $categlib->get_category_name($catId));
                        }
                    }
                } else {
                    $warnings[] = tr("You don't have permission to edit categories.");
                }
            }

            if ($dupTags && $prefs['feature_freetags'] === 'y') {
                if ($globalperms->freetags_tag) {
                    $freetaglib = TikiLib::lib('freetag');
                    $freetags = $freetaglib->get_tags_on_object($name, 'wiki page');

                    foreach ($freetags['data'] as $tag) {
                        $freetaglib->tag_object($user, $copyName, 'wiki page', $tag['tag']);
                    }
                } else {
                    $warnings[] = tr("You don't have permission to edit tags.");
                }
            }

            if (count($warnings) > 0) {
                Feedback::warning(['mes' => $warnings]);
            }
        }

        return $copyPage;
    }

    // This method renames a wiki page
    // If you think this is easy you are very very wrong
    public function wiki_rename_page($oldName, $newName, $renameHomes = true, $user = '')
    {
        global $prefs;
        $tikilib = TikiLib::lib('tiki');
        // if page already exists, stop here
        $newName = trim($newName);
        if ($this->get_page_info($newName, false, true)) {
            // if it is a case change of same page: allow it, else stop here
            if (strcasecmp(trim($oldName), $newName) <> 0) {
                throw new Exception("Page already exists", 2);
            }
        }

        if ($this->contains_badchars($newName) && $prefs['wiki_badchar_prevent'] == 'y') {
            throw new Exception("Bad characters", 1);
        }

        // The pre- and post-tags are eating away the max usable page name length
        //	Use ~ instead of Tmp. Shorter
        // $tmpName = "TmP".$newName."TmP";
        $tmpName = "~" . $newName . "~";

        // 1st rename the page in tiki_pages, using a tmpname inbetween for
        // rename pages like ThisTestpage to ThisTestPage
        $query = 'update `tiki_pages` set `pageName`=?, `pageSlug`=NULL where `pageName`=?';
        $this->query($query, [ $tmpName, $oldName ]);

        $slug = TikiLib::lib('slugmanager')->generate($prefs['wiki_url_scheme'], $newName, $prefs['url_only_ascii'] === 'y');
        $query = 'update `tiki_pages` set `pageName`=?, `pageSlug`=? where `pageName`=?';
        $this->query($query, [ $newName, $slug, $tmpName ]);

        // correct pageName in tiki_history, using a tmpname inbetween for
        // rename pages like ThisTestpage to ThisTestPage
        $query = 'update `tiki_history` set `pageName`=? where `pageName`=?';
        $this->query($query, [ $tmpName, $oldName ]);

        $query = 'update `tiki_history` set `pageName`=? where `pageName`=?';
        $this->query($query, [ $newName, $tmpName ]);

        // get pages linking to the old page
        $query = 'select `fromPage` from `tiki_links` where `toPage`=?';
        $result = $this->query($query, [ $oldName ]);

        $linksToOld = [];
        while ($res = $result->fetchRow()) {
            $linkOrigin = $res['fromPage'];
            $linksToOld[] = $linkOrigin;
            $is_wiki_page = substr($linkOrigin, 0, 11) != 'objectlink:';
            if (! $is_wiki_page) {
                $objectlinkparts = explode(':', $linkOrigin);
                $type = $objectlinkparts[1];
                // $objectId can contain :, so consider the remaining string
                $objectId = substr($linkOrigin, strlen($type) + 12);
            }

            $parserlib = TikiLib::lib('parser');

            if ($is_wiki_page) {
                $info = $this->get_page_info($linkOrigin);
                //$data=addslashes(str_replace($oldName,$newName,$info['data']));
                $data = $parserlib->replace_links($info['data'], $oldName, $newName);
                $query = "update `tiki_pages` set `data`=?,`page_size`=? where `pageName`=?";
                $this->query($query, [ $data, (int) strlen($data), $linkOrigin]);
                $this->invalidate_cache($linkOrigin);
            } elseif ($type == 'forum post' || substr($type, -7) == 'comment') {
                $comment_info = TikiLib::lib('comments')->get_comment($objectId);
                $data = $parserlib->replace_links($comment_info['data'], $oldName, $newName);
                $query = "update `tiki_comments` set `data`=? where `threadId`=?";
                $this->query($query, [ $data, $objectId]);
            } elseif ($type == 'article') {
                $info = TikiLib::lib('art')->get_article($objectId);
                $heading = $parserlib->replace_links($info['heading'], $oldName, $newName);
                $body = $parserlib->replace_links($info['body'], $oldName, $newName);
                $query = "update `tiki_articles` set `heading`=?, `body`=? where `articleId`=?";
                $this->query($query, [ $heading, $body, $objectId]);
            } elseif ($type == 'post') {
                $info = TikiLib::lib('blog')->get_post($objectId);
                $data = $parserlib->replace_links($info['data'], $oldName, $newName);
                $query = "update `tiki_blog_posts` set `data`=? where `postId`=?";
                $this->query($query, [ $data, $objectId]);
            } elseif ($type == 'tracker') {
                $tracker_info = TikiLib::lib('trk')->get_tracker($objectId);
                $data = $parserlib->replace_links($tracker_info['description'], $oldName, $newName);
                $query = "update `tiki_trackers` set `description`=? where `trackerId`=?";
                $this->query($query, [ $data, $objectId]);
            } elseif ($type == 'trackerfield') {
                $field_info = TikiLib::lib('trk')->get_field_info($objectId);
                $data = $parserlib->replace_links($field_info['description'], $oldName, $newName);
                $query = "update `tiki_tracker_fields` set `description`=? where `fieldId`=?";
                $this->query($query, [ $data, $objectId]);
            } elseif ($type == 'trackeritemfield') {
                list($itemId, $fieldId) = explode(":", $objectId);
                $data = TikiLib::lib('trk')->get_item_value(null, (int)$itemId, (int)$fieldId);
                $data = $parserlib->replace_links($data, $oldName, $newName);
                $query = "update `tiki_tracker_item_fields` set `value`=? where `itemId`=? and `fieldId`=?";
                $this->query($query, [ $data, $itemId, $fieldId]);
            } elseif ($type == 'calendar event') {
                $event_info = TikiLib::lib('calendar')->get_item($objectId);
                $data = $parserlib->replace_links($event_info['description'], $oldName, $newName);
                $query = "update `tiki_calendar_items` set `description`=? where `calitemId`=?";
                $this->query($query, [ $data, $objectId ]);
            }
        }

        // correct toPage and fromPage in tiki_links
        // before update, manage to avoid duplicating index(es) when B is renamed to C while page(s) points to both C (not created yet) and B
        $query = 'select `fromPage` from `tiki_links` where `toPage`=?';
        $result = $this->query($query, [ $newName ]);
        $linksToNew = [];

        while ($res = $result->fetchRow()) {
            $linksToNew[] = $res['fromPage'];
        }

        if ($extra = array_intersect($linksToOld, $linksToNew)) {
            $query = 'delete from `tiki_links` where `fromPage` in (' . implode(',', array_fill(0, count($extra), '?')) . ') and `toPage`=?';
            $this->query($query, array_merge($extra, [$oldName]));
        }

        $query = 'update `tiki_links` set `fromPage`=? where `fromPage`=?';
        $this->query($query, [ $newName, $oldName]);

        $query = 'update `tiki_links` set `toPage`=? where `toPage`=?';
        $this->query($query, [ $newName, $oldName]);

        // Modify pages including the old page with Include plugin,
        // so that they include the new name
        $relationlib = TikiLib::lib('relation');
        $relations = $relationlib->get_relations_to('wiki page', $oldName, 'tiki.wiki.include');

        foreach ($relations as $relation) {
            $type = $relation['type'];
            if ($type == 'wiki page') {
                $page = $relation['itemId'];
                $info = $this->get_page_info($page);
                $data = [ $info['data'] ];
            } elseif ($type == 'forum post' || substr($type, -7) == 'comment') {
                $objectId = (int)$relation['itemId'];
                $comment_info = TikiLib::lib('comments')->get_comment($objectId);
                $data = [ $comment_info['data'] ];
            } elseif ($type == 'article') {
                $objectId = (int)$relation['itemId'];
                $info = TikiLib::lib('art')->get_article($objectId);
                $data = [ $info['heading'], $info['body'] ];
            } elseif ($type == 'post') {
                $objectId = (int)$relation['itemId'];
                $info = TikiLib::lib('blog')->get_post($objectId);
                $data = [ $info['data'] ];
            } elseif ($type == 'tracker') {
                $objectId = (int)$relation['itemId'];
                $tracker_info = TikiLib::lib('trk')->get_tracker($objectId);
                $data = [ $tracker_info['description'] ];
            } elseif ($type == 'trackerfield') {
                $objectId = (int)$relation['itemId'];
                $field_info = TikiLib::lib('trk')->get_field_info($objectId);
                $data = [ $field_info['description'] ];
            } elseif ($type == 'trackeritemfield') {
                $objectId = explode(":", $relation['itemId']);
                $data = [ TikiLib::lib('trk')->get_item_value(null, $objectId[0], $objectId[1]) ];
            } elseif ($type == 'calendar event') {
                $objectId = (int)$relation['itemId'];
                $data = [ TikiLib::lib('calendar')->get_item($objectId)['description'] ];
            } else {
                continue;
            }

            $modified = false;
            $matches = [];
            for ($i = 0; $i < sizeof($data); $i++) {
                $matches[] = WikiParser_PluginMatcher::match($data[$i]);
                $argParser = new WikiParser_PluginArgumentParser();
                foreach ($matches[$i] as $match) {
                    if ($match->getName() == 'include') {
                        $arguments = $argParser->parse($match->getArguments());
                        if ($arguments['page'] == $oldName) {
                            $arguments['page'] = $newName;
                            $match->replaceWithPlugin($match->getName(), $arguments, $match->getBody());
                            $modified = true;
                        }
                    }
                }
            }

            if ($modified) {
                if ($type == 'article') {
                    $heading = $matches[0]->getText();
                    $body = $matches[1]->getText();
                    $query = "update `tiki_articles` set `heading`=?, `body`=? where `articleId`=?";
                    $this->query($query, [ $heading, $body, $objectId]);

                    continue;
                }
                $data = $matches[0]->getText();
                
                if ($type == 'wiki page') {
                    $query = "update `tiki_pages` set `data`=?,`page_size`=? where `pageName`=?";
                    $this->query($query, [ $data, (int) strlen($data), $page]);
                    $this->invalidate_cache($page);
                } elseif ($type == 'forum post' || substr($type, -7) == 'comment') {
                    $query = "update `tiki_comments` set `data`=? where `threadId`=?";
                    $this->query($query, [ $data, $objectId]);
                } elseif ($type == 'post') {
                    $query = "update `tiki_blog_posts` set `data`=? where `postId`=?";
                    $this->query($query, [ $data, $objectId]);
                } elseif ($type == 'tracker') {
                    $query = "update `tiki_trackers` set `description`=? where `trackerId`=?";
                    $this->query($query, [ $data, $objectId]);
                } elseif ($type == 'trackerfield') {
                    $query = "update `tiki_tracker_fields` set `description`=? where `fieldId`=?";
                    $this->query($query, [ $data, $objectId]);
                } elseif ($type == 'trackeritemfield') {
                    $query = "update `tiki_tracker_item_fields` set `value`=? where `itemId`=? and `fieldId`=?";
                    $this->query($query, [ $data, $objectId[0], $objectId[1]]);
                } elseif ($type == 'calendar event') {
                    $query = "update `tiki_calendar_items` set `description`=? where `calitemId`=?";
                    $this->query($query, [ $data, $objectId ]);
                }
            }
        }

        // tiki_footnotes change pageName
        $query = 'update `tiki_page_footnotes` set `pageName`=? where `pageName`=?';
        $this->query($query, [ $newName, $oldName ]);

        // in tiki_categorized_objects update objId
        $newcathref = 'tiki-index.php?page=' . urlencode($newName);
        $query = 'update `tiki_objects` set `itemId`=?,`name`=?,`href`=? where `itemId`=? and `type`=?';
        $this->query($query, [ $newName, $newName, $newcathref, $oldName, 'wiki page']);

        $this->rename_object('wiki page', $oldName, $newName, $user);

        // update categories if new name has a category default
        $categlib = TikiLib::lib('categ');
        $categories = $categlib->get_object_categories('wiki page', $newName);
        $info = $this->get_page_info($newName);
        $categlib->update_object_categories($categories, $newName, 'wiki page', $info['description'], $newName, $newcathref);

        $query = 'update `tiki_wiki_attachments` set `page`=? where `page`=?';
        $this->query($query, [ $newName, $oldName ]);

        // group home page
        if ($renameHomes) {
            $query = 'update `users_groups` set `groupHome`=? where `groupHome`=?';
            $this->query($query, [ $newName, $oldName ]);
        }

        // copyright
        $query = 'update tiki_copyrights set `page`=? where `page`=?';
        $this->query($query, [ $newName, $oldName ]);

        //breadcrumb
        if (isset($_SESSION['breadCrumb']) && in_array($oldName, $_SESSION['breadCrumb'])) {
            $pos = array_search($oldName, $_SESSION["breadCrumb"]);
            $_SESSION['breadCrumb'][$pos] = $newName;
        }

        global $prefs;
        global $user;
        $tikilib = TikiLib::lib('tiki');
        $smarty = TikiLib::lib('smarty');
        if ($prefs['feature_use_fgal_for_wiki_attachments'] == 'y') {
            $query = 'update `tiki_file_galleries` set `name`=? where `name`=?';
            $this->query($query, [ $newName, $oldName ]);
        }

        // first get all watches for this page ...
        if ($prefs['feature_user_watches'] == 'y') {
            $nots = $tikilib->get_event_watches('wiki_page_changed', $oldName);
        }

        // ... then update the watches table
        // user watches
        $query = "update `tiki_user_watches` set `object`=?, `title`=?, `url`=? where `object`=? and `type` = 'wiki page'";
        $this->query($query, [ $newName, $newName, 'tiki-index.php?page=' . $newName, $oldName ]);
        $query = "update `tiki_group_watches` set `object`=?, `title`=?, `url`=? where `object`=? and `type` = 'wiki page'";
        $this->query($query, [ $newName, $newName, 'tiki-index.php?page=' . $newName, $oldName ]);

        // now send notification email to all on the watchlist:
        if ($prefs['feature_user_watches'] == 'y') {
            if (! isset($_SERVER["SERVER_NAME"])) {
                $_SERVER['SERVER_NAME'] = $_SERVER['HTTP_HOST'];
            }

            if (count($nots)) {
                include_once('lib/notifications/notificationemaillib.php');
                $smarty->assign('mail_site', $_SERVER['SERVER_NAME']);
                $smarty->assign('mail_oldname', $oldName);
                $smarty->assign('mail_newname', $newName);
                $smarty->assign('mail_user', $user);
                sendEmailNotification($nots, 'watch', 'user_watch_wiki_page_renamed_subject.tpl', $_SERVER['SERVER_NAME'], 'user_watch_wiki_page_renamed.tpl');
            }
        }

        require_once('lib/search/refresh-functions.php');
        refresh_index('pages', $oldName, false);
        refresh_index('pages', $newName);

        if ($renameHomes && $prefs['wikiHomePage'] == $oldName) {
            $tikilib->set_preference('wikiHomePage', $newName);
        }
        if ($prefs['feature_trackers'] == 'y') {
            $trklib = TikiLib::lib('trk');
            $trklib->rename_page($oldName, $newName);
        }

        return true;
    }

    /**
     * Gets a wiki content that has just been saved, parses it and stores relations
     * with wiki pages that will created from this content.
     *
     * If content is optionally wiki parsed, this function must be called even if content is not
     * to be parsed in this case, so that relations can be cleared.
     *
     * @param string $data wiki parsed content
     * @param string $objectType
     * @param string/int $itemId
     * @param boolean $wikiParsed Indicates if content is wiki parsed.
     */
    public function update_wikicontent_relations($data, $objectType, $itemId, $wikiParsed = true)
    {
        $parserlib = TikiLib::lib('parser');
        $relationlib = TikiLib::lib('relation');

        $relationlib->remove_relations_from($objectType, $itemId, 'tiki.wiki.include');

        if (! $wikiParsed) {
            return;
        }

        // Now create Plugin Include relations
        $includes = $parserlib->find_plugins($data, 'include');

        foreach ($includes as $include) {
            $page = $include['arguments']['page'];
            if (isset($page)) {
                $relationlib->add_relation('tiki.wiki.include', $objectType, $itemId, 'wiki page', $page);
            }
        }
    }

    /**
     * Very similar to update_wikicontent_relations, but for links.
     * Both should be merged. The only reason they are not is because
     * wiki pages have their own way of updating content links.
     *
     * @param string $data wiki parsed content
     * @param string $objectType
     * @param string/int $itemId
     * @param boolean $wikiParsed Indicates if content is wiki parsed.
     */
    public function update_wikicontent_links($data, $objectType, $itemId, $wikiParsed = true)
    {
        $parserlib = TikiLib::lib('parser');
        $tikilib = TikiLib::lib('tiki');

        // First get identifier for tiki_links table
        if ($objectType == 'wiki page') {
            $linkhandle = $itemId;
        } else {
            $linkhandle = "objectlink:$objectType:$itemId";
        }

        $tikilib->clear_links($linkhandle);

        if (! $wikiParsed) {
            return;
        }

        // Create tiki_links entries
        $pages = $parserlib->get_pages($data);
        foreach ($pages as $a_page) {
            $tikilib->replace_link($linkhandle, $a_page);
        }
    }

    /**
     * Checks all pages that include $page and parses its contents to
     * get a list of all Plugin Include calls.
     *
     * @param string $page
     * @return array list of associative arrays with (page, arguments, body) keys
     */
    public function get_external_includes($page)
    {
        $relationlib = TikiLib::lib('relation');
        $relations = $relationlib->get_relations_to('wiki page', $page, 'tiki.wiki.include');

        $objectlib = TikiLib::lib('object');

        $result = [];

        foreach ($relations as $relation) {
            $data = $objectlib->get_wiki_content($relation['type'], $relation['itemId']);
            $matches = WikiParser_PluginMatcher::match($data);
            $argParser = new WikiParser_PluginArgumentParser();

            $objectlib = TikiLib::lib('object');

            foreach ($matches as $match) {
                $arguments = $argParser->parse($match->getArguments());
                if ($match->getName() == 'include') {
                    $result[$relation['type'] . ':' . $relation['itemId']] = [
                        'type' => $relation['type'],
                        'itemId' => $relation['itemId'],
                        'start' => $arguments['start'],
                        'end' => $arguments['end']
                    ];
                }
            }
        }

        return array_values($result);
    }

    public function set_page_cache($page, $cache)
    {
        $query = 'update `tiki_pages` set `wiki_cache`=? where `pageName`=?';
        $this->query($query, [ $cache, $page]);
    }

    // TODO: huho why that function is empty ?
    public function save_notepad($user, $title, $data)
    {
    }

    // Methods to cache and handle the cached version of wiki pages
    // to prevent parsing large pages.
    public function get_cache_info($page)
    {
        $query = 'select `cache`,`cache_timestamp` from `tiki_pages` where `pageName`=?';

        $result = $this->query($query, [ $page ]);
        $res = $result->fetchRow();

        return $res;
    }

    public function get_parse($page, &$canBeRefreshed = false, $suppress_icons = false)
    {
        global $prefs, $user;
        $tikilib = TikiLib::lib('tiki');
        $headerlib = TikiLib::lib('header');
        $content = '';
        $canBeRefreshed = false;

        $info = $this->get_page_info($page);
        if (empty($info)) {
            return '';
        }

        $parse_options = [
            'is_html' => $info['is_html'],
            'language' => $info['lang'],
            'namespace' => $info['namespace'],
        ];

        if ($suppress_icons || (! empty($info['lockedby']) && $info['lockedby'] != $user)) {
            $parse_options['suppress_icons'] = true;
        }

        if ($prefs['wysiwyg_inline_editing'] === 'y' && getCookie('wysiwyg_inline_edit', "preview", false)) {
            $parse_options['ck_editor'] = true;
            $parse_options['suppress_icons'] = true;
        }

        $wiki_cache = ($prefs['feature_wiki_icache'] == 'y' && ! is_null($info['wiki_cache'])) ? $info['wiki_cache'] : $prefs['wiki_cache'];

        if ($wiki_cache > 0 && empty($_REQUEST['offset']) && empty($_REQUEST['itemId']) && (empty($user) || $prefs['wiki_cache'] == 0)) {
            $cache_info = $this->get_cache_info($page);
            if (! empty($cache_info['cache_timestamp']) && $cache_info['cache_timestamp'] + $wiki_cache >= $this->now) {
                $content = $cache_info['cache'];
                // get any cached JS and add to headerlib JS
                $jsFiles = $headerlib->getJsFromHTML($content, false, true);

                foreach ($jsFiles as $jsFile) {
                    $headerlib->add_jsfile($jsFile);
                }

                $headerlib->add_js(implode("\n", $headerlib->getJsFromHTML($content)));

                // now remove all the js from the source
                $content = $headerlib->removeJsFromHtml($content);

                $canBeRefreshed = true;
            } else {
                $jsFile1 = $headerlib->getJsFilesWithScriptTags();
                $js1 = $headerlib->getJs();
                $info['outputType'] = $tikilib->getOne("SELECT `outputType` FROM `tiki_output` WHERE `entityId` = ? AND `objectType` = ? AND `version` = ?", [$info['pageName'], 'wikiPage', $info['version']]);
                $content = (new WikiLibOutput($info, $info['data'], $parse_options))->parsedValue;

                // get any JS added to headerlib during parse_data and add to the bottom of the data to cache
                $jsFile2 = $headerlib->getJsFilesWithScriptTags();
                $js2 = $headerlib->getJs();

                $jsFile = array_diff($jsFile2, $jsFile1);
                $js = array_diff($js2, $js1);

                $jsFile = implode("\n", $jsFile);
                $js = $headerlib->wrap_js(implode("\n", $js));

                $this->update_cache($page, $content . $jsFile . $js);
            }
        } else {
            $content = (new WikiLibOutput($info, $info['data'], $parse_options, $info['version']))->parsedValue;
        }

        return $content;
    }

    public function update_cache($page, $data)
    {
        $query = 'update `tiki_pages` set `cache`=?, `cache_timestamp`=? where `pageName`=?';
        $result = $this->query($query, [ $data, $this->now, $page ]);

        return true;
    }

    public function get_attachment_owner($attId)
    {
        return $this->getOne("select `user` from `tiki_wiki_attachments` where `attId`=$attId");
    }

    public function remove_wiki_attachment($attId)
    {
        global $prefs;

        $path = $this->getOne("select `path` from `tiki_wiki_attachments` where `attId`=?", [$attId]);

        /* carefull a same file can be attached in different page */
        if ($path && $this->getOne("select count(*) from `tiki_wiki_attachments` where `path`=?", [$path]) <= 1) {
            @unlink($prefs['w_use_dir'] . $path);
        }

        $query = "delete from `tiki_wiki_attachments` where `attId`=?";
        $result = $this->query($query, [$attId]);
        if ($prefs['feature_actionlog'] == 'y') {
            $logslib = TikiLib::lib('logs');
            $logslib->add_action('Removed', $attId, 'wiki page attachment');
        }
    }

    public function wiki_attach_file($page, $name, $type, $size, $data, $comment, $user, $fhash, $date = '')
    {
        $comment = strip_tags($comment);
        $now = empty($date) ? $this->now : $date;
        $attId = $this->table('tiki_wiki_attachments')->insert([
            'page' => $page,
            'filename' => $name,
            'filesize' => (int) $size,
            'filetype' => $type,
            'data' => $data,
            'created' => (int) $now,
            'hits' => 0,
            'user' => $user,
            'comment' => $comment,
            'path' => $fhash,
        ]);

        global $prefs;
        TikiLib::events()->trigger(
            'tiki.wiki.attachfile',
            [
                'type' => 'file',
                'object' => $attId,
                'wiki' => $page,
                'user' => $user,
            ]
        );
        if ($prefs['feature_user_watches'] = 'y') {
            include_once(__DIR__ . '/../notifications/notificationemaillib.php');
            sendWikiEmailNotification('wiki_file_attached', $page, $user, $comment, '', $name, '', '', false, '', 0, $attId);
        }
        if ($prefs['feature_actionlog'] == 'y') {
            $logslib = TikiLib::lib('logs');
            $logslib->add_action('Created', $attId, 'wiki page attachment', '', $user);
        }

        return $attId;
    }

    public function get_wiki_attach_file($page, $name, $type, $size)
    {
        $query = 'select * from `tiki_wiki_attachments` where `page`=? and `filename`=? and `filetype`=? and `filesize`=?';
        $result = $this->query($query, [$page, $name, $type, $size]);
        $res = $result->fetchRow();

        return $res;
    }

    public function list_wiki_attachments($page, $offset = 0, $maxRecords = -1, $sort_mode = 'created_desc', $find = '')
    {
        if ($find) {
            $mid = ' where `page`=? and (`filename` like ?)'; // why braces?
            $bindvars = [$page, '%' . $find . '%'];
        } else {
            $mid = ' where `page`=? ';
            $bindvars = [$page];
        }

        if ($sort_mode !== 'created_desc') {
            $pos = strrpos($sort_mode, '_');
            // check the sort order is valid for attachments
            if ($pos !== false && $pos > 0) {
                $shortsort = substr($sort_mode, 0, $pos);
            } else {
                $shortsort = $sort_mode;
            }
            if (! in_array(['user', 'attId', 'page', 'filename', 'filesize', 'filetype', 'hits', 'created', 'comment'], $shortsort)) {
                $sort_mode = 'created_desc';
            }
        }

        $query = 'select `user`,`attId`,`page`,`filename`,`filesize`,`filetype`,`hits`,`created`,`comment`' .
            ' from `tiki_wiki_attachments` ' . $mid . ' order by ' . $this->convertSortMode($sort_mode);
        $query_cant = "select count(*) from `tiki_wiki_attachments` $mid";
        $result = $this->query($query, $bindvars, $maxRecords, $offset);
        $cant = $this->getOne($query_cant, $bindvars);
        $ret = [];

        while ($res = $result->fetchRow()) {
            $ret[] = $res;
        }

        $retval = [];
        $retval['data'] = $ret;
        $retval['cant'] = $cant;

        return $retval;
    }
    public function list_all_attachments($offset = 0, $maxRecords = -1, $sort_mode = 'created_desc', $find = '')
    {
        if ($find) {
            $findesc = '%' . $find . '%';
            $mid = ' where `filename` like ?';
            $bindvars = [$findesc];
        } else {
            $mid = '';
            $bindvars = [];
        }
        $query = 'select `user`,`attId`,`page`,`filename`,`filesize`,`filetype`,`hits`,`created`,`comment`,`path` ';
        $query .= ' from `tiki_wiki_attachments` ' . $mid . ' order by ' . $this->convertSortMode($sort_mode);
        $query_cant = "select count(*) from `tiki_wiki_attachments` $mid";
        $result = $this->query($query, $bindvars, $maxRecords, $offset);
        $cant = $this->getOne($query_cant, $bindvars);
        $ret = [];
        while ($res = $result->fetchRow()) {
            $ret[] = $res;
        }
        $retval = [];
        $retval['data'] = $ret;
        $retval['cant'] = $cant;

        return $retval;
    }

    public function file_to_db($path, $attId)
    {
        if (is_file($path)) {
            $fp = fopen($path, 'rb');
            $data = '';
            while (! feof($fp)) {
                $data .= fread($fp, 8192 * 16);
            }
            fclose($fp);
            $query = 'update `tiki_wiki_attachments` set `data`=?,`path`=? where `attId`=?';
            if ($this->query($query, [$data, '', (int) $attId])) {
                unlink($path);
            }
        }
    }

    public function db_to_file($filename, $attId)
    {
        global $prefs;
        $file_name = md5($filename . date('U') . rand());
        $fw = fopen($prefs['w_use_dir'] . $file_name, 'wb');
        $data = $this->getOne('select `data` from `tiki_wiki_attachments` where `attId`=?', [(int) $attId]);
        if ($data) {
            fwrite($fw, $data);
        }
        fclose($fw);
        if (is_file($prefs['w_use_dir'] . $file_name)) {
            $query = 'update `tiki_wiki_attachments` set `data`=?,`path`=? where `attId`=?';
            $this->query($query, ['', $file_name, (int) $attId]);
        }
    }

    public function get_item_attachment($attId)
    {
        $query = 'select * from `tiki_wiki_attachments` where `attId`=?';
        $result = $this->query($query, [(int) $attId]);
        if (! $result->numRows()) {
            return false;
        }
        $res = $result->fetchRow();

        return $res;
    }

    public function get_item_attachement_data($att_info)
    {
        if ($att_info['path']) {
            return file_get_contents($att_info['filename']);
        }

        return $att_info['data'];
    }


    // Functions for wiki page footnotes
    public function get_footnote($user, $page)
    {
        $count = $this->getOne('select count(*) from `tiki_page_footnotes` where `user`=? and `pageName`=?', [$user, $page]);

        if (! $count) {
            return '';
        }

        return $this->getOne('select `data` from `tiki_page_footnotes` where `user`=? and `pageName`=?', [$user, $page]);
    }

    public function replace_footnote($user, $page, $data)
    {
        $querydel = 'delete from `tiki_page_footnotes` where `user`=? and `pageName`=?';
        $this->query($querydel, [$user, $page], -1, -1, false);
        $query = 'insert into `tiki_page_footnotes`(`user`,`pageName`,`data`) values(?,?,?)';
        $this->query($query, [$user, $page, $data]);
    }

    public function remove_footnote($user, $page)
    {
        if (empty($user)) {
            $query = 'delete from `tiki_page_footnotes` where `pageName`=?';
            $this->query($query, [$page]);
        } else {
            $query = 'delete from `tiki_page_footnotes` where `user`=? and `pageName`=?';
            $this->query($query, [$user, $page]);
        }
    }

    public function wiki_link_structure()
    {
        $query = 'select `pageName` from `tiki_pages` order by ' . $this->convertSortMode('pageName_asc');

        $result = $this->query($query);

        while ($res = $result->fetchRow()) {
            print($res['pageName'] . ' ');

            $page = $res['pageName'];
            $query2 = 'select `toPage` from `tiki_links` where `fromPage`=?';
            $result2 = $this->query($query2, [ $page ]);
            $pages = [];

            while ($res2 = $result2->fetchRow()) {
                if (($res2['toPage'] <> $res['pageName']) && (! in_array($res2['toPage'], $pages))) {
                    $pages[] = $res2['toPage'];
                    print($res2['toPage'] . ' ');
                }
            }

            print("\n");
        }
    }

    // Removes last version of the page (from pages) if theres some
    // version in the tiki_history then the last version becomes the actual version
    public function remove_last_version($page, $comment = '')
    {
        global $prefs;
        $this->invalidate_cache($page);
        $query = 'select * from `tiki_history` where `pageName`=? order by ' . $this->convertSortMode('lastModif_desc');
        $result = $this->query($query, [ $page ]);

        if ($result->numRows()) {
            // We have a version
            $res = $result->fetchRow();

            $histlib = TikiLib::lib('hist');

            if ($prefs['feature_contribution'] == 'y') {
                $contributionlib = TikiLib::lib('contribution');
                $tikilib = TikiLib::lib('tiki');
                $info = $tikilib->get_page_info($res['pageName']);

                $contributionlib->change_assigned_contributions(
                    $res['historyId'],
                    'history',
                    $res['pageName'],
                    'wiki page',
                    $info['description'],
                    $res['pageName'],
                    'tiki-index.php?page' . urlencode($res['pageName'])
                );
            }
            $ret = $histlib->remove_version($res['pageName'], $res['version']);
            $ret2 = $histlib->restore_page_from_history($res['pageName']);
        } else {
            $ret = $this->remove_all_versions($page);
        }
        if ($ret) {
            $logslib = TikiLib::lib('logs');
            $logslib->add_action('Removed last version', $page, 'wiki page', $comment);
            //get_strings tra("Removed last version");
        }

        return $ret;
    }

    /**
     * Return the page names for a page alias, if any.
     *
     * Unfortunately there is no mechanism to prevent two
     * different pages from sharing the same alias and that is
     * why this method return an array of page names instead of a
     * page name string.
     *
     * @param string $alias
     * @return array page names
     */
    public function get_pages_by_alias($alias)
    {
        global $prefs;
        $semanticlib = TikiLib::lib('semantic');

        $pages = [];

        if ($prefs['feature_wiki_pagealias'] == 'n' && empty($prefs["wiki_prefixalias_tokens"])) {
            return $pages;
        }

        $toPage = $alias;
        $tokens = explode(',', $prefs['wiki_pagealias_tokens']);

        $prefixes = explode(',', $prefs["wiki_prefixalias_tokens"]);
        foreach ($prefixes as $p) {
            $p = trim($p);
            if (strlen($p) > 0 && TikiLib::strtolower(substr($alias, 0, strlen($p))) == TikiLib::strtolower($p)) {
                $toPage = $p;
                $tokens = 'prefixalias';
            }
        }

        $links = $semanticlib->getLinksUsing($tokens, [ 'toPage' => $toPage ]);

        if (empty($links)) {	// if no linked pages found then the alias may be sefurl "slug" encoded, so try the un-slugged version
            global $prefs;

            $toPage = TikiLib::lib('slugmanager')->degenerate($prefs['wiki_url_scheme'], $toPage);
            $links = $semanticlib->getLinksUsing($tokens, [ 'toPage' => $toPage ]);
        }

        if (count($links) > 0) {
            foreach ($links as $row) {
                $pages[] = $row['fromPage'];
            }
        }

        return $pages;
    }

    // Like pages are pages that share a word in common with the current page
    public function get_like_pages($page)
    {
        global $user, $prefs;
        $semanticlib = TikiLib::lib('semantic');
        $tikilib = TikiLib::lib('tiki');

        preg_match_all("/([A-Z])([a-z]+)/", $page, $words);

        // Add support to ((x)) in either strict or full modes
        preg_match_all("/(([A-Za-z]|[\x80-\xFF])+)/", $page, $words2);
        $words = array_unique(array_merge($words[0], $words2[0]));
        $exps = [];
        $bindvars = [];
        foreach ($words as $word) {
            $exps[] = ' `pageName` like ?';
            $bindvars[] = "%$word%";
        }

        $exp = implode(' or ', $exps);
        if ($exp) {
            $query = "select `pageName`, `lang` from `tiki_pages` where ($exp)";

            if ($prefs['feature_multilingual'] == 'y') {
                $query .= ' ORDER BY CASE WHEN `lang` = ? THEN 0 WHEN `lang` IS NULL OR `lang` = \'\' THEN 1 ELSE 2 END';
                $bindvars[] = $prefs['language'];
            }

            $result = $this->query($query, $bindvars);
            $ret = [];

            while ($res = $result->fetchRow()) {
                if ($prefs['wiki_likepages_samelang_only'] == 'y' && ! empty($res['lang']) && $res['lang'] != $prefs['language']) {
                    continue;
                }

                if ($tikilib->user_has_perm_on_object($user, $res['pageName'], 'wiki page', 'tiki_p_view')) {
                    $ret[] = $res['pageName'];
                }
            }

            asort($ret);

            return $ret;
        }

        return [];
    }

    public function is_locked($page, $info = null)
    {
        if (! $info) {
            $query = "select `flag`, `user` from `tiki_pages` where `pageName`=?";
            $result = $this->query($query, [ $page ]);
            $info = $result->fetchRow();
        }

        return ($info['flag'] == 'L') ? $info['user'] : null;
    }

    public function get_locked()
    {
        $locked = [];
        $query = "select `pageName`, 'lockedby', 'lastModif' from `tiki_pages` where `flag`='L'";

        return $this->fetchAll($query);
    }

    public function is_editable($page, $user, $info = null)
    {
        global $prefs;
        $perms = Perms::get([ 'type' => 'wiki page', 'object' => $page ]);

        if ($perms->admin_wiki) {
            return true;
        }

        if ($prefs['wiki_creator_admin'] == 'y' && ! empty($user) && $info['creator'] == $user) {
            return true;
        }

        if ($prefs['feature_wiki_userpage'] == 'y'
            && ! empty($user)
            && strcasecmp($prefs['feature_wiki_userpage_prefix'], substr($page, 0, strlen($prefs['feature_wiki_userpage_prefix']))) == 0
        ) {
            if (strcasecmp($page, $prefs['feature_wiki_userpage_prefix'] . $user) == 0) {
                return true;
            }
        }

        if ($prefs['feature_wiki_userpage'] == 'y'
            && strcasecmp(substr($page, 0, strlen($prefs['feature_wiki_userpage_prefix'])), $prefs['feature_wiki_userpage_prefix']) == 0
            and strcasecmp($page, $prefs['feature_wiki_userpage_prefix'] . $user) != 0
        ) {
            return false;
        }
        if (! $perms->edit) {
            return false;
        }

        return ($this->is_locked($page, $info) == null || $user == $this->is_locked($page, $info)) ? true : false;
    }

    /**
     * Lock a wiki page
     *
     * @param $page
     *
     * @return TikiDb_Pdo_Result|TikiDb_Adodb_Result
     */
    public function lock_page($page)
    {
        global $user, $tikilib;

        $query = 'update `tiki_pages` set `flag`=?, `lockedby`=? where `pageName`=?';
        $result = $this->query($query, [ 'L', $user, $page ]);

        if (! empty($user)) {
            $info = $tikilib->get_page_info($page);

            $query = 'update `tiki_pages` set `user`=?, `comment`=?, `version`=? where `pageName`=?';
            $this->query($query, [$user, tra('Page locked'), $info['version'] + 1, $page]);

            $query = 'insert into `tiki_history`(`pageName`, `version`, `lastModif`, `user`, `ip`, `comment`, `data`, `description`)' .
                ' values(?,?,?,?,?,?,?,?)';
            $this->query(
                $query,
                [
                    $page,
                    (int) $info['version'] + 1,
                    (int) $info['lastModif'],
                    $user,
                    $info['ip'],
                    tra('Page locked'),
                    $info['data'],
                    $info['description']
                ]
            );
        }

        return $result;
    }

    public function unlock_page($page)
    {
        global $user;
        $tikilib = TikiLib::lib('tiki');

        $query = "update `tiki_pages` set `flag`='' where `pageName`=?";
        $result = $this->query($query, [$page]);

        if (isset($user)) {
            $info = $tikilib->get_page_info($page);

            $query = "update `tiki_pages` set `user`=?, `comment`=?, `version`=? where `pageName`=?";
            $result = $this->query($query, [$user, tra('Page unlocked'), $info['version'] + 1, $page]);

            $query = "insert into `tiki_history`(`pageName`, `version`, `lastModif`, `user`, `ip`, `comment`, `data`, `description`) values(?,?,?,?,?,?,?,?)";
            $result = $this->query(
                $query,
                [
                    $page,
                    (int) $info['version'] + 1,
                    (int) $info['lastModif'],
                    $user,
                    $info['ip'],
                    tra('Page unlocked'),
                    $info['data'],
                    $info['description']
                ]
            );
        }

        return true;
    }

    // Returns backlinks for a given page
    public function get_backlinks($page)
    {
        global $prefs;
        $query = "select `fromPage` from `tiki_links` where `toPage` = ?";
        $result = $this->query($query, [ $page ]);
        $ret = [];

        while ($res = $result->fetchRow()) {
            $is_wiki_page = substr($res['fromPage'], 0, 11) != 'objectlink:';
            if ($is_wiki_page) {
                $type = 'wiki page';
                $objectId = $res['fromPage'];
            } else {
                $objectlinkparts = explode(':', $res['fromPage']);
                $type = $objectlinkparts[1];
                $objectId = substr($res['fromPage'], strlen($type) + 12);
                if ($type == 'trackeritemfield') {
                    $feature = 'wiki_backlinks_show_trackeritem';
                } elseif (substr($type, -7) == 'comment') {
                    $feature = 'wiki_backlinks_show_comment';
                } else {
                    $feature = 'wiki_backlinks_show_' . str_replace(" ", "_", $type);
                }
                if ($prefs[$feature] !== 'y') {
                    continue;
                }
            }
            if ($type == 'trackeritemfield') {
                list($itemId, $fieldId) = explode(':', $objectId);
                $itemObject = Tracker_Item::fromId($itemId);
                if (! $itemObject->canView() || ! $itemObject->canViewField($fieldId)) {
                    continue;
                }
            } else {
                $objectperms = Perms::get(['type' => $type, 'object' => $objectId]);
                if (! $objectperms->view) {
                    continue;
                }
            }
            $aux["type"] = $type;
            $aux["objectId"] = $objectId;
            $ret[] = $aux;
        }

        return $ret;
    }

    public function get_parent_pages($child_page)
    {
        $parent_pages = [];
        $backlinks_info = $this->get_backlinks($child_page);
        foreach ($backlinks_info as $index => $backlink) {
            $parent_pages[] = $backlink['fromPage'];
        }

        return $parent_pages;
    }

    public function list_plugins($with_help = false, $area_id = 'editwiki', $onlyEnabled = true)
    {
        $parserlib = TikiLib::lib('parser');

        if ($with_help) {
            global $prefs;
            $cachelib = TikiLib::lib('cache');
            $commonKey = '{{{area-id}}}';
            $cachetag = 'plugindesc' . $this->get_language() . '_js=' . $prefs['javascript_enabled'];
            if (! $plugins = $cachelib->getSerialized($cachetag)) {
                $list = $parserlib->plugin_get_list();

                $plugins = [];
                foreach ($list as $name) {
                    $pinfo = [
                        'help' => $parserlib->get_plugin_description($name, $enabled, $commonKey),
                        'name' => TikiLib::strtoupper($name),
                    ];

                    if (! $onlyEnabled || $enabled) {
                        $info = $parserlib->plugin_info($name);
                        $pinfo['title'] = $info['name'];
                        unset($info['name']);
                        $pinfo = array_merge($pinfo, $info);

                        $plugins[] = $pinfo;
                    }
                }
                usort(
                    $plugins,
                    function ($ar1, $ar2) {
                        return strcasecmp($ar1['title'], $ar2['title']);		// sort by translated name
                    }
                );
                $cachelib->cacheItem($cachetag, serialize($plugins));
            }
            array_walk_recursive(
                $plugins,
                function (& $item) use ($commonKey, $area_id) {
                    $item = str_replace($commonKey, $area_id, $item);
                }
            );

            return $plugins;
        }
        // Only used by WikiPluginPluginManager
        $files = [];

        if (is_dir(PLUGINS_DIR)) {
            if ($dh = opendir(PLUGINS_DIR)) {
                while (($file = readdir($dh)) !== false) {
                    if (preg_match("/^wikiplugin_.*\.php$/", $file)) {
                        array_push($files, $file);
                    }
                }
                closedir($dh);
            }
        }
        sort($files);

        return $files;
    }

    // get all modified pages for a user (if actionlog is not clean)
    public function get_user_all_pages($user, $sort_mode)
    {
        $query = "select p.`pageName`, p.`user` as lastEditor, p.`creator`, max(a.`lastModif`) as date" .
            " from `tiki_actionlog` as a, `tiki_pages` as p" .
            " where a.`object`= p.`pageName` and a.`user`= ? and (a.`action`=? or a.`action`=?)" .
            " group by p.`pageName`, p.`user`, p.`creator` order by " . $this->convertSortMode($sort_mode);

        $result = $this->query($query, [$user, 'Updated', 'Created']);
        $ret = [];

        while ($res = $result->fetchRow()) {
            if ($this->user_has_perm_on_object($user, $res['pageName'], 'wiki page', 'tiki_p_view')) {
                $ret[] = $res;
            }
        }

        return $ret;
    }

    public function get_default_wiki_page()
    {
        global $user, $prefs;
        if ($prefs['useGroupHome'] == 'y') {
            $userlib = TikiLib::lib('user');
            if ($groupHome = $userlib->get_user_default_homepage($user)) {
                return $groupHome;
            }

            return $prefs['wikiHomePage'];
        }

        return $prefs['wikiHomePage'];
    }

    public function sefurl($page, $with_next = '', $all_langs = '')
    {
        global $prefs, $info;
        $smarty = TikiLib::lib('smarty');
        $script_name = 'tiki-index.php';

        if ($prefs['feature_multilingual_one_page'] == 'y') {
            // 	if ( basename($_SERVER['PHP_SELF']) == 'tiki-all_languages.php' ) {
            // 		return 'tiki-all_languages.php?page='.urlencode($page);
            // 	}

            if ($all_langs == 'y') {
                $script_name = 'tiki-all_languages.php';
            }
        }

        $pages = TikiDb::get()->table('tiki_pages');
        $page = $pages->fetchOne('pageSlug', ['pageName' => $page]) ?: $page;
        $href = "$script_name?page=" . $page;

        if (isset($prefs['feature_wiki_use_date_links']) && $prefs['feature_wiki_use_date_links'] == 'y') {
            if (isset($_REQUEST['date'])) {
                $href .= '&date=' . urlencode($_REQUEST['date']);
            } elseif (isset($_REQUEST['version'])) {
                $href .= '&date=' . urlencode($info['lastModif']);
            }
        }

        if ($with_next) {
            $href .= '&amp;';
        }

        if ($prefs['feature_sefurl'] == 'y') {
            // escape colon chars so the url doesn't appear to be protocol:address - occurs with user pages and namespaces
            $href = str_replace(':', '%3A', $href);

            include_once(__DIR__ . '/../../tiki-sefurl.php');

            return filter_out_sefurl($href, 'wiki');
        }

        return $href;
    }

    public function url_for_operation_on_a_page($script_name, $page, $with_next)
    {
        $href = "$script_name?page=" . urlencode($page);
        if ($with_next) {
            $href .= '&amp;';
        }

        return $href;
    }

    public function editpage_url($page, $with_next)
    {
        return $this->url_for_operation_on_a_page('tiki-editpage.php', $page, $with_next);
    }

    public function move_attachments($old, $new)
    {
        $query = 'update `tiki_wiki_attachments` set `page`=? where `page`=?';
        $this->query($query, [$new, $old]);
    }

    public function duplicate_page($old, $new)
    {
        $query = 'insert into `tiki_pages`' .
            ' (`pageName`,`hits`,`data`,`lastModif`,`comment`,`version`,`user`,`ip`,`description`,' .
            ' `creator`,`page_size`,`is_html`,`created`, `flag`,`points`,`votes`,`pageRank`,`lang`,' .
            ' `lockedby`) select ?,`hits`,`data`,`lastModif`,`comment`,`version`,`user`,`ip`,' .
            ' `description`,`creator`,`page_size`,`is_html`,`created`, `flag`,`points`,`votes`' .
            ',`pageRank`,`lang`,`lockedby` from `tiki_pages` where `pageName`=?';
        $this->query($query, [$new, $old]);
    }

    public function get_pages_contains($searchtext, $offset = 0, $maxRecords = -1, $sort_mode = 'pageName_asc', $categFilter = [])
    {
        $jail_bind = [];
        $jail_join = '';
        $jail_where = '';

        if ($categFilter) {
            $categlib = TikiLib::lib('categ');
            $categlib->getSqlJoin($categFilter, 'wiki page', '`tiki_pages`.`pageName`', $jail_join, $jail_where, $jail_bind);
        }

        $query = "select * from `tiki_pages` $jail_join where `tiki_pages`.`data` like ? $jail_where order by " . $this->convertSortMode($sort_mode);
        $bindvars = ['%' . $searchtext . '%'];
        $bindvars = array_merge($bindvars, $jail_bind);
        $results = $this->fetchAll($query, $bindvars, $maxRecords, $offset);
        $ret['data'] = $results;
        $query_cant = "select count(*) from (select count(*) from `tiki_pages` $jail_join where `data` like ? $jail_where group by `page_id`) as `temp`";
        $ret['cant'] = $this->getOne($query_cant, $bindvars);

        return $ret;
    }

    /*
    *	get_page_auto_toc
    *	Get the auto generated TOC setting for the page
    *	@return
    *		+1 page_auto_toc is explicitly set to true
    *		0  page_auto_toc is not set for page. Use global setting
    *		-1 page_auto_toc is explicitly set to false
    */
    public function get_page_auto_toc($pageName)
    {
        $attributes = TikiLib::lib('attribute')->get_attributes('wiki page', $pageName);
        $rc = 0;
        if (! isset($attributes['tiki.wiki.autotoc'])) {
            return 0;
        }
        $value = (int)$attributes['tiki.wiki.autotoc'];
        if ($value > 0) {
            return 1;
        }

        return -1;
    }

    public function set_page_auto_toc($pageName, $isAutoToc)
    {
        TikiLib::lib('attribute')->set_attribute('wiki page', $pageName, 'tiki.wiki.autotoc', $isAutoToc);
    }



    /*
    *	get_page_hide_title
    *	Enable the page title to not be displayed, on a per-page basis.
    *	@return
    *		+1 page_hide_title is explicitly set to true
    *		0  page_hide_title is not set for page. Use global setting
    *		-1 page_hide_title is explicitly set to false
    */
    public function get_page_hide_title($pageName)
    {
        $attributes = TikiLib::lib('attribute')->get_attributes('wiki page', $pageName);
        $rc = 0;
        if (! isset($attributes['tiki.wiki.page_hide_title'])) {
            return 0;
        }
        $value = (int)$attributes['tiki.wiki.page_hide_title'];
        if ($value > 0) {
            return 1;
        }

        return -1;
    }

    public function set_page_hide_title($pageName, $isHideTitle)
    {
        TikiLib::lib('attribute')->set_attribute('wiki page', $pageName, 'tiki.wiki.page_hide_title', $isHideTitle);
    }

    public function get_without_namespace($pageName)
    {
        global $prefs;

        if ((isset($prefs['namespace_enabled']) && $prefs['namespace_enabled'] == 'y') && $prefs['namespace_separator']) {
            $pos = strrpos($pageName, $prefs['namespace_separator']);

            if (false !== $pos) {
                return substr($pageName, $pos + strlen($prefs['namespace_separator']));
            }

            return $pageName;
        }

        return $pageName;
    }

    public function get_explicit_namespace($pageName)
    {
        $attributes = TikiLib::lib('attribute')->get_attributes('wiki page', $pageName);

        return isset($attributes['tiki.wiki.namespace']) ? $attributes['tiki.wiki.namespace'] : '';
    }

    public function set_explicit_namespace($pageName, $namespace)
    {
        TikiLib::lib('attribute')->set_attribute('wiki page', $pageName, 'tiki.wiki.namespace', $namespace);
    }

    public function get_namespace($pageName)
    {
        global $prefs;

        if ($pageName
            && $prefs['namespace_enabled'] == 'y'
            && $prefs['namespace_separator']
        ) {
            $explicit = $this->get_explicit_namespace($pageName);

            if ($explicit) {
                return $explicit;
            }

            $pos = strrpos($pageName, $prefs['namespace_separator']);

            if (false !== $pos) {
                return substr($pageName, 0, $pos);
            }
        }

        return false;
    }

    public function get_readable($pageName)
    {
        global $prefs;

        if ($pageName
            && $prefs['namespace_enabled'] == 'y'
            && $prefs['namespace_separator']
        ) {
            return str_replace($prefs['namespace_separator'], ' / ', $pageName);
        }

        return $pageName;
    }

    public function include_default_namespace($pageName)
    {
        global $prefs;

        if ($prefs['namespace_enabled'] == 'y' && ! empty($prefs['namespace_default'])) {
            return $prefs['namespace_default'] . $prefs['namespace_separator'] . $pageName;
        }

        return $pageName;
    }

    public function include_namespace($pageName, $namespace)
    {
        global $prefs;

        if ($prefs['namespace_enabled'] == 'y' && $namespace) {
            return $namespace . $prefs['namespace_separator'] . $pageName;
        }

        return $pageName;
    }

    public function get_namespace_parts($pageName)
    {
        global $prefs;

        if ($namespace = $this->get_namespace($pageName)) {
            return explode($prefs['namespace_separator'], $namespace);
        }

        return [];
    }

    // Page display options
    //////////////////////////
    public function processPageDisplayOptions()
    {
        global	$prefs;
        $headerlib = TikiLib::lib('header');

        $currPage = isset($_REQUEST['page']) ? $_REQUEST['page'] : '';
        if (! empty($currPage) &&
            (strstr($_SERVER["SCRIPT_NAME"], "tiki-editpage.php") === false) &&
            (strstr($_SERVER["SCRIPT_NAME"], 'tiki-pagehistory.php') === false)) {
            // Determine the auto TOC setting
            if ($prefs['wiki_auto_toc'] === 'y') {
                $isAutoTocActive = $this->get_page_auto_toc($currPage);
                // Use page specific setting?
                if ($isAutoTocActive > 0) {
                    $isAutoTocActive = true;
                } elseif ($isAutoTocActive < 0) {
                    $isAutoTocActive = false;
                } else {
                    $isAutoTocActive = $prefs['wiki_toc_default'] === 'on';
                }
                // Add Auto TOC if enabled
                if ($isAutoTocActive) {
                    // Enable Auto TOC
                    $headerlib->add_jsfile('lib/jquery_tiki/autoToc.js');

                    //Get autoToc offset
                    $tocOffset = ! empty($prefs['wiki_toc_offset']) ? $prefs['wiki_toc_offset'] : 10;

                    // Show/Hide the static inline TOC
                    $isAddInlineToc = isset($prefs['wiki_inline_auto_toc']) ? $prefs['wiki_inline_auto_toc'] === 'y' : false;
                    if ($isAddInlineToc) {
                        // Enable static, inline TOC
                        //$headerlib->add_css('#autotoc {display: block;}');

                        //Add top margin
                        $headerlib->add_css('#autotoc {margin-top:' . $tocOffset . 'px;}');

                        // Postion inline TOC top/left/right
                        $tocPos = ! empty($prefs['wiki_toc_pos']) ? $prefs['wiki_toc_pos'] : 'right';
                        switch (strtolower($tocPos)) {
                            case 'top':
                                $headerlib->add_css('#autotoc {border: 0px;}');

                                break;
                            case 'left':
                                $headerlib->add_css('#autotoc {float: left;margin-right:15px;}');

                                break;
                            case 'right':
                            default:
                                $headerlib->add_css('#autotoc {float: right;margin-left:15px;}');

                                break;
                        }
                    } else {//Not inline TOC
                        //$headerlib->add_css('#autotoc {display: none;}');
                        //Adds the offset for the affix
                        $headerlib->add_css('.affix {top:' . $tocOffset . 'px;}');
                    }
                }
            }

            // Hide title per page
            $isHideTitlePerPage = isset($prefs['wiki_page_hide_title']) ? $prefs['wiki_page_hide_title'] === 'y' : false;
            if ($isHideTitlePerPage) {
                $isHideTitle = false;
                if (! empty($currPage)) {
                    $isPageHideTitle = $this->get_page_hide_title($currPage);
                    if ($isPageHideTitle != 0) {
                        // Use page specific setting
                        $isHideTitle = $isPageHideTitle < 0 ? true : false;
                    }
                }
                if ($isHideTitle) {
                    $headerlib->add_css('.pagetitle {display: none;}');
                    $headerlib->add_css('.titletop {display: none;}');
                }
            }
        }
    }
}

class convertToTiki9
{
    public $parserlib;
    public $argumentParser;

    public function __construct()
    {
        $this->parserlib = TikiLib::lib('parser');
        $this->argumentParser = new WikiParser_PluginArgumentParser();
    }


    //<!--below methods are used for converting objects
    //<!--Start for converting pages
    public function convertPages()
    {
        $infos = $this->parserlib->fetchAll(
            'SELECT data, page_id' .
            ' FROM tiki_pages' .
            ' LEFT JOIN tiki_db_status ON tiki_db_status.objectId = tiki_pages.page_id' .
            ' WHERE tiki_db_status.tableName = "tiki_pages" IS NULL'
        );

        foreach ($infos as $info) {
            if (! empty($info['data'])) {
                $converted = $this->convertData($info['data']);

                $this->updatePlugins($converted['fingerPrintsOld'], $converted['fingerPrintsNew']);

                $this->savePage($info['page_id'], $converted['data']);
            }
        }
    }

    public function savePage($id, $data)
    {
        $status = $this->checkObjectStatus($id, 'tiki_pages');

        if (empty($status)) {
            $this->parserlib->query("UPDATE tiki_pages SET data = ? WHERE page_id = ?", [$data, $id]);

            $this->saveObjectStatus($id, 'tiki_pages', 'conv9.0');
        }
    }
    //end for converting pages-->


    //<!--start for converting histories
    public function convertPageHistoryFromPageAndVersion($page, $version)
    {
        $infos = $this->parserlib->fetchAll(
            'SELECT data, historyId' .
            ' FROM tiki_history' .
            ' LEFT JOIN tiki_db_status' .
            ' ON tiki_db_status.objectId = tiki_history.historyId' .
            ' WHERE tiki_db_status.tableName = "tiki_history" IS NULL' .
            ' AND pageName = ? AND version = ?',
            [$page, $version]
        );

        foreach ($infos as $info) {
            if (! empty($info['data'])) {
                $converted = $this->convertData($info['data']);

                //update plugins first, if it failes, no problems with the page
                $this->updatePlugins($converted['fingerPrintsOld'], $converted['fingerPrintsNew']);

                $this->savePageHistory($info['historyId'], $converted['data']);
            }
        }
    }

    public function convertPageHistories()
    {
        $infos = $this->parserlib->fetchAll(
            'SELECT data, historyId' .
            ' FROM tiki_history' .
            ' LEFT JOIN tiki_db_status ON tiki_db_status.objectId = tiki_history.historyId' .
            ' WHERE tiki_db_status.tableName = "tiki_history" IS NULL'
        );

        foreach ($infos as $info) {
            if (! empty($info['data'])) {
                $converted = $this->convertData($info['data']);

                $this->updatePlugins($converted['fingerPrintsOld'], $converted['fingerPrintsNew']);

                $this->savePageHistory($info['historyId'], $converted['data']);
            }
        }
    }

    public function savePageHistory($id, $data)
    {
        $status = $this->checkObjectStatus($id, 'tiki_history');

        if (empty($status)) {
            $this->parserlib->query(
                'UPDATE tiki_history' .
                ' SET data = ?' .
                ' WHERE historyId = ?',
                [$data, $id]
            );

            $this->saveObjectStatus($id, 'tiki_history', 'conv9.0');
        }
    }
    //end for converting histories-->



    //<!--start for converting modules
    public function convertModules()
    {
        $infos = $this->parserlib->fetchAll(
            'SELECT data, name' .
            ' FROM tiki_user_modules' .
            ' LEFT JOIN tiki_db_status ON tiki_db_status.objectId = tiki_user_modules.name' .
            ' WHERE tiki_db_status.tableName = "tiki_user_modules" IS NULL'
        );

        foreach ($infos as $info) {
            if (! empty($info['data'])) {
                $converted = $this->convertData($info['data']);

                $this->updatePlugins($converted['fingerPrintsOld'], $converted['fingerPrintsNew']);

                $this->saveModule($info['name'], $converted['data']);
            }
        }
    }

    public function saveModule($name, $data)
    {
        $status = $this->checkObjectStatus($name, 'tiki_user_modules');

        if (empty($status)) {
            $this->parserlib->query('UPDATE tiki_user_modules SET data = ? WHERE name = ?', [$data, $name]);

            $this->saveObjectStatus($name, 'tiki_user_modules', 'conv9.0');
        }
    }
    //end for converting modules-->
    //end conversion of objects-->



    //<!--below methods are used in tracking status of pages
    public function saveObjectStatus($objectId, $tableName, $status = 'new9.0+')
    {
        $currentStatus = $this->parserlib->getOne("SELECT status FROM tiki_db_status WHERE objectId = ? AND tableName = ?", [$objectId, $tableName]);

        if (empty($currentStatus)) {
            //Insert a status record if one doesn't exist
            $this->parserlib->query(
                'INSERT INTO tiki_db_status ( objectId,	tableName,	status )' .
                ' VALUES (?, ?, ?)',
                [$objectId, 	$tableName,	$status]
            );
        } else {
            //update a status record, it already exists
            $this->parserlib->query(
                'UPDATE tiki_db_status' .
                ' SET status = ?' .
                ' WHERE objectId = ? AND tableName = ?',
                [$status, $objectId, $tableName]
            );
        }
    }

    public function checkObjectStatus($objectId, $tableName)
    {
        return $this->parserlib->getOne(
            'SELECT status' .
            ' FROM tiki_db_status' .
            ' WHERE objectId = ? AND tableName = ?',
            [$objectId, $tableName]
        );
    }
    //end status methods-->


    //<!--below methods are used for conversion of plugins and data
    public function updatePlugins($fingerPrintsOld, $fingerPrintsNew)
    {
        //here we find the old fingerprint and replace it with the new one
        for ($i = 0, $count_fingerPrintsOld = count($fingerPrintsOld); $i < $count_fingerPrintsOld; $i++) {
            if (! empty($fingerPrintsOld[$i]) && $fingerPrintsOld[$i] != $fingerPrintsNew[$i]) {
                //Remove any that may conflict with the new fingerprint, not sure how to fix this yet
                $this->parserlib->query("DELETE FROM tiki_plugin_security WHERE fingerprint = ?", [$fingerPrintsNew[$i]]);

                // Now update fingerprint (if it exists)
                $this->parserlib->query("UPDATE tiki_plugin_security SET fingerprint = ? WHERE fingerprint = ?", [$fingerPrintsNew[$i], $fingerPrintsOld[$i]]);
            }
        }
    }

    public function convertData($data)
    {
        //we store the original matches because we are about to change and update them, we need to get their fingerprint
        $oldMatches = WikiParser_PluginMatcher::match($data);

        // HTML-decode pages
        $data = htmlspecialchars_decode($data);

        // find the plugins
        $matches = WikiParser_PluginMatcher::match($data);

        $replaced = [];

        $fingerPrintsOld = [];
        foreach ($oldMatches as $match) {
            $name = $match->getName();
            $meta = $this->parserlib->plugin_info($name);
            // only check fingerprints of plugins requiring validation
            if (! empty($meta['validate'])) {
                $args = $this->argumentParser->parse($match->getArguments());

                //RobertPlummer - pre 9, latest findings from v8 is that the < and > chars are THE ONLY ones converted to &lt; and &gt; everything else seems to be decoded
                $body = $match->getBody();

                // jonnyb - pre 9.0, Tiki 6 (?) fingerprints are calculated with the undecoded body
                $fingerPrint = $this->parserlib->plugin_fingerprint($name, $meta, $body, $args);

                // so check the db for previously recorded plugins
                if (! $this->parserlib->getOne('SELECT COUNT(*) FROM tiki_plugin_security WHERE fingerprint = ?', [$fingerPrint])) {
                    // jb but v 7 & 8 fingerprints may be calculated differently, so check both fully decoded and partially
                    $body = htmlspecialchars_decode($body);
                    $fingerPrint = $this->parserlib->plugin_fingerprint($name, $meta, $body, $args);

                    if (! $this->parserlib->getOne('SELECT COUNT(*) FROM tiki_plugin_security WHERE fingerprint = ?', [$fingerPrint])) {
                        $body = str_replace(['<', '>'], ['&lt;', '&gt;'], $body);
                        $fingerPrint = $this->parserlib->plugin_fingerprint($name, $meta, $body, $args);

                        if (! $this->parserlib->getOne('SELECT COUNT(*) FROM tiki_plugin_security WHERE fingerprint = ?', [$fingerPrint])) {
                            // old fingerprint not found - what to do? Might be worth trying &quot; chars too...
                            $fingerPrint = '';
                        }
                    }
                }
                $fingerPrintsOld[] = $fingerPrint;
            }
        }

        $fingerPrintsNew = [];
        // each plugin
        foreach ($matches as $match) {
            $name = $match->getName();
            $meta = $this->parserlib->plugin_info($name);
            $argsRaw = $match->getArguments();

            //Here we detect if a plugin was double encoded and this is the second decode
            //try to detect double encoding
            if (preg_match("/&amp;&/i", $argsRaw) || preg_match("/&quot;/i", $argsRaw) || preg_match("/&gt;/i", $argsRaw)) {
                $argsRaw = htmlspecialchars_decode($argsRaw);				// decode entities in the plugin args (usually &quot;)
            }

            $args = $this->argumentParser->parse($argsRaw);
            $plugin = (string) $match;
            $key = '§' . md5(TikiLib::genPass()) . '§';					// by replace whole plugin with a guid

            $data = str_replace($plugin, $key, $data);

            $body = $match->getBody();									// leave the bodies alone
            $key2 = '§' . md5(TikiLib::genPass()) . '§';					// by replacing it with a guid
            $plugin = str_replace($body, $key2, $plugin);

            //Here we detect if a plugin was double encoded and this is the second decode
            //try to detect double encoding
            if (preg_match("/&amp;&/i", $plugin) || preg_match("/&quot;/i", $plugin) || preg_match("/&gt;/i", $plugin)) {
                $plugin = htmlspecialchars_decode($plugin);				// decode entities in the plugin args (usually &quot;)
            }

            $plugin = str_replace($key2, $body, $plugin);				// finally put the body back

            $replaced['key'][] = $key;
            $replaced['data'][] = $plugin;								// store the decoded-args plugin for replacement later

            // only check fingerprints of plugins requiring validation
            if (! empty($meta['validate'])) {
                $fingerPrintsNew[] = $this->parserlib->plugin_fingerprint($name, $meta, $body, $args);
            }
        }

        $this->parserlib->plugins_replace($data, $replaced);					// put the plugins back into the page

        return [
            "data" => $data,
            "fingerPrintsOld" => $fingerPrintsOld,
            "fingerPrintsNew" => $fingerPrintsNew
        ];
    }

    //end conversion methods-->
}


class WikiLibOutput
{
    public $info;
    public $originalValue;
    public $parsedValue;
    public $options;

    public function __construct($info, $originalValue, $options = [])
    {
        //TODO: info may have an override, we need to build it in using MYSQL
        $this->info = $info;
        $this->originalValue = $originalValue;
        $this->options = $options;

        $this->parsedValue = TikiLib::lib('parser')->parse_data($this->originalValue, $this->options = $options);
    }
}
