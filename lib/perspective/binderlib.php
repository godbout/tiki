<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

// For documentation how to use this file please see the comment at the end of this file

//this script may only be included - so its better to die if called  directly.
if (strpos($_SERVER["SCRIPT_NAME"], basename(__FILE__)) !== false) {
    header("location: index.php");
    exit;
}

use Tiki\Sections as Sections;

$categlib = TikiLib::lib('categ');

class AreasLib extends CategLib
{
    private $areas;
    private $areasArray;

    public function __construct()
    {
        $this->areas = $this->table('tiki_areas');
        $this->cacheAreas();

        parent::__construct();
    }

    public function getPerspectiveByObjectAndCategories($current_object, $objectCategoryIds)
    {
        global $prefs;

        if (! $current_object || empty($objectCategoryIds)) {    // only used on tiki objects
            return;
        }

        $perspectivelib = TikiLib::lib('perspective');
        $accesslib = TikiLib::lib('access');

        $descendants = $this->get_category_descendants($prefs['areas_root']);

        $objectPerspective = 0;
        if (! empty($objectCategoryIds)) {
            foreach ($objectCategoryIds as $categId) {
                // If category is inside $prefs['areas_root']
                if (in_array($categId, $descendants)) {
                    $area = $this->getAreaByCategId($categId);

                    if ($area) {
                        $objectPerspective = $area['perspectives'][0]; // use 1st persp

                        break;
                    }
                }
            }
        }

        return $objectPerspective;
    }

    public function HandleObjectCategories($objectCategoryIds)
    {
        global $prefs;
        $perspectivelib = TikiLib::lib('perspective');
        $accesslib = TikiLib::lib('access');

        $current_object = Sections::currentObject();

        if (! $current_object) {	// only used on tiki objects
            return;
        }

        $descendants = $this->get_category_descendants($prefs['areas_root']);

        $objectPerspective = 0;
        if (! empty($objectCategoryIds)) {
            if (! isset($_SESSION['current_perspective'])) {
                unset($_SESSION['current_perspective']);
            }
            foreach ($objectCategoryIds as $categId) {
                // If category is inside $prefs['areas_root']
                if (in_array($categId, $descendants)) {
                    $area = $this->getAreaByCategId($categId);

                    if ($area) {
                        $objectPerspective = $area['perspectives'][0]; // use 1st persp

                        break;
                    }
                }
            }
            if ($objectPerspective && $objectPerspective != $_SESSION['current_perspective']) {
                $area = $this->getAreaByPerspId($_SESSION['current_perspective']);
                $objectArea = $this->getAreaByPerspId($objectPerspective);

                if (($area && ! $area['share_common']) || ($objectArea && $objectArea['exclusive'])) {
                    $perspectivelib->set_perspective($objectPerspective, true);
                    $accesslib->redirect($accesslib->selfUrl(), '', 301);
                }
            }
        }
        if ($objectPerspective < 1 && ! empty($_SESSION['current_perspective'])) { // uncategorised objects
            $area = $this->getAreaByPerspId($_SESSION['current_perspective']);
            if ($area) {
                if (! $area['share_common']) {
                    $perspectivelib->set_perspective($objectPerspective, true);

                    if ($prefs['multidomain_switchdomain'] == 'y') {
                        // When running as multi-domain, with switch domain active, un categorized content (without one
                        // category inside the jail for the perspective) can only be displayed in the current domain if
                        // the perspective is marked as share_common.
                        // So we need to fall back to the default domain for multi domain or just fail - if we can't
                        // guess the domain, as we would end up in an endless redirect loop
                        foreach ($perspectivelib->get_domain_map() as $domain => $persp) {
                            if (isset($_SERVER['HTTP_HOST']) && $domain == $_SERVER['HTTP_HOST']) {
                                if (! empty($prefs['multidomain_default_not_categorized'])
                                    && ! in_array($prefs['multidomain_default_not_categorized'], array_keys($perspectivelib->get_domain_map()))
                                ) {
                                    $saveHttpHost = $_SERVER['HTTP_HOST'];
                                    // selfUrl uses HTTP_HOST, and redirect will exit after redirect, so no problem on "tampering" with $_SERVER
                                    $_SERVER['HTTP_HOST'] = $prefs['multidomain_default_not_categorized'];
                                    $accesslib->redirect($accesslib->selfUrl(), '', 301);
                                    $_SERVER['HTTP_HOST'] = $saveHttpHost; // this should never be reach
                                } else {
                                    $accesslib->display_error(
                                        $accesslib->selfUrl(),
                                        tra("Perspective misconfiguration"),
                                        500,
                                        false,
                                        tra('The resource you requested is not available in the current perspective, and the system administrator did not define a default domain to redirect to. Please contact your system administrator, or check the documentation in <a href="http://doc.tiki.org/Perspectives">http://doc.tiki.org/Perspectives</a> related with multi-domain configurations.')
                                    );
                                }
                            }
                        }
                    }

                    $accesslib->redirect($accesslib->selfUrl(), '', 301);
                }
            }
        }
    }

    public function getAreaByCategId($categId, $enabled = true)
    {
        foreach ($this->areasArray as $area) {
            if ($area['enabled'] == $enabled && $categId == $area['categId']) {
                return $area;
            }
        }

        return [];
    }

