<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

use Tiki\Package\ExtensionManager;
use Tiki\Yaml\Directives as YamlDirectives;
use Tiki\Yaml\Filter\ReplaceUserData as YamlReplaceUserData;

class Tiki_Profile_Installer
{
    public static function exportGroup(Tiki_Profile_Writer $writer, $group, $categories = false, $objects = false) // {{{
    {
        $userlib = \TikiLib::lib('user');
        $info = $userlib->get_group_info($group);

        if (empty($info['id'])) {
            return false;
        }

        $data = [
            'description' => $info['groupDesc'],
            'home' => $info['groupHome'],
            'user_tracker' => $writer->getReference('tracker', $info['usersTrackerId']),
            'group_tracker' => $writer->getReference('tracker', $info['groupTrackerId']),
            'user_tracker_field' => $writer->getReference('tracker_field', $info['usersFieldId']),
            'group_tracker_field' => $writer->getReference('tracker_field', $info['groupFieldId']),
            'registration_fields' => $writer->getReference('tracker_field', array_filter(explode(':', $info['registrationUsersFieldIds']))),
            'user_signup' => $info['userChoice'],
            'default_category' => $writer->getReference('category', $info['groupDefCat']),
            'theme' => $info['groupTheme'],
            'color' => $info['groupColor'],
            'is_external' => $info['isExternal'],
            'expire_after' => $info['expireAfter'],
            'email_pattern' => $info['emailPattern'],
            'anniversary' => $info['anniversary'],
            'prorate_interval' => $info['prorateInterval'],
            'allow' => [],
            'objects' => [],
        ];

        foreach ($info['perms'] as $perm) {
            // Skip tiki_p_
            $data['allow'][] = substr($perm, 7);
        }

        if ($categories) {
            $data['objects'] = self::getPermissionList($writer, 'category', $group);
        }

        if ($objects) {
            $data['objects'] = array_merge(
                $data['objects'],
                self::getPermissionList($writer, 'wiki page', $group),
                self::getPermissionList($writer, 'tracker', $group),
                self::getPermissionList($writer, 'forum', $group)
            );
        }

        // Clean and store
        $data = array_filter($data);
        $writer->addPermissions($group, $data);

        return true;
    } // }}}

