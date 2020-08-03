<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class Tiki_Profile_Writer_SearchFieldHelper
{
    private $mapping = [];

    public function addGlobalSource(Search_GlobalSource_Interface $source)
    {
        $this->addProvider($source);
    }

    public function addContentSource($objectType, Search_ContentSource_Interface $source)
    {
        $this->addProvider($source);
    }

    private function addProvider($source)
    {
        if ($source instanceof Tiki_Profile_Writer_ReferenceProvider) {
            $this->mapping = array_merge($this->mapping, $source->getReferenceMap());
        }
    }

    public function getTypeForField($field)
    {
        if (isset($this->mapping[$field])) {
            return $this->mapping[$field];
        }
    }

    public function replaceFilterReferences(Tiki_Profile_Writer $writer, $args)
    {
        if (isset($args['categories'])) {
            $args['categories'] = Tiki_Profile_Writer_Helper::uniform_string('category', $writer, $args['categories']);
        }

        if (isset($args['deepcategories'])) {
            $args['deepcategories'] = Tiki_Profile_Writer_Helper::uniform_string('category', $writer, $args['deepcategories']);
        }

        if (isset($args['content'], $args['field'])) {
            // Expect all fields to be compatible, use the first one
            $field = explode(',', $args['field'])[0];
            if ($type = $this->getTypeForField($field)) {
                $args['content'] = Tiki_Profile_Writer_Helper::uniform_string($type, $writer, $args['content']);
            }
        }

        if (isset($args['relation'], $args['objecttype'])) {
            $args['relation'] = Tiki_Profile_Writer_Helper::uniform_string($args['objecttype'], $writer, $args['relation']);
        }

        return $args;
    }

    public function replaceStepReferences(Tiki_Profile_Writer $writer, $args)
    {
        if (isset($args['action'], $args['field'], $args['value']) && $args['action'] == 'tracker_item_modify') {
            $trklib = TikiLib::lib('trk');
            $field = $trklib->get_field_by_perm_name($args['field']);
            if ($field && isset($field['type']) && $field['type'] == 'e') { // category field
                $args['value'] = Tiki_profile_Writer_Helper::uniform_string('category', $writer, $args['value']);
            }
        }

        return $args;
    }
}
