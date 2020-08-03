<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class Search_GlobalSource_AdvancedRatingSource implements Search_GlobalSource_Interface
{
    private $ratinglib;
    private $fields = null;
    private $recalculate = false;

    public function __construct($recalculate = false)
    {
        $this->ratinglib = TikiLib::lib('rating');
        $this->recalculate = $recalculate;
    }

    public function getProvidedFields()
    {
        if (is_null($this->fields)) {
            $ratingconfiglib = TikiLib::lib('ratingconfig');

            $this->fields = [];
            foreach ($ratingconfiglib->get_configurations() as $config) {
                $this->fields[] = "adv_rating_{$config['ratingConfigId']}";
            }
        }

        return $this->fields;
    }

    public function getGlobalFields()
    {
        return [];
    }

    public function getData($objectType, $objectId, Search_Type_Factory_Interface $typeFactory, array $data = [])
    {
        $ratings = $this->ratinglib->obtain_ratings($objectType, $objectId, $this->recalculate);

        $data = [];

        foreach ($ratings as $id => $value) {
            $data["adv_rating_$id"] = $typeFactory->sortable($value);
        }

        return $data;
    }
}