    private static function getPermissionList($writer, $objectType, $group) // {{{
    {
        switch ($objectType) {
            case 'category':
                $sub = "SELECT MD5(CONCAT('category', categId)) hash, categId objectId FROM tiki_categories";

                break;
            case 'forum':
                $sub = "SELECT MD5(CONCAT('forum', forumId)) hash, forumId objectId FROM tiki_forums";

                break;
            case 'tracker':
                $sub = "SELECT MD5(CONCAT('tracker', trackerId)) hash, trackerId objectId FROM tiki_trackers";

                break;
            case 'wiki page':
                $sub = "SELECT MD5(CONCAT('wiki page', LOWER(pageName))) hash, pageName objectId FROM tiki_pages";

                break;
            default:
                return [];
        }

        $db = TikiDb::get();
        $result = $db->fetchAll("
		SELECT i.objectId, permName
		FROM users_objectpermissions p
			INNER JOIN ($sub) i ON i.hash = p.objectId
		WHERE p.objectType = ? AND p.groupName = ?
		", [$objectType, $group]);

        $map = [];
        foreach ($result as $row) {
            $id = $row['objectId'];
            if (! isset($map[$id])) {
                $map[$id] = [
                    'type' => $objectType,
                    'id' => $writer->getReference($objectType, $id),
                    'allow' => [],
                ];
            }

            // Strip tiki_p_
            $map[$id]['allow'][] = substr($row['permName'], 7);
        }

        return array_values($map);
    } // }}}

    private $installed = [];
    private $handlers = [
        'tracker' => 'Tiki_Profile_InstallHandler_Tracker',
        'tracker_field' => 'Tiki_Profile_InstallHandler_TrackerField',
        'tracker_item' => 'Tiki_Profile_InstallHandler_TrackerItem',
        'tracker_option' => 'Tiki_Profile_InstallHandler_TrackerOption',
        'wiki_page' => 'Tiki_Profile_InstallHandler_WikiPage',
        'category' => 'Tiki_Profile_InstallHandler_Category',
        'categorize' => 'Tiki_Profile_InstallHandler_Categorize',
        'file_gallery' => 'Tiki_Profile_InstallHandler_FileGallery',
        'module' => 'Tiki_Profile_InstallHandler_Module',
        'menu' => 'Tiki_Profile_InstallHandler_Menu',
        'menu_option' => 'Tiki_Profile_InstallHandler_MenuOption',
        'blog' => 'Tiki_Profile_InstallHandler_Blog',
        'blog_post' => 'Tiki_Profile_InstallHandler_BlogPost',
        'plugin_alias' => 'Tiki_Profile_InstallHandler_PluginAlias',
        'webservice' => 'Tiki_Profile_InstallHandler_Webservice',
        'webservice_template' => 'Tiki_Profile_InstallHandler_WebserviceTemplate',
        'rss' => 'Tiki_Profile_InstallHandler_Rss',
        'article_topic' => 'Tiki_Profile_InstallHandler_ArticleTopic',
        'article_type' => 'Tiki_Profile_InstallHandler_ArticleType',
        'article' => 'Tiki_Profile_InstallHandler_Article',
        'forum' => 'Tiki_Profile_InstallHandler_Forum',
        'template' => 'Tiki_Profile_InstallHandler_Template',
        'perspective' => 'Tiki_Profile_InstallHandler_Perspective',
        'users' => 'Tiki_Profile_InstallHandler_User',
        // keeping 'users' as well as 'user' that was the previous behaviour (up to Tiki 6)
        // so as to support existing profiles
        'user' => 'Tiki_Profile_InstallHandler_User',
        'datachannel' => 'Tiki_Profile_InstallHandler_DataChannel',
        'transition' => 'Tiki_Profile_InstallHandler_Transition',
        'calendar' => 'Tiki_Profile_InstallHandler_Calendar',
        'extwiki' => 'Tiki_Profile_InstallHandler_ExtWiki',
        'webmail' => 'Tiki_Profile_InstallHandler_Webmail',
        'sheet' => 'Tiki_Profile_InstallHandler_Sheet',
        'scheduler' => 'Tiki_Profile_InstallHandler_Scheduler',
        'rating_config' => 'Tiki_Profile_InstallHandler_RatingConfig',
        'rating_config_set' => 'Tiki_Profile_InstallHandler_RatingConfigSet',
        'area_binding' => 'Tiki_Profile_InstallHandler_AreaBinding',
        'activity_stream_rule' => 'Tiki_Profile_InstallHandler_ActivityStreamRule',
        'activity_rule_set' => 'Tiki_Profile_InstallHandler_ActivityRuleSet',
        'goal' => 'Tiki_Profile_InstallHandler_Goal',
        'goal_set' => 'Tiki_Profile_InstallHandler_GoalSet',
    ];

    private static $typeMap = [
        'wiki_page' => 'wiki page',
        'file_gallery' => 'file gallery',
        'tracker_item' => 'trackeritem',
    ];

    private static $typeMapInvert = [
        'wiki page' => 'wiki_page',
        'wiki' => 'wiki_page',
        'fgal' => 'file_gallery',
        'file gallery' => 'file_gallery',
        'trackeritem' => 'tracker_item',
        'tracker item' => 'tracker_item',
    ];

    private $userData = false;
    private $debug = false;
    private $prefixDependencies = true;

    private $profileChanges = [];
    private $feedback = [];	// Let users know what's happened

    private $allowedGlobalPreferences = false;
    private $allowedObjectTypes = false;

    /**
     * @param $feed - (strings append, array replaces) lines of feedback text
     * @return none
     */
    public function setFeedback($feed) // {{{
    {
        if (is_array($feed)) {
            $this->feedback = $feed;
        } else {
            $this->feedback[] = $feed;
        }
    } // }}}

    /**
     * @param $index - (int) index of feedback string to return if present
     * @return mixed string or whole array if no index specified
     */
    public function getFeedback($index = null) // {{{
    {
        if (! is_null($index) && $index < count($this->feedback)) {
            return $this->feedback[$index];
        }

        return $this->feedback;
    } // }}}

    /**
     * Add changes to the tracking list for profile changes
     *
     * @param $type - string with feedback type
     * @param $newValue - mixed with new values
     * @param $oldValue - mixed with old values
     * @param $description - mixed with aditional information
     * @return none
     */
    public function setTrackProfileChanges($type, $newValue = false, $oldValue = false, $description = false)
    {
        $this->profileChanges[] = [
            'type' => $type,
            'new' => $newValue,
            'old' => $oldValue,
            'description' => $description,
        ];
    }

    /**
     * Return the list of changes applied by the profile
     *
     * @param int|null $index - index of feedback string to return if present
     * @return mixed string or whole array if no index specified
     */
    public function getTrackProfileChanges($index = null)
    {
        if (! is_null($index) && $index < count($this->profileChanges)) {
            return $this->profileChanges[$index];
        }

        return $this->profileChanges;
    }


    public static function convertType($type) // {{{
    {
        if (isset(self::$typeMap[$type])) {
            return self::$typeMap[$type];
        }

        return $type;
    } // }}}

    /**
     * Converts a Tiki object type to a profile object type.
     * @param mixed $type
     */
    public static function convertTypeInvert($type) // {{{
    {
        $typeMap = self::$typeMapInvert;

        if (isset($typeMap[$type])) {
            return $typeMap[$type];
        }

        return $type;
    } // }}}

    public static function convertObject($type, $id, $contextualizedInfo = []) // {{{
    {
        global $tikilib;

        if ($type == 'wiki page' && is_numeric($id)) {
            return $tikilib->get_page_name_from_id($id);
        } elseif ($type == 'group' && isset($contextualizedInfo['groupMap'])) {
            if (isset($contextualizedInfo['groupMap'][$id])) {
                return $contextualizedInfo['groupMap'][$id];
            }

            return $id;
        }

        return $id;
    } // }}}

    public function __construct() // {{{
    {
        global $tikilib;

        $result = $tikilib->fetchAll("SELECT DISTINCT `domain`, `profile` FROM `tiki_profile_symbols`");
        foreach ($result as $row) {
            $this->installed[Tiki_Profile::getProfileKeyFor($row['domain'], $row['profile'])] = true;
        }
    } // }}}

    public function setUserData($userData) // {{{
    {
        $this->userData = $userData;
    } // }}}

    public function setDebug() // {{{
    {
        $this->debug = true;
    } // }}}

    public function disablePrefixDependencies() // {{{
    {
        $this->prefixDependencies = false;
    } // }}}

    public function enablePrefixDependencies() // {{{
    {
        $this->prefixDependencies = true;
    } // }}}

    public function getInstallOrder(Tiki_Profile $profile) // {{{
    {
        if ($profile == null) {
            return false;
        }

        // Obtain the list of all required profiles
        $dependencies = $profile->getRequiredProfiles(true);
        $dependencies[$profile->getProfileKey()] = $profile;

        $referenced = [];
        $knownObjects = [];
        foreach (Tiki_Profile_Object::getNamedObjects() as $o) {
            $knownObjects[] = Tiki_Profile_Object::serializeNamedObject($o);
        }

        // Build the list of dependencies for each profile
        $short = [];
        foreach ($dependencies as $key => $prf) {
            if (empty($prf)) {
                throw new Exception("Unknown objects are referenced: " . $key);
            }

            $short[$key] = [];
            foreach ($prf->getRequiredProfiles() as $k => $p) {
                $short[$key][] = $k;
            }

            foreach ($prf->getNamedObjects() as $o) {
                $knownObjects[] = Tiki_Profile_Object::serializeNamedObject($o);
            }
            foreach ($prf->getReferences() as $o) {
                if (empty($o['object'])) {
                    continue;
                }
                $referenced[] = Tiki_Profile_Object::serializeNamedObject($o);
            }

            if (! $this->isInstallable($prf)) {
                return false;
            }
        }

        // Make sure all referenced objects actually exist
        $remain = array_diff($referenced, $knownObjects);
        if (! empty($remain)) {
            throw new Exception("Unknown objects are referenced: " . implode(', ', $remain));
        }

        // Build the list of packages that need to be installed
        $toSequence = [];
        foreach ($dependencies as $key => $prf) {
            if (! $this->isInstalled($prf, $key == $profile->getProfileKey() || $this->prefixDependencies)) {
                $toSequence[] = $key;
            }
        }

        // Order the packages to make sure all dependencies are met
        $toInstall = [];
        $counter = 0;
        while (count($toSequence)) {
            // If all packages were tested and no order was found, exit
            // Probably means there is a circular dependency
            if ($counter++ > count($toSequence) * 2) {
                throw new Exception("Profiles could not be ordered: " . implode(", ", $toSequence));
            }

            $key = reset($toSequence);

            // Remove packages that are already scheduled or installed from dependencies
            $short[$key] = array_diff($short[$key], array_keys($this->installed), $toInstall);

            $element = array_shift($toSequence);
            if (count($short[$key])) {
                $toSequence[] = $element;
            } else {
                $counter = 0;
                $toInstall[] = $element;
            }
        }

        $final = [];
        // Perform the actual install
        foreach ($toInstall as $key) {
            $final[] = $dependencies[$key];
        }

        return $final;
    } // }}}

    /**
     * Install a profile
     *
     * @param Tiki_Profile $profile Profile object
     * @param string $empty_cache all|templates_c|temp_cache|temp_public|modules_cache|prefs (default all)
     * @param bool $dryRun set to tru to run the install without applying the profile
     * @return bool
     */
    public function install(Tiki_Profile $profile, $empty_cache = 'all', $dryRun = false) // {{{
    {
        global $tikidomain;
        $cachelib = TikiLib::lib('cache');
        $tikilib = TikiLib::lib('tiki');

        try {
            // Apply directives, note Directives should be and are a runtime thing
            $yamlDirectives = new YamlDirectives(new YamlReplaceUserData($profile, $this->userData), $profile->getPath());
            $data = $profile->getData();
            $yamlDirectives->process($data);
            $profile->setData($data);
            $profile->fetchExternals(); // there might be new externals as a result of the directives processing


            $profile->getObjects(); // need to be refreshed before installation in case any have changed due to replacements

            if (! $profiles = $this->getInstallOrder($profile)) {
                return false;
            }

            $tikiVersion = TikiInit::getContainer()->getParameter('tiki.version');
            foreach ($profiles as $p) {
                if (! $p->isCompatible($tikiVersion)) {
                    $message = tr('Tiki (%0) is not satisfiable by profile required Tiki version (%1)', $tikiVersion, $p->getTikiSupportedVersions());

                    throw new Exception($message);
                }
            }

            foreach ($profiles as $p) {
                $this->doInstall($p, $dryRun);
            }

            if (count($this->getFeedback()) == count($profiles)) {
                $this->setFeedback(tra('Nothing was changed. Please check the profile for errors'));
            }
            $cachelib->empty_cache($empty_cache, 'profile');

            return true;
        } catch (Exception $e) {
            $this->setFeedback(tra('An error occurred: ') . $e->getMessage());

            return false;
        }
    } // }}}

    public function isInstalled(Tiki_Profile $profile, $prefix = true) // {{{
    {
        return array_key_exists($profile->getProfileKey($prefix), $this->installed);
    } // }}}

    public function isKeyInstalled($domain, $profile) // {{{
    {
        return array_key_exists(Tiki_Profile::getProfileKeyFor($domain, $profile), $this->installed);
    } // }}}

    public function isInstallable(Tiki_Profile $profile) // {{{
    {
        foreach ($profile->getObjects() as $object) {
            $handler = $this->getInstallHandler($object);
            if (! $handler) {
                throw new Exception("No handler found for object type {$object->getType()} in {$profile->domain}:{$profile->profile}");
            }

            if (! $handler->canInstall()) {
                throw new Exception("Object (#{$object->getRef()}) of type {$object->getType()} in {$profile->domain}:{$profile->profile} does not validate");
            }
        }

        return true;
    } // }}}

    public function getInstallHandler(Tiki_Profile_Object $object) // {{{
    {
        $type = $object->getType();
        if (array_key_exists($type, $this->handlers)) {
            if ($this->allowedObjectTypes !== false && ! in_array($type, $this->allowedObjectTypes)) {
                return null;
            }

            $class = $this->handlers[$type];
            if (class_exists($class)) {
                return new $class($object, $this->userData);
            }
        }
    } // }}}

    private function doInstall(Tiki_Profile $profile, $dryRun = false) // {{{
    {
        $this->setFeedback(tra('Applying profile') . ': ' . $profile->profile);

        $this->installed[$profile->getProfileKey()] = $profile;

        $preferences = $profile->getPreferences();
        $packages = $profile->getPackages();

        $leftovers = $this->applyPreferences($profile, $preferences, true, $dryRun);
        $this->applyPackages($packages, $dryRun);

        require_once 'lib/setup/events.php';
        tiki_setup_events();

        $userhandlers = [];
        foreach ($profile->getObjects() as $object) {
            $installer = $this->getInstallHandler($object);
            $description = $object->getDescription();
            if ($installer instanceof Tiki_Profile_InstallHandler_User) {
                // postpone installation of users till after groups/perms are set
                $userhandlers[$description] = $installer;
                $this->setTrackProfileChanges('user', $object, false, $description);

                continue;
            }
            $installedOldValue = false;
            $type = $object->getType();
            $class = ! empty($this->handlers[$type]) ? $this->handlers[$type] : null;
            if (! $dryRun && $class && class_exists($class) && ! empty($this->userData)) {
                $classHandler = new $class($object, $this->userData);
                if (is_callable([$classHandler, 'getData'])) {
                    $handlerData = $classHandler->getData();
                    if (! empty($handlerData) && is_callable([$classHandler, 'getCurrentData'])) {
                        $installedOldValue = $classHandler->getCurrentData($handlerData);
                    }
                }
            }
            if ($dryRun) {
                $this->setTrackProfileChanges('installer', $object, false, $description);
            }
            

            if (! $dryRun) {
                $installer->install();
                $installer->replaceReferences($description);
                $logOldValue = false;
                if (! $installer instanceof Tiki_Profile_InstallHandler_User) {
                    if (! empty($installedOldValue) && is_callable([$classHandler, 'getChanges'])) {
                        $oldValue = $classHandler->getCurrentData($handlerData);
                        $logOldValue = $classHandler->getChanges($installedOldValue, $oldValue);
                    }
                }
                $this->setTrackProfileChanges('installer', $object, $logOldValue, $description);
                $this->setFeedback(tra('Added (or modified)') . ': ' . $description);
            }
        }
        $groupMap = $profile->getGroupMap();
        $profile->replaceReferences($groupMap, $this->userData);

        $permissions = $profile->getPermissions($groupMap);
        $profile->replaceReferences($permissions, $this->userData);
        foreach ($permissions as $groupName => $info) {
            $this->setFeedback(tra('Group changed (or modified)') . ': ' . $groupName);
            $this->setupGroup($groupName, $info['general'], $info['permissions'], $info['objects'], $groupMap);
        }

        foreach ($userhandlers as $description => $installer) {
            if (! $dryRun) {
                $installer->install();
            }
            $this->setFeedback(tra('Added (or modified)') . ': ' . $description);
        }

        $this->applyPreferences($profile, $leftovers, $dryRun);
        tiki_setup_events();
    } // }}}

    /**
     * Revert a profile
     *
     * @param Tiki_Profile $profile Profile object
     * @param array $changes
     * @param string $empty_cache
     * @return void
     */
    public function revert($profile, $changes, $empty_cache = 'all')
    {
        $userlib = TikiLib::lib('user');
        $preferences = [];
        $packages = [];
        foreach ($changes as $change) {
            if (! empty($change['description'])) {
                if ($change['type'] == 'preference') {
                    $preferences[$change['description']] = $change['old'];
                } elseif ($change['type'] == 'package') {
                    $packages[$change['description']] = $change['old'];
                } elseif (in_array($change['type'], ['installer', 'user']) && ! empty($change['description'])) {
                    $description = explode(' ', $change['description']);
                    if (array_key_exists($description[0], $this->handlers)) {
                        $handlersName = $change['description'];
                        $pos = strpos($change['description'], $handlersName);
                        if ($pos !== false) {
                            $handlersName = substr_replace($change['description'], '', $pos, strlen($description[0]));
                            $handlersName = str_replace('"', "", trim($handlersName));
                        }
                        $handle = $this->handlers[$description[0]];
                        $installerType = $description[0];
                        if (! empty($change['old']) && method_exists($handle, 'revert')) {
                            $handle::revert($change['old']);
                            $this->setFeedback(tra('Installer reverted') . ': ' . $installerType . ' ' . $change['old']);
                        } elseif (method_exists($handle, 'remove')) {
                            $handle::remove($handlersName);
                            $this->setFeedback(tra('Installer removed') . ': ' . $installerType . ' ' . $handlersName);
                        }
                    }
                } elseif ($change['type'] == 'group') {
                    if (empty($change['old']) && ! empty($change['new'])) {
                        $userlib->remove_group($change['description']);
                        $this->setFeedback(tra('Group removed') . ': ' . $change['description']);
                    } else {
                        $info = $change['old'];
                        $userlib->change_group(
                            $info['groupName'],
                            $change['description'],
                            $info['groupDesc'],
                            $info['groupHome'],
                            $info['usersTrackerId'],
                            $info['groupTrackerId'],
                            $info['usersFieldId'],
                            $info['groupFieldId'],
                            implode(':', $info['registrationUsersFieldIds']),
                            $info['userChoice'],
                            $info['groupDefCat'],
                            $info['groupTheme'],
                            $info['isExternal'],
                            $info['expireAfter'],
                            $info['emailPattern'],
                            $info['anniversary'],
                            $info['prorateInterval'],
                            $info['groupColor']
                        );
                        $this->setFeedback(tra('Group modified') . ': ' . $info['groupName']);
                    }
                } elseif ($change['type'] == 'permission' && ! empty($change['description'][0]) && ! empty($change['description'][1])) {
                    $permission = $change['description'][0];
                    $groupName = $change['description'][1];

                    if ($groupName == 'Admins' && $permission == 'tiki_p_admin') {
                        continue;
                    }

                    if (empty($change['old'])) {
                        if (! empty($change['description'][2])) {
                            $data = $change['description'][2];
                            $userlib->remove_object_permission($groupName, $data['id'], $data['type'], $permission);
                        } else {
                            $userlib->remove_permission_from_group($permission, $groupName);
                        }
                        $this->setFeedback(tra('Permission removed') . ': ' . $permission);
                    } else {
                        if (! empty($change['description'][2])) {
                            $data = $change['description'][2];
                            $userlib->assign_object_permission($groupName, $data['id'], $data['type'], $permission);
                        } else {
                            $userlib->assign_permission_to_group($permission, $groupName);
                        }
                        $this->setFeedback(tra('Permission assign') . ': ' . $permission);
                    }
                }
            }
        }

        $leftovers = $this->applyPreferences($profile, $preferences, true);
        $this->applyPreferences($profile, $leftovers);
        $this->applyPackages($packages);
        tiki_setup_events();
    }

    private function applyPreferences($profile, $preferences, $leaveUnknown = false, $dryRun = false)
    {
        global $prefs;
        $tikilib = TikiLib::lib('tiki');

        $profile->replaceReferences($preferences, $this->userData, $leaveUnknown);
        $leftovers = [];

        foreach ($preferences as $pref => $value) {
            if ($leaveUnknown && $profile->containsReferences($value)) {
                $leftovers[$pref] = $value;

                continue;
            }

            if ($this->allowedGlobalPreferences === false || in_array($pref, $this->allowedGlobalPreferences)) {
                $prefslib = TikiLib::lib('prefs');
                $pinfo = $prefslib->getPreference($pref);
                if (! empty($pinfo['separator']) && ! is_array($value)) {
                    $value = explode($pinfo['separator'], $value);
                }

                if (! isset($prefs[$pref]) || $prefs[$pref] != $value) {
                    $this->setFeedback(tra('Preference set') . ': ' . $pref . '=' . (is_array($value) ? implode(', ', $value) : $value));

                    $oldValue = isset($prefs[$pref]) ? $prefs[$pref] : 'n';
                    $this->setTrackProfileChanges('preference', $value, $oldValue, $pref);
                }

                if (! $dryRun) {
                    $tikilib->set_preference($pref, $value);
                }
            }
        }

        return $leftovers;
    }

    /**
     * @param array $packages  Packages to apply
     * @param bool $dryRun
     * @throws Exception
     * @return void
     */
    private function applyPackages($packages, $dryRun = false)
    {
        foreach ($packages as $packageName => $value) {
            $basePath = ExtensionManager::locatePackage($packageName);

            if (empty($basePath)) {
                $this->setFeedback(tr('<error>Package %0:  No folder was found. Did you forgot to install?</error>', $packageName));

                continue;
            }

            $oldValue = ExtensionManager::isExtensionEnabled($packageName) ? "y" : "n";

            if ($oldValue != $value) {
                $this->setFeedback(tra('Package set') . ': ' . $packageName . '=' . $value);
                $this->setTrackProfileChanges('package', $value, $oldValue, $packageName);
            }

            if (! $dryRun && $value != $oldValue) {
                if ($value == "y") {
                    ExtensionManager::enableExtension($packageName, $basePath);
                } elseif ($value == "n") {
                    ExtensionManager::disableExtension($packageName);
                }
            }
        }
    }

    private function setupGroup($groupName, $info, $permissions, $objects, $groupMap, $dryRun = false) // {{{
    {
        $userlib = TikiLib::lib('user');

        foreach (['description', 'home', 'user_tracker', 'group_tracker', 'user_signup', 'default_category', 'theme', 'color', 'user_tracker_field', 'group_tracker_field', 'is_external', 'expire_after', 'email_pattern', 'anniversary', 'prorate_interval'] as $field) {
            if (! isset($info[$field])) {
                $info[$field] = '';
            }
        }
        if (! isset($info['registration_fields'])) {
            $info['registration_fields'] = [];
        }

        TikiLib::lib('cache')->invalidate('grouplist');

        if (! $userlib->group_exists($groupName)) {
            $this->setTrackProfileChanges('group', $info, false, $groupName);
            if (! $dryRun) {
                $userlib->add_group(
                    $groupName,
                    $info['description'],
                    $info['home'],
                    $info['user_tracker'],
                    $info['group_tracker'],
                    implode(':', $info['registration_fields']),
                    $info['user_signup'],
                    $info['default_category'],
                    $info['theme'],
                    $info['user_tracker_field'],
                    $info['group_tracker_field'],
                    $info['is_external'],
                    $info['expire_after'],
                    $info['email_pattern'],
                    $info['anniversary'],
                    $info['prorate_interval'],
                    $info['color']
                );
            }
        } else {
            $this->setTrackProfileChanges('group', $info, $userlib->get_group_info($groupName), $groupName);
            if (! $dryRun) {
                $userlib->change_group(
                    $groupName,
                    $groupName,
                    $info['description'],
                    $info['home'],
                    $info['user_tracker'],
                    $info['group_tracker'],
                    $info['user_tracker_field'],
                    $info['group_tracker_field'],
                    implode(':', $info['registration_fields']),
                    $info['user_signup'],
                    $info['default_category'],
                    $info['theme'],
                    $info['is_external'],
                    $info['expire_after'],
                    $info['email_pattern'],
                    $info['anniversary'],
                    $info['prorate_interval'],
                    $info['color']
                );
            }
        }

        if (count($info['include'])) {
            $userlib->manage_group($groupName, $info['include']);
        }

        foreach ($permissions as $perm => $v) {
            if ($v == 'y') {
                $userlib->assign_permission_to_group($perm, $groupName);
                $this->setTrackProfileChanges('permission', $v, false, [$perm, $groupName]);
            } else {
                $userlib->remove_permission_from_group($perm, $groupName);
                $this->setTrackProfileChanges('permission', false, $v, [$perm, $groupName]);
            }
            $this->setFeedback(sprintf(tra('Modified permission %s for %s'), $perm, $groupName));
        }

        foreach ($objects as $data) {
            foreach ($data['permissions'] as $perm => $v) {
                $data['id'] = trim($data['id']);
                $data['type'] = self::convertType($data['type']);
                $data['id'] = Tiki_Profile_Installer::convertObject($data['type'], $data['id'], ['groupMap' => $groupMap]);

                if ($v == 'y') {
                    $this->setTrackProfileChanges('permission', $data, false, [$perm, $groupName, $data]);
                    $userlib->assign_object_permission($groupName, $data['id'], $data['type'], $perm);
                } else {
                    $this->setTrackProfileChanges('permission', false, $data, [$perm, $groupName, $data]);
                    $userlib->remove_object_permission($groupName, $data['id'], $data['type'], $perm);
                }
                $this->setFeedback(
                    sprintf(tra('Modified permission %s on %s/%s for %s'), $perm, $data['type'], $data['id'], $groupName)
                );
            }
        }

        global $user;
        if ($info['autojoin'] == 'y' && $user) {
            $userlib->assign_user_to_group($user, $groupName);
            $this->setFeedback(tr('User %0 was added to %1', $user, $groupName));
        }
    } // }}}

    public function forget(Tiki_Profile $profile) // {{{
    {
        $key = $profile->getProfileKey();
        unset($this->installed[$key]);
        $profile->removeSymbols();
    } // }}}

    public function limitGlobalPreferences(array $allowedPreferences) // {{{
    {
        $this->allowedGlobalPreferences = $allowedPreferences;
    } // }}}

    public function limitObjectTypes(array $objectTypes) // {{{
    {
        $this->allowedObjectTypes = $objectTypes;
    } // }}}
}
