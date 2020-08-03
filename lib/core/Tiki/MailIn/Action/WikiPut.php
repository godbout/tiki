<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tiki\MailIn\Action;

use Tiki\MailIn\Account;
use Tiki\MailIn\Source\Message;
use TikiLib;

class WikiPut implements ActionInterface
{
    private $namespace;
    private $routing;

    public function __construct(array $params)
    {
        $this->namespace = isset($params['namespace']) ? $params['namespace'] : null;
        $this->routing = ! empty($params['structure_routing']);
    }

    public function getName()
    {
        return tr('Wiki Create/Update');
    }

    public function isEnabled()
    {
        global $prefs;

        return $prefs['feature_wiki'] == 'y';
    }

    public function isAllowed(Account $account, Message $message)
    {
        $tikilib = TikiLib::lib('tiki');
        $user = $message->getAssociatedUser();

        $page = $this->getPage($message, true);
        $perms = $tikilib->get_user_permission_accessor($user, 'wiki page', $page);

        $info = $tikilib->get_page_info($page);
        if (! $info) {
            if ($route = $this->getRoute($message)) {
                $structName = $route['structName'];
                $structperms = $tikilib->get_user_permission_accessor($user, 'wiki page', $structName);
                if (! $structperms->edit_structures) {
                    return false;
                }
            }

            $defaultCategory = $account->getDefaultCategory();
            if ($defaultCategory) {
                $categperms = $tikilib->get_user_permission_accessor($user, 'category', $defaultCategory);

                return $categperms->edit;
            }
        }

        return $perms->edit;
    }

    public function canAttach(Account $account, Message $message)
    {
        global $prefs;
        if ($prefs['feature_wiki_attachments'] != 'y') {
            return false;
        }

        $tikilib = TikiLib::lib('tiki');
        $user = $message->getAssociatedUser();

        $page = $this->getPage($message, true);
        $info = $tikilib->get_page_info($page);

        if (! $info) {
            $defaultCategory = $account->getDefaultCategory();
            if ($defaultCategory) {
                $categperms = $tikilib->get_user_permission_accessor($user, 'category', $defaultCategory);

                return $categperms->wiki_attach_files;
            }
        }

        $perms = $tikilib->get_user_permission_accessor($user, 'wiki page', $page);

        return $perms->wiki_attach_files || $account->isAnyoneAllowed();
    }

    public function execute(Account $account, Message $message)
    {
        $tikilib = TikiLib::lib('tiki');

        $user = $message->getAssociatedUser();
        $page = $this->getPage($message, true);

        if (strlen($page) > 160) {
            $smarty = TikiLib::lib('smarty');
            $smarty->loadPlugin('smarty_modifier_truncate');

            $page = smarty_modifier_truncate($page, 159, '...', false, true);
        }

        if ($this->canAttach($account, $message) && $account->hasAutoAttach()) {
            foreach ($message->getAttachments() as $att) {
                $link = $this->attachFile($page, $att, $user);
                $message->setLink($att['contentId'], $link);
            }
        }

        $body = $message->getHtmlBody(false);

        if ($this->canAttach($account, $message) && $account->hasInlineAttach() && $body) {
            $body = $this->handleInlineImages($page, $body, $message);
        } else {
            $body = $message->getHtmlBody();
        }

        $data = $account->parseBody($body);
        $info = $tikilib->get_page_info($page);

        // Allow sub-classes to play with the data
        $body = $this->handleContent($data, $info);

        if (! $info) {
            if ($route = $this->getRoute($message)) {
                // Use the page structure node, if specified, otherwise link to the rrot of the structure
                if ($route['page_id'] > 0) {
                    $parent_id = $route['page_struct_refid'];	// page_ref_id
                } else {
                    $parent_id = $route['page_ref_id'];
                }

                $structName = $route['structName'];
                $structure_id = $route['structure_id'];
                $begin = true;

                $after_ref_id = null;
                $alias = '';
                $options = [];

                $options['hide_toc'] = 'y';
                $options['creator'] = $user;
                $options['creator_msg'] = tra('created from mail-in');
                $options['ip_source'] = '0.0.0.0';

                $structlib = TikiLib::lib('struct');
                $structlib->s_create_page($parent_id, $after_ref_id, $page, $alias, $structure_id, $options);
                $comment = "Page: $page has been added to structureId: $structure_id by " . $account->getAddress();

                $tikilib->update_page(
                    $page,
                    $body,
                    $comment,
                    $user,
                    $options['ip_source'],
                    '', //desc
                    0, //edit_minor
                    '', //lang
                    $data['is_html'], //is_html
                    '', //hash
                    null, //saveLastModif
                    $data['wysiwyg']	//wysiwyg
                );

                // Categorize with structure categories
                $categlib = TikiLib::lib('categ');
                $categParent = $categlib->get_object_categories('wiki page', $structName, -1, false);
                foreach ($categParent as $c) {
                    $categoryId = $c['categoryId'];
                    $this->categorize($page, $categoryId, $user);
                }
            } else {
                // No routing
                $tikilib->create_page(
                    $page,
                    0,
                    $body,
                    $tikilib->now,
                    "Created from " . $account->getAddress(),
                    $user,
                    '0.0.0.0',
                    '', //description
                    '', //lang
                    $data['is_html'], //is_html
                    '', //hash
                    $data['wysiwyg'] //wysiwyg
                );
            }

            $default = $account->getDefaultCategory();
            if ($default) {
                $this->categorize($page, $default, $user);
            }
        } else {
            // Page exists
            $tikilib->update_page(
                $page,
                $body,
                "Updated from " . $account->getAddress(),
                $user,
                '0.0.0.0',
                '', //desc
                0, //edit_minor
                '', //lang
                $data['is_html'], //is_html
                '', //hash
                null, //saveLastModif
                $data['wysiwyg']	//wysiwyg
            );
        }

        return true;
    }