    public function getAreaByPerspId($perspid, $enabled = true)
    {
        foreach ($this->areasArray as $area) {
            if ($area['enabled'] == $enabled && in_array($perspid, $area['perspectives'])) {
                return $area;
            }
        }

        return [];
    }

    /**
     * @param bool $reload	force reload from database
     *
     * Sets up a cached version of the table with proper arrays and bools
     */
    private function cacheAreas($reload = false)
    {
        if ($reload || empty($this->areasArray)) {
            $this->areasArray = [];
            $res = $this->areas->fetchAll($this->areas->all());
            foreach ($res as & $row) {
                $row['perspectives'] = unserialize($row['perspectives']);
                $row['enabled'] = ($row['enabled'] === 'y');
                $row['exclusive'] = ($row['exclusive'] === 'y');
                $row['share_common'] = ($row['share_common'] === 'y');
            }
            $this->areasArray = $res;
        }
    }

    public function update_areas()
    {
        global $prefs;
        $this->areas->deleteMultiple([]);	// empty areas table before rebuilding
        $areas = [];
        $descendants = $this->get_category_descendants($prefs['areas_root']);
        if (is_array($descendants)) {
            foreach ($descendants as $item) { // it only should be just one perspective assigned
                $areas[$item] = [];
            }
            $result = $this->fetchAll("SELECT `perspectiveId`, `pref`, `value` FROM tiki_perspective_preferences WHERE pref = 'category_jail'", []);
            if (count($result) != 0) {
                foreach ($result as $row) {
                    $categs = unserialize($row['value']);
                    foreach ($categs as $item) {
                        if (array_key_exists($item, $areas)) {
                            $areas[$item][] = $row['perspectiveId'];
                        }
                    }
                }

                foreach (array_filter($areas) as $key => $item) { // don't bother with categs with no perspectives
                    $data = [];
                    // update checkboxes from form
                    $data['enabled'] = ! empty($_REQUEST['enabled'][$key]) ? 'y' : 'n';
                    $data['exclusive'] = ! empty($_REQUEST['exclusive'][$key]) ? 'y' : 'n';
                    $data['share_common'] = ! empty($_REQUEST['share_common'][$key]) ? 'y' : 'n';

                    $this->bind_area($key, $item, $data);
                }
            } else {
                return tra("No category jail set in any perspective.");
            }
            $this->cacheAreas(true); // recache the whole table

            return true;
        }

        return tra("Areas root category ID") . " " . tra("is invalid.");
    }

    public function bind_area($categId, $perspectiveIds, $data = [])
    {
        $perspectiveIds = (array)$perspectiveIds;
        $conditions = ['categId' => $categId];
        $data['perspectives'] = serialize($perspectiveIds);

        if ($this->areas->fetchCount($conditions)) {
            $this->areas->update($data, $conditions);
        } else {
            $this->areas->insert(array_merge($data, $conditions));
        }
    }
} // class end

/*-----------------------------------------------
+++ Description of Perspective Binder / Areas +++
-------------------------------------------------

Much of this will become out of date for tiki 10 (jb)

----------------------
+++ Configurations +++
----------------------

What Areas does is make it such that an object (wiki page etc...) is always loaded in a particular perspective. The following steps describe where to set the necessary parameters. There is an admin panel for this feature.

Step 1 of 5
-----------

Turn on Areas feature in admin panel

Step 2 of 5
-----------

In Tiki you need to setup a structure of categories:
A basic category must be a "top category" with no parent.
You can name it for example "Areas"
Then you create one or several child categories of this category, wich you can name "Area1", "Area2", "Area3", etc.

The id of the parent category you can type in the text field areas root id in the category admin panel.

Step 3 of 5
-----------

Please activate "categories used in templates" in your Tiki installation:
Admin->Categories check tick box "categories used in templates.

To satisfy this step feature_areas is set to depend on categories_used_in_tpl.

Step 4 of 5
-----------

Setup one perspective for each of the categories in the areas-structure.
Assign one category Id of this structure to the category jail of the related perspective, in the way that each perspective Id=X has one category Id=Y in its jail to bind exact one category and one perspective together.

Step 5 of 5
-----------

In the Areas admin panel, you need to click on "Update areas" everytime you add or remove a category under the Areas "root" category defined above.

--------------------------
+++ using the feature: +++
--------------------------

Once you proceeded all steps, you can assign objects like wikipages to ONE of the areas-categories and it will always be shown in the related perspective.

Thus you can built up "areas" as "subwebsites" and the (categorised) content, you call in the browser, will always be visible in the "environment" of theme, modules, etc., that you defined for the related perspective.

If you do not assign a content object to one of the areas-categories, it will still be visible in every perspective.

You can still assign objects to several categories, but please only assign to one of the areas-category structure, to make Tiki not confused what area an object is assigned to. You CAN assign objects only to one single area, OR leave it visible in all perspectives like it is the default. You CANNOT assign objects to several areas.

Now you can combine very specific content, that mainly makes sense in a specific context and is always automatically adressed in the related perspective, together with global content like imprints or general information, wich remains accessible in all perspectives across the whole Tiki website.

Examples of usage would be:

* Project related content, that only should be visible in the perspective and context of the specific project.
* Subwebsites of local groups
a) that should not appear on the national website
b) whith content that automatically should be adressed to the right local website without cryptical urls, even if the same Tiki installation is shared for several groups
* News Websites with specific regional or local related content and common content in the same Tiki installation
* List may be continued over time

--------------------------
+++ End of Description +++
------------------------*/
