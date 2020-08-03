<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class Category_Manipulator
{
    private $objectType;
    private $objectId;

    private $current = [];
    private $managed = [];
    private $unmanaged = [];
    private $constraints = [
        'required' => [],
    ];
    private $new = [];

    private $prepared = false;
    private $overrides = [];
    private $overrideAll = false;

    public function __construct($objectType, $objectId)
    {
        $this->objectType = $objectType;
        $this->objectId = $objectId;
    }

    public function addRequiredSet(array $categories, $default, $filter = null, $type = null)
    {
        $categories = array_unique($categories);
        $this->constraints['required'][] = [
            'set' => $categories,
            'default' => $default,
            'filter' => $filter,
            'type' => $type
        ];
    }

    public function overrideChecks()
    {
        $this->overrideAll = true;
    }

    public function setCurrentCategories(array $categories)
    {
        $this->current = $categories;
    }

    public function setManagedCategories(array $categories)
    {
        $this->managed = $categories;
    }

    public function setUnmanagedCategories(array $categories)
    {
        $this->unmanaged = $categories;
    }

    public function setNewCategories(array $categories)
    {
        $this->new = $categories;
    }

    public function getAddedCategories()
    {
        $this->prepare();

        $attempt = array_diff($this->new, $this->current);

        return $this->filter($attempt, 'add_object');
    }

    public function getRemovedCategories()
    {
        $this->prepare();

        $attempt = array_diff($this->current, $this->new);

        return $this->filter($attempt, 'remove_object');
    }


    /*
     * Check wether the given permission is allowed for the given categories.
     * Note: The group in question requires also the _global_ permission 'modify_object_categories'
     * which could be given to a parent object like parent Tracker of a TrackerItem.
     * @param array $categories - requested categories
     * @param string  $permission - required permission for that category. Ie. 'add_category'
     * @return array $authorizedCategories - filterd list of given $categories that have proper permissions set.
     */
    private function filter($categories, $permission)
    {
        $objectperms = Perms::get(['type' => $this->objectType, 'object' => $this->objectId]);
        $canModifyObject = $objectperms->modify_object_categories;

        $out = [];
        foreach ($categories as $categ) {
            $perms = Perms::get(['type' => 'category', 'object' => $categ]);
            $hasCategoryPermission = $perms->$permission;

            if ($this->overrideAll || ($canModifyObject && $hasCategoryPermission) || in_array($categ, $this->overrides)) {
                $out[] = $categ;
            }
        }

        return $out;
    }


    private function prepare()
    {
        if ($this->prepared) {
            return;
        }

        $categories = $this->managed;
        Perms::bulk(['type' => 'category'], 'object', $categories);

        if (count($this->managed)) {
            $base = array_diff($this->current, $this->managed);
            $managed = array_intersect($this->new, $this->managed);
            $this->new = array_merge($base, $managed);
        }

        if (count($this->unmanaged)) {
            $base = array_intersect($this->current, $this->unmanaged);
            $managed = array_diff($this->new, $this->unmanaged);
            $this->new = array_merge($base, $managed);
        }

        $this->applyConstraints();

        $this->prepared = true;
    }

    private function applyConstraints()
    {
        foreach ($this->constraints['required'] as $constraint) {
            $set = $constraint['set'];
            $default = $constraint['default'];
            $filter = $constraint['filter'];
            $type = $constraint['type'];

            $interim = array_intersect($this->new, $set);

            if (! empty($type) && $type != $this->objectType) {
                return;
            }

            if (! empty($filter)) {
                $objectlib = TikiLib::lib('object');
                $info = $objectlib->get_info($this->objectType, $this->objectId);
                if (! preg_match($filter, $info['title'])) {
                    return;
                }
            }

            if (count($interim) == 0 && ! in_array($default, $this->new)) {
                $this->new[] = $default;
                $this->overrides[] = $default;
            }
        }
    }
}