    protected function handleContent($data, $info)
    {
        return $data['body'];
    }

    private function categorize($pageName, $category, $user)
    {
        $categlib = TikiLib::lib('categ');
        if ($categlib->get_category($category)) {
            $categlib->categorizePage($pageName, $category, $user);
        }
    }

    protected function getPage($message, $routing = false)
    {
        $page = $message->getSubject();

        $wikilib = Tikilib::lib('wiki');
        $page = $wikilib->remove_badchars($page);

        if ($this->namespace) {
            return $wikilib->include_namespace($page, $this->namespace);
        } elseif ($routing) {
            if ($route = $this->getRoute($message)) {
                $nsName = $wikilib->get_namespace($route['structName']);
                if (! empty($nsName)) {
                    return $wikilib->include_namespace($page, $nsName);
                }
            }
        }

        return $page;
    }

    /**
     * @param Message $message
     *
     * @throws \Exception
     * @return |null
     */
    private function getRoute($message)
    {
        if (! $this->routing) {
            return null;
        }

        $usermailinlib = TikiLib::lib('usermailin');
        $body = $message->getHtmlBody();
        $routes = $usermailinlib->locate_struct($message->getAssociatedUser(), $message->getSubject(), $body);
        if (! empty($routes['data'])) {
            return $routes['data'][0]; // Only use the first route
        }
    }

    private function attachFile($page, $att, $user)
    {
        // TODO make it work with feature_use_fgal_for_wiki_attachments
        if (! $att['link']) {
            $wikilib = TikiLib::lib('wiki');
            $attId = $wikilib->wiki_attach_file($page, $att['name'], $att['type'], $att['size'], $att['data'], "attached by mail $user", $user, '');

            return 'tiki-download_wiki_attachment.php?attId=' . $attId . '&page=' . urlencode($page);
        }

        return $att['link'];
    }

    private function handleInlineImages($page, $body, $message)
    {
        $user = $message->getAssociatedUser();

        foreach ($message->getAttachments() as $att) {
            if (substr($att['type'], 0, 6) != 'image/') {
                // Skip non-images
                continue;
            }

            $string = "cid:{$att['contentId']}"; // This string may differ
            if (strpos($body, $string) !== false) {
                $link = $this->attachFile($page, $att, $user);
                $message->setLink($att['contentId'], $link);
                $body = str_replace($string, $link, $body);
            }
        }

        return $body;
    }
}
