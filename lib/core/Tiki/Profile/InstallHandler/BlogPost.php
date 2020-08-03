<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class Tiki_Profile_InstallHandler_BlogPost extends Tiki_Profile_InstallHandler
{
    public function getData()
    {
        if ($this->data) {
            return $this->data;
        }

        $defaults = [
            'title' => 'Title',
            'private' => 'n',
            'user' => '',
            'geolocation' => '',
        ];

        $data = array_merge($defaults, $this->obj->getData());

        $data = Tiki_Profile::convertYesNo($data);

        return $this->data = $data;
    }

    public function canInstall()
    {
        $data = $this->getData();
        if (! isset($data['blog'])) {
            return false;
        }
        if (! isset($data['content'])) {
            return false;
        }

        return true;
    }

    public function _install()
    {
        global $prefs;
        $bloglib = TikiLib::lib('blog');

        $data = $this->getData();

        $this->replaceReferences($data);

        if (isset($data['blog']) && empty($data['user'])) {
            $tikilib = TikiLib::lib('tiki');
            $bloglib = TikiLib::lib('blog');

            $result = $tikilib->query("SELECT `user` FROM `tiki_blogs` WHERE `blogId` = ?", [ $data['blog'] ]);

            if ($row = $result->fetchRow()) {
                $data['user'] = $row['user'];
            }
        }

        $entryId = $bloglib->blog_post($data['blog'], $data['content'], $data['excerpt'], $data['user'], $data['title'], '', $data['private']);

        if ($prefs['geo_locate_blogpost'] == 'y' && ! empty($data['geolocation'])) {
            TikiLib::lib('geo')->set_coordinates('blog post', $entryId, $data['geolocation']);
        }

        return $entryId;
    }

    /**
     * Remove blog post
     *
     * @param string $blogPost
     * @return bool
     */
    public function remove($blogPost)
    {
        if (! empty($blogPost)) {
            $bloglib = TikiLib::lib('blog');
            $post = $bloglib->table('tiki_blog_posts')->fetchAll(['postId'], ['title' => $blogPost]);
            if (count($post) == 1 && ! empty($post[0]['postId']) && $bloglib->remove_post($post[0]['postId'])) {
                return true;
            }
        }

        return false;
    }
}
