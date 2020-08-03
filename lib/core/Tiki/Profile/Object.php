<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class Tiki_Profile_Object
{
    private $data;
    private $profile;
    private $id = false;

    private $references = null;

    public static function serializeNamedObject($object) // {{{
    {
        if (strpos($object['domain'], '://') === false) {
            if (is_dir($object['domain'])) {
                $object['domain'] = "file://" . $object['domain'];
            } else {
                $object['domain'] = "http://" . $object['domain'];
            }
        }

        return sprintf("%s#%s", Tiki_Profile::getProfileKeyfor($object['domain'], $object['profile']), $object['object']);
    } // }}}

    public static function getNamedObjects() // {{{
    {
        global $tikilib;

        $objects = [];

        $result = $tikilib->query("SELECT domain, profile, object FROM tiki_profile_symbols WHERE named = 'y'");
        while ($row = $result->fetchRow()) {
            $objects[] = $row;
        }

        return $objects;
    } // }}}

    public function __construct(&$data, Tiki_Profile $profile) // {{{
    {
        $this->data = &$data;
        $this->profile = $profile;
    } // }}}

    public function getDescription() // {{{
    {
        $str = '';
        if ($this->isWellStructured()) {
            $str .= $this->getType() . ' ';
            $name = isset($this->data['data']['name']) ? $this->data['data']['name'] : tra('No name');
            $str .= '"' . $name . '"';
        } else {
            $str .= tra('Bad object');
        }

        return $str;
    } // }}}

    public function isWellStructured() // {{{
    {
        $is = isset($this->data['type'], $this->data['data']);

        return $is;
    } // }}}

    public function getType() // {{{
    {
        return $this->data['type'];
    } // }}}

    public function getRef() // {{{
    {
        if (isset($this->data['ref'])) {
            return trim($this->data['ref']);
        }
    } // }}}

    public function getValue() // {{{
    {
        return $this->id;
    } // }}}

    public function setValue($value) // {{{
    {
        $this->id = $value;

        $named = 'y';
        if (! $name = $this->getRef()) {
            $name = uniqid();
            $named = 'n';
        }

        $this->profile->setSymbol($this->getType(), $name, $this->id, $named);
    } // }}}

    public function getInternalReferences() // {{{
    {
        if (! is_null($this->references)) {
            return $this->references;
        }

        $this->references = $this->traverseForReferences($this->data);

        return $this->references;
    } // }}}

    public function getData() // {{{
    {
        if (array_key_exists('data', $this->data)) {
            return $this->data['data'];
        }

        return [];
    } // }}}

    public function replaceReferences(&$data, $suppliedUserData = false) // {{{
    {
        $this->profile->replaceReferences($data, $suppliedUserData);
    } // }}}

    private function traverseForReferences($value) // {{{
    {
        $array = [];
        if (is_array($value)) {
            foreach ($value as $v) {
                $array = array_merge($array, $this->traverseForReferences($v));
            }
        } elseif (preg_match(Tiki_Profile::SHORT_PATTERN, $value, $parts)) {
            $ref = $this->profile->convertReference($parts);
            if ($this->profile->domain == $ref['domain']
                && $this->profile->profile == $ref['profile']) {
                $array[] = $ref['object'];
            }
        } elseif (preg_match_all(Tiki_Profile::LONG_PATTERN, $value, $parts, PREG_SET_ORDER)) {
            foreach ($parts as $row) {
                $ref = $this->profile->convertReference($row);
                if ($this->profile->domain == $ref['domain']
                && $this->profile->profile == $ref['profile']) {
                    $array[] = $ref['object'];
                }
            }
        }

        return $array;
    } // }}}

    public function getProfile() // {{{
    {
        return $this->profile;
    } // }}}

    public function __get($name) // {{{
    {
        if (array_key_exists($name, $this->data['data'])) {
            return $this->data['data'][$name];
        }
    } // }}}
}
