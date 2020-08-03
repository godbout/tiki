<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class RatingConfigLib extends TikiDb_Bridge
{
    private function config()
    {
        return $this->table('tiki_rating_configs');
    }

    private function obtained()
    {
        return $this->table('tiki_rating_obtained');
    }

    public function get_configurations()
    {
        return $this->fetchAll('SELECT * FROM `tiki_rating_configs`');
    }

    public function get_configuration($id)
    {
        return $this->config()->fetchFullRow([
            'ratingConfigId' => $id,
        ]);
    }

    public function create_configuration($name)
    {
        $this->query(
            'INSERT INTO `tiki_rating_configs` ( `name`, `formula` ) VALUES( ?, ? )',
            [ $name, '(rating-average (object type object-id))' ]
        );

        return $this->lastInsertId();
    }

    public function update_configuration($id, $name, $expiry, $formula)
    {
        $this->query(
            'UPDATE `tiki_rating_configs` SET `name` = ?, `expiry` = ?, `formula` = ? WHERE `ratingConfigId` = ?',
            [ $name, $expiry, $formula, $id ]
        );
    }

    public function record_value($info, $type, $object, $value)
    {
        $now = time() + $info['expiry'];

        $this->query(
            'INSERT INTO `tiki_rating_obtained`' .
            ' ( `ratingConfigId`, `type`, `object`, `value`, `expire` )' .
            ' VALUES( ?, ?, ?, ?, ? ) ON DUPLICATE KEY UPDATE `value` = ?, `expire` = ?',
            [ $info['ratingConfigId'], $type, $object, $value, $now, $value, $now ]
        );
    }

    public function get_expired_object_list($max)
    {
        global $prefs;

        if ($prefs['feature_wiki'] == 'y') {
            $this->query(
                'INSERT IGNORE INTO `tiki_rating_obtained` ( `ratingConfigId`, `type`, `object`, `value`, `expire`)' .
                ' SELECT `ratingConfigId`, "wiki page", `page_id`, 0, 0' .
                ' FROM `tiki_pages`, `tiki_rating_configs`'
            );
        }

        if ($prefs['feature_wiki_comments'] == 'y' || $prefs['feature_forums'] == 'y') {
            $this->query(
                'INSERT IGNORE INTO `tiki_rating_obtained` ( `ratingConfigId`, `type`, `object`, `value`, `expire` )' .
                ' SELECT `ratingConfigId`, "comment", `threadId`, 0, 0' .
                ' FROM `tiki_comments`, `tiki_rating_configs`'
            );
        }

        if ($prefs['feature_articles'] == 'y') {
            $this->query(
                'INSERT IGNORE INTO `tiki_rating_obtained` ( `ratingConfigId`, `type`, `object`, `value`, `expire` )' .
                ' SELECT `ratingConfigId`, "article", `articleId`, 0, 0' .
                ' FROM `tiki_articles`, `tiki_rating_configs`'
            );
        }

        return $this->fetchAll(
            'SELECT `type`, `object` FROM `tiki_rating_obtained` WHERE `expire` < UNIX_TIMESTAMP() GROUP BY `type`, `object`, `expire` ORDER BY `expire`',
            [],
            $max
        );
    }

    public function preserve_configurations(array $ids)
    {
        $config = $this->config();
        $obtained = $this->obtained();
        $config->deleteMultiple(
            [
                'ratingConfigId' => $config->notIn($ids),
            ]
        );
        $obtained->deleteMultiple(
            [
                'ratingConfigId' => $obtained->notIn($ids),
            ]
        );
    }
}
