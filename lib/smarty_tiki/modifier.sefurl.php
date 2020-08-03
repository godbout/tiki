<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

// Translate only if feature_multilingual is on

//this script may only be included - so its better to die if called directly.
if (strpos($_SERVER["SCRIPT_NAME"], basename(__FILE__)) !== false) {
    header("location: index.php");
    exit;
}

function smarty_modifier_sefurl($source, $type = 'wiki', $with_next = '', $all_langs = '', $with_title = 'y', $title = '')
{
    global $prefs;
    $wikilib = TikiLib::lib('wiki');
    $tikilib = TikiLib::lib('tiki');
    $smarty = TikiLib::lib('smarty');

    $sefurl = $prefs['feature_sefurl'] == 'y';

    switch ($type) {
        case 'wiki page':
        case 'wikipage':
            $type = 'wiki';

            break;
        case 'post':
        case 'blog post':
            $type = 'blogpost';

            break;
    }

    $urlAnchor = '';
    if (substr($type, -7) == 'comment') {
        $urlAnchor = '#threadId=' . (int)$source;
        $type = substr($type, 0, strlen($type) - 8);
        $info = TikiLib::lib('comments')->get_comment((int)$source);
        $source = $info['object'];
    }

    switch ($type) {
        case 'wiki':
            return TikiLib::tikiUrlOpt($wikilib->sefurl($source, $with_next, $all_langs));

        case 'blog':
            $href = $sefurl ? "blog$source" : "tiki-view_blog.php?blogId=$source";

            break;

        case 'blog post':
        case 'blogpost':
            $href = $sefurl ? "blogpost$source" : "tiki-view_blog_post.php?postId=$source";

            break;
        case 'calendar':
            $href = $sefurl ? "cal$source" : "tiki-calendar.php?calIds[]=$source";

            break;

        case 'calendaritem':
            $href = "tiki-calendar_edit_item.php?viewcalitemId=$source";

            break;

        case 'calendar event':
            $href = $sefurl ? "calevent$source" : "tiki-calendar_edit_item.php?viewcalitemId=$source";

            break;

        case 'gallery':
            $href = 'tiki-browse_gallery.php?galleryId=' . $source;

            break;

        case 'article':
            $href = $sefurl ? "article$source" : "tiki-read_article.php?articleId=$source";

            break;

        case 'topic':
            $href = "tiki-view_articles.php?topic=$source";

            break;

        case 'file':
        case 'thumbnail':
        case 'display':
        case 'preview':
            $attributelib = TikiLib::lib('attribute');
            $attributes = $attributelib->get_attributes('file', $source);

            if ($type == 'file') {
                $prefix = 'dl';
                $suffix = null;
            } else {
                $prefix = $type;
                $suffix = '&amp;' . $type;
            }

            if (isset($attributes['tiki.content.url'])) {
                $href = $attributes['tiki.content.url'];
            } else {
                $href = $sefurl ? "$prefix$source" : "tiki-download_file.php?fileId=$source$suffix";
            }

            break;

        case 'draft':
            $href = 'tiki-download_file.php?fileId=' . $source . '&amp;draft';

            break;

        case 'trackeritemfield':
            $type = 'trackeritem';
            $source = (int)explode(':', $source)[0];

            // no break
        case 'tracker item':
            $type = 'trackeritem';

            // no break
        case 'trackeritem':
            $replacementpage = '';
            if ($prefs["feature_sefurl_tracker_prefixalias"] == 'y' && $prefs['tracker_prefixalias_on_links'] == 'y') {
                $trklib = TikiLib::lib('trk');
                $replacementpage = $trklib->get_trackeritem_pagealias($source);
            }
            if ($replacementpage) {
                return TikiLib::tikiUrlOpt($wikilib->sefurl($replacementpage, $with_next, $all_langs));
            }
                if ($prefs['pwa_feature'] == 'y') {
                    $trklib = TikiLib::lib('trk');
                    $item = $trklib->get_item_info($source);
                    $href = 'tiki-ajax_services.php?controller=tracker&action=update_item&trackerId=' . $item['trackerId'] . '&itemId=' . $source;
                } else {
                    $href = 'tiki-view_tracker_item.php?itemId=' . $source;
                }
            
            break;

        case 'tracker':
            if ($source) {
                $href = 'tiki-view_tracker.php?trackerId=' . $source;
            } else {
                $href = 'tiki-list_trackers.php';
            }

            break;

        case 'trackerfield':
            $trklib = TikiLib::lib('trk');
            $trackerId = TikiLib::lib('trk')->get_field_info((int)$source)['trackerId'];
            $href = 'tiki-admin_tracker_fields.php?trackerId=' . $trackerId;

            break;
        case 'filegallery':
        case 'file gallery':
            $type = 'file gallery';
            $href = 'tiki-list_file_gallery.php?galleryId=' . $source;

            break;

        case 'forum':
            $href = $sefurl ? "forum$source" : 'tiki-view_forum.php?forumId=' . $source;

            break;

        case 'forumthread':
        case 'forum post':	// used in unified search getSupportedTypes()
            $href = $sefurl ? "forumthread$source" : 'tiki-view_forum_thread.php?comments_parentId=' . $source;

            break;

        case 'image':
            $href = 'tiki-browse_image.php?imageId=' . $source;

            break;

        case 'sheet':
            $href = $sefurl ? "sheet$source" : "tiki-view_sheets.php?sheetId=$source";

            break;

        case 'category':
            $href = $sefurl ? "cat$source" : "tiki-browse_categories.php?parentId=$source";

            break;

        case 'freetag':
            $href = "tiki-browse_freetags.php?tag=" . urlencode($source);

            break;

        case 'newsletter':
            $href = "tiki-newsletters.php?nlId=" . urlencode($source);

            break;

        case 'survey':
            $href = "tiki-take_survey.php?surveyId=" . urlencode($source);

            break;

        default:
            $href = $source;

            break;
    }

    if ($with_next && ($with_title != 'y' || $prefs['feature_sefurl'] !== 'y')) {
        $href .= '&amp;';
    }

    if ($prefs['feature_sefurl'] == 'y' && $smarty) {
        include_once('tiki-sefurl.php');

        return TikiLib::tikiUrlOpt(filter_out_sefurl($href, $type, $title, $with_next, $with_title)) . $urlAnchor;
    }

    return TikiLib::tikiUrlOpt($href) . $urlAnchor;
}
