<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class SemanticLib
{
    private $knownTokens = false;
    private $newTokens = false;

    private function loadKnownTokens() // {{{
    {
        if (is_array($this->knownTokens)) {
            return;
        }

        $tikilib = TikiLib::lib('tiki');

        $this->knownTokens = [];

        $result = $tikilib->query("SELECT token, label, invert_token FROM tiki_semantic_tokens");
        while ($row = $result->fetchRow()) {
            $token = $row['token'];
            $this->knownTokens[$token] = $row;
        }

        ksort($this->knownTokens);
    } // }}}

    private function loadNewTokens() // {{{
    {
        if (is_array($this->newTokens)) {
            return;
        }

        $db = TikiDb::get();
        $result = $db->fetchAll("SELECT DISTINCT relation FROM tiki_object_relations WHERE relation LIKE 'tiki.link.%'");

        $tokens = [];
        foreach ($result as $row) {
            $tokens[] = substr($row['relation'], strlen('tiki.link.'));
        }

        $this->loadKnownTokens();
        $existing = array_keys($this->knownTokens);

        $this->newTokens = array_diff($tokens, $existing);
    } // }}}

    public function getToken($name, $field = null) // {{{
    {
        $this->loadKnownTokens();

        if (array_key_exists($name, $this->knownTokens)) {
            $data = $this->knownTokens[$name];

            if (is_null($field)) {
                return $data;
            }

            if (array_key_exists($field, $data)) {
                return $data[$field];
            }
        }

        return false;
    } // }}}

    public function getInvert($name, $field = null) // {{{
    {
        if (false !== $invert = $this->getToken($name, 'invert_token')) {
            if (empty($invert)) {
                $invert = $name;
            }

            return $this->getToken($invert, $field);
        }

        return false;
    } // }}}

    public function getTokens() // {{{
    {
        $this->loadKnownTokens();

        return $this->knownTokens;
    } // }}}

    public function getNewTokens() // {{{
    {
        $this->loadNewTokens();

        return $this->newTokens;
    } // }}}

    public function getAllTokens() // {{{
    {
        $this->loadKnownTokens();
        $this->loadNewTokens();

        return array_merge(array_keys($this->knownTokens), $this->newTokens);
    } // }}}

    public function getLinksUsing($token, $conditions = []) // {{{
    {
        $db = TikiDb::get();

        $token = (array) $token;
        $bindvars = [];

        // Multiple tokens can be fetched at the same time
        $values = [];
        foreach ($token as $name) {
            $values[] = "tiki.link.$name";
        }

        $mid = [ $db->in('relation', $values, $bindvars) ];

        // Filter on source and destination
        foreach ($conditions as $field => $value) {
            if ($field == 'fromPage') {
                $field = 'source_itemId';
            } elseif ($field == 'toPage') {
                $field = 'target_itemId';
            } else {
                continue;
            }

            $mid[] = "`$field` = ?";
            $bindvars[] = $value;
        }

        $mid = implode(' AND ', $mid);
        $result = $db->query(
            $q = "SELECT `source_itemId` `fromPage`, `target_itemId` `toPage`, GROUP_CONCAT(SUBSTR(`relation` FROM 11) SEPARATOR ',') `reltype` FROM tiki_object_relations WHERE $mid AND `source_type` = 'wiki page' AND `target_type` = 'wiki page' AND `relation` LIKE 'tiki.link.%' GROUP BY `fromPage`, `toPage` ORDER BY `fromPage`, `toPage`",
            $bindvars
        );

        $links = [];
        while ($row = $result->fetchRow()) {
            $row['reltype'] = explode(',', $row['reltype']);

            $links[] = $row;
        }

        return $links;
    } // }}}

    public function replaceToken($oldName, $newName, $label, $invert = null) // {{{
    {
        $exists = (false !== $this->getToken($oldName));

        if ($oldName != $newName && false !== $this->getToken($newName)) {
            return tra('Semantic token already exists') . ": $newName";
        }
        if (! $this->isValid($oldName)) {
            return tra('Invalid semantic token name') . ": $oldName";
        }
        if (! $this->isValid($newName)) {
            return tra('Invalid semantic token name') . ": $newName";
        }
        if (false === $this->getToken($invert) || $invert == $newName) {
            $invert = null;
        }

        $tikilib = TikiLib::lib('tiki');

        if ($exists) {
            $tikilib->query("DELETE FROM tiki_semantic_tokens WHERE token = ?", [ $oldName ]);
        }

        if (is_null($invert)) {
            $tikilib->query("INSERT INTO tiki_semantic_tokens (token, label) VALUES(?,?)", [ $newName, $label ]);
        } else {
            $tikilib->query("INSERT INTO tiki_semantic_tokens (token, label, invert_token) VALUES(?,?,?)", [ $newName, $label, $invert ]);
        }

        if ($oldName != '' && $newName != $oldName) {
            $tikilib->query("UPDATE tiki_semantic_tokens SET invert_token = ? WHERE invert_token = ?", [ $newName, $oldName ]);

            $this->replaceReferences($oldName, $newName);
        }

        unset($this->knownTokens[$oldName]);
        $this->knownTokens[$newName] = [
            'token' => $newName,
            'label' => $label,
            'invert_token' => $invert,
        ];
        ksort($this->knownTokens);

        return true;
    } // }}}

    private function replaceReferences($oldName, $newName = null) // {{{
    {
        $tikilib = TikiLib::lib('tiki');

        if (! $this->isValid($oldName)) {
            return tra('Invalid semantic token name') . ": $oldName";
        }
        if (! is_null($newName) && ! $this->isValid($newName) && $valid) {
            return tra('Invalid semantic token name') . ": $newName";
        }

        $links = $this->getLinksUsing($oldName);

        $pagesDone = [];
        foreach ($links as $link) {
            // Page body only needs to be replaced once
            if (! array_key_exists($link['fromPage'], $pagesDone)) {
                $info = $tikilib->get_page_info($link['fromPage']);
                $data = $info['data'];
                $data = str_replace("($oldName(", "($newName(", $data);

                $query = "update `tiki_pages` set `data`=?,`page_size`=? where `pageName`=?";
                $tikilib->query($query, [ $data, (int) strlen($data), $link['fromPage']]);

                $pagesDone[ $link['fromPage'] ] = true;
            }
        }

        if ($newName) {
            $tikilib->query('UPDATE tiki_object_relations SET relation = ? WHERE relation = ? AND source_type = "wiki page" AND target_type = "wiki page"', [ "tiki.link.$newName", "tiki.link.$oldName" ]);
        } else {
            $tikilib->query('DELETE FROM tiki_object_relations WHERE relation = ? AND source_type = "wiki page" AND target_type = "wiki page"', [ "tiki.link.$oldName" ]);
        }

        return true;
    } // }}}

    public function cleanToken($token) // {{{
    {
        $this->replaceReferences($token);

        $this->newTokens = array_diff($this->newTokens, [ $token ]);
    } // }}}

    public function removeToken($token, $removeReferences = false) // {{{
    {
        $tikilib = TikiLib::lib('tiki');

        if (false === $this->getToken($token)) {
            return tra("Semantic token not found") . ": $token";
        }

        $tikilib->query("DELETE FROM tiki_semantic_tokens WHERE token = ?", [ $token ]);

        unset($this->knownTokens[$token]);

        if ($removeReferences) {
            $this->replaceReferences($token, '');
        } elseif ($this->newTokens !== false) {
            $this->newTokens[] = $token;
        }

        return true;
    } // }}}

    public function renameToken($oldName, $newName) // {{{
    {
        $this->replaceReferences($oldName, $newName);

        $this->newTokens = array_diff($this->newTokens, [ $oldName ]);
        if (false === $this->getToken($newName)) {
            $this->newTokens[] = $newName;
        }
    } // }}}

    public function isValid($token) // {{{
    {
        return preg_match("/^[a-z0-9-]{1,15}\\z/", $token);
    } // }}}

    public function getRelationList($page) // {{{
    {
        $wikilib = TikiLib::lib('wiki');
        $tikilib = TikiLib::lib('tiki');
        $relations = [];

        $result = $tikilib->fetchAll("SELECT `target_itemId` `toPage`, SUBSTR(`relation` FROM 11) `reltype` FROM tiki_object_relations WHERE `source_itemId` = ? AND `source_type` = 'wiki page' AND `target_type` = 'wiki page' AND `relation` LIKE 'tiki.link.%'", [$page]);

        foreach ($result as $row) {
            if (false === $label = $this->getToken($row['reltype'], 'label')) {
                continue;
            }

            $label = tra($label);

            if (! array_key_exists($label, $relations)) {
                $relations[$label] = [];
            }

            if (! array_key_exists($row['toPage'], $relations[$label])) {
                $relations[$label][ $row['toPage'] ] = $wikilib->sefurl($row['toPage']);
            }
        }

        $result = $tikilib->fetchAll("SELECT `source_itemId` `fromPage`, SUBSTR(`relation` FROM 11) `reltype` FROM tiki_object_relations WHERE `target_itemId` = ? AND `source_type` = 'wiki page' AND `target_type` = 'wiki page' AND `relation` LIKE 'tiki.link.%'", [$page]);
        foreach ($result as $row) {
            if (false === $label = $this->getInvert($row['reltype'], 'label')) {
                continue;
            }

            $label = tra($label);

            if (! array_key_exists($label, $relations)) {
                $relations[$label] = [];
            }

            if (! array_key_exists($row['fromPage'], $relations[$label])) {
                $relations[$label][ $row['fromPage'] ] = $wikilib->sefurl($row['fromPage']);
            }
        }

        ksort($relations);
        foreach ($relations as &$set) {
            ksort($set);
        }

        return $relations;
    } // }}}

    public function getAliasContaining($query, $exact_match = false, $in_lang = null) // {{{
    {
        global $prefs;
        $tikilib = TikiLib::lib('tiki');

        $orig_query = $query;
        if (! $exact_match) {
            $query = "%$query%";
        }

        $mid = "((`target_type` = 'wiki page' AND `target_itemId` LIKE ?)";
        $bindvars = [$query];

        $prefixes = explode(',', $prefs["wiki_prefixalias_tokens"]);
        $haveprefixes = false;
        foreach ($prefixes as $p) {
            $p = trim($p);
            if (strlen($p) > 0 && TikiLib::strtolower(substr($query, 0, strlen($p))) == TikiLib::strtolower($p)) {
                $mid .= " OR ( `target_type` = 'wiki page' AND `target_itemId` LIKE ?)";
                $bindvars[] = "$p%";
                $haveprefixes = true;
            }
        }

        $mid .= ") AND ( `relation` = 'tiki.link.alias' ";

        if ($haveprefixes) {
            $mid .= " OR `relation` = 'tiki.link.prefixalias' ";
        }

        $mid .= ")";
        $querystring = "SELECT `source_itemId` `fromPage`, `target_itemId` `toPage` FROM `tiki_object_relations` WHERE $mid";
        $aliases = $tikilib->fetchAll($querystring, $bindvars);

        $aliases = $this->onlyKeepAliasesFromPageInLanguage($in_lang, $aliases);

        return $aliases;
    } // }}}

    public function onlyKeepAliasesFromPageInLanguage($language, $aliases)
    {
        $multilinguallib = TikiLib::lib('multilingual');
        if (! $language) {
            return $aliases;
        }

        $aliasesInCorrectLanguage = [];
        foreach ($aliases as $index => $aliasInfo) {
            $aliasLang = $multilinguallib->getLangOfPage($aliasInfo['fromPage']);
            if ($aliasLang === $language) {
                $aliasesInCorrectLanguage[] = $aliasInfo;
            }
        }
        //		echo "<pre>-- onlyKeepAliasesFromPageInLanguage: exiting</pre>\n";
        return $aliasesInCorrectLanguage;
    }

    public function getItemsFromTracker($page, $suffix)
    {
        $t_links = $this->getLinksUsing('trackerid', [ 'fromPage' => $page ]);
        $f_links = $this->getLinksUsing('titlefieldid', [ 'fromPage' => $page ]);
        $ret = [];
        if (count($t_links) && count($f_links) && ctype_digit($t_links[0]['toPage']) && ctype_digit($f_links[0]['toPage'])) {
            $trklib = TikiLib::lib('trk');
            $items = $trklib->list_items($t_links[0]['toPage'], 0, -1, '', '', $f_links[0]['toPage'], '', '', '', $suffix);
            foreach ($items["data"] as $i) {
                $ret[] = $i["itemId"];
            }
        }

        return $ret;
    }
}
