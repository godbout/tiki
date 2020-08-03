<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

function prefs_user_list($partial = false)
{
    global $prefs;

    $fieldFormat = '{title} ({tracker_name})';

    return [
        'user_show_realnames' => [
            'name' => tra('Show user\'s real name'),
            'description' => tra('Show the user\'s real name instead of username (log-in name), when possible.'),
            'help' => 'User+Preferences',
            'type' => 'flag',
            'default' => 'n',
            'tags' => ['basic'],
        ],
        'user_unique_email' => [
            'name' => tra('User emails must be unique'),
            'help' => 'User+Preferences',
            'description' => tra('The email address of each user must be unique.'),
            'type' => 'flag',
            'default' => 'n',
        ],
        'user_tracker_infos' => [
            'name' => tra('Display user tracker information on the user information page'),
            'description' => tra('Display user tracker information on the user information page'),
            'help' => 'User+Tracker',
            'hint' => tra('Input the user tracker ID then field IDs to be shown, all separated by commas. Example: 1,1,2,3,4 (user tracker ID 1 followed by field IDs 1-4)'),
            'type' => 'text',
            'size' => '50',
            'dependencies' => [
                'userTracker',
            ],
            'default' => '',
            'profile_reference' => 'prefs_user_tracker_references',
        ],
        'user_assigned_modules' => [
            'name' => tra('Users can configure modules'),
            'help' => 'Users+Configure+Modules',
            'description' => tr('Modules aren\'t reflected in the screen until they are configured on MyAccount->Modules, including for the admin user'),
            'tags' => ['experimental'],	// This feature seems broken and will mess the display of the adventurous user. See https://dev.tiki.org/item5871
            'type' => 'flag',
            'default' => 'n',
        ],
        'user_flip_modules' => [
            'name' => tra('Users can open and close the modules'),
            'help' => 'Users+Shade+Modules',
            'type' => 'list',
            'description' => tra('Allows users to open and close modules using the icon in the module header.'),
            'options' => [
                'y' => tra('Always'),
                'module' => tra('Module decides'),
                'n' => tra('Never'),
            ],
            'default' => 'module',
        ],
        'user_store_file_gallery_picture' => [
            'name' => tra('Store full-size copy of profile picture in file gallery'),
            'help' => 'User+Preferences',
            'keywords' => 'avatar',
            'type' => 'flag',
            'default' => 'n',
            'dependencies' => ['user_picture_gallery_id', ],
        ],
        'user_small_avatar_size' => [
            'name' => tra('Size of the small profile picture stored for users'),
            'help' => 'User+Preferences',
            'type' => 'text',
            'units' => tra('pixels'),
            'filter' => 'digits',
            'default' => '45',
        ],
        'user_small_avatar_square_crop' => [
            'name' => tra('Crop the profile picture thumbnail to a square'),
            'help' => 'User+Preferences',
            'type' => 'flag',
            'default' => 'n',
        ],
        'user_picture_gallery_id' => [
            'name' => tra('File gallery in which to store full-size profile picture'),
            'description' => tra('Enter the gallery ID here. Create a dedicated gallery that is admin-only for security, or make sure gallery permissions are set so that only admins can edit.'),
            'help' => 'User+Preferences',
            'keywords' => 'avatar',
            'type' => 'text',
            'filter' => 'digits',
            'size' => '3',
            'default' => 0,
            'profile_reference' => 'file_gallery',
            'dependencies' => ['feature_file_galleries', ],
        ],
        'user_default_picture_id' => [
            'name' => tra('File ID of default profile picture'),
            'description' => tra('File ID of image to use in file gallery as the profile picture if user has no profile picture in file galleries'),
            'keywords' => 'avatar',
            'help' => 'User+Preferences',
            'type' => 'text',
            'filter' => 'digits',
            'size' => '5',
            'default' => 0,
            'dependencies' => ['user_store_file_gallery_picture'],
            'profile_reference' => 'file',
        ],
        'user_who_viewed_my_stuff' => [
            'name' => tra('Display who has viewed "my items" on the user information page'),
            'description' => tra('This requires activation of tracking of views for various items in the action log'),
            'type' => 'flag',
            'dependencies' => [
                'feature_actionlog',
            ],
            'default' => 'n',
        ],
        'user_who_viewed_my_stuff_days' => [
            'name' => tra('Length of "who viewed my items" history'),
            'description' => tra('Number of days before the current day to consider when displaying "who viewed my items"'),
            'type' => 'text',
            'filter' => 'digits',
            'units' => tra('days'),
            'size' => '4',
            'default' => 90,
        ],
        'user_who_viewed_my_stuff_show_others' => [
            'name' => tra('Show to others "who viewed my items" on the user information page'),
            'description' => tra('Show to others "who viewed my items" on the user information page. Admins can always see this information.'),
            'type' => 'flag',
            'dependencies' => [
                'user_who_viewed_my_stuff',
            ],
            'default' => 'n',
        ],
        'user_list_order' => [
            'name' => tra('Sort order'),
            'type' => 'list',
            'options' => $partial ? [] : UserListOrder(),
            'default' => 'score_desc',
        ],
        'user_register_prettytracker' => [
            'name' => tra('Use pretty trackers for registration form'),
            'help' => 'User+Tracker',
            'description' => tra('Use pretty trackers for registration form'),
            'type' => 'flag',
            'dependencies' => [
                'userTracker',
            ],
            'default' => 'n',
        ],
        'user_register_prettytracker_tpl' => [
            'name' => tra('Registration pretty tracker template'),
            'description' => tra('Use a wiki page name or Smarty template file with a .tpl extension.'),
            'type' => 'text',
            'size' => '20',
            'dependencies' => [
                'user_register_pretty_tracker',
            ],
            'default' => ''
        ],
        'user_register_prettytracker_hide_mandatory' => [
            'name' => tra('Hide Mandatory'),
            'description' => tra('Hide mandatory fields indication with an asterisk (shown by default).'),
            'type' => 'flag',
            'default' => 'n',
            'dependencies' => [
                'user_register_pretty_tracker',
            ],
        ],
        'user_register_prettytracker_output' => [
            'name' => tra('Output the registration results'),
            'help' => 'User+Tracker',
            'description' => tra('Use a wiki page as template to output the registration results to'),
            'type' => 'flag',
            'default' => 'n',
            'dependencies' => [
                'userTracker',
            ],
            'profile_reference' => 'wiki_page',
        ],
        'user_register_prettytracker_outputwiki' => [
            'name' => tra('Output registration pretty tracker template'),
            'description' => tra('Wiki page only'),
            'type' => 'text',
            'size' => '20',
            'default' => '',
            'dependencies' => [
                'user_register_prettytracker_output',
            ],
            'profile_reference' => 'wiki_page',
        ],
        'user_register_prettytracker_outputtowiki' => [
            'name' => tra('Page name field ID'),
            'description' => tra("User the tracker's field ID whose value is used as the output page name."),
            'type' => 'text',
            'size' => '20',
            'default' => '',
            'dependencies' => [
                'user_register_prettytracker_output',
            ],
            'profile_reference' => 'tracker_field',
            'format' => $fieldFormat,
        ],
        'user_trackersync_trackers' => [
            'name' => tra('User tracker IDs to sync prefs from'),
            'description' => tra('Select one or more trackers to sync user preferences from.'),
            'type' => 'text',
            'size' => '10',
            'dependencies' => [
                'userTracker',
            ],
            'default' => '',
            'separator' => ',',
            'profile_reference' => 'tracker',
        ],
        'user_trackersync_realname' => [
            'name' => tra('Tracker field IDs to sync the "real name" pref from'),
            'description' => tra('Enter the comma-separated IDs in order of priority to be chosen; each item can concatenate multiple fields using "+", for example "2+3,4".'),
            'type' => 'text',
            'size' => '10',
            'dependencies' => [
                'userTracker',
                'user_trackersync_trackers',
            ],
            'default' => '',
        ],
        'user_trackersync_groups' => [
            'name' => tra('Tracker field IDs to sync user groups'),
            'description' => tra('Enter the comma-separated IDs of all fields that contain group names to which to sync user groups.'),
            'type' => 'text',
            'size' => '10',
            'dependencies' => [
                'userTracker',
                'user_trackersync_trackers',
            ],
            'default' => '',
        ],
        'user_trackersync_geo' => [
            'name' => tra('Synchronize long/lat/zoom to location field'),
            'description' => tra('Synchronize user geolocation preferences with the main location field.'),
            'type' => 'flag',
            'dependencies' => [
                'userTracker',
                'user_trackersync_trackers',
            ],
            'default' => 'n',
        ],
        'user_trackersync_lang' => [
            'name' => tra('Change user system language when changing user tracker item language'),
            'type' => 'flag',
            'dependencies' => [
                'userTracker',
                'user_trackersync_trackers',
            ],
            'default' => 'n',
        ],
        'user_tracker_auto_assign_item_field' => [
            'name' => tra('Assign a user tracker item when registering if email equals this field'),
            'type' => 'text',
            'filter' => 'digits',
            'dependencies' => [
                'userTracker',
            ],
            'default' => '',
            'profile_reference' => 'tracker_field',
            'format' => $fieldFormat,
        ],
        'user_selector_threshold' => [
            'name' => tra('Maximum users in drop-down lists'),
            'description' => tra('Use jQuery autocomplete text input to prevent out-of-memory errors and performance issues when the user list is very large.'),
            'type' => 'text',
            'size' => '5',
            'units' => tra('users'),
            'dependencies' => ['feature_jquery_autocomplete'],
            'default' => 50,
        ],
        'user_selector_realnames_tracker' => [
            'name' => tra('Show user\'s real name'),
            'description' => tra('Use the user\'s real name instead of log-in name in the autocomplete selector in trackers'),
            'type' => 'flag',
            'dependencies' => ['feature_jquery_autocomplete', 'user_show_realnames', 'feature_trackers'],
            'default' => 'n',

        ],
        'user_selector_realnames_messu' => [
            'name' => tra('Show user\'s real name'),
            'description' => tra('Use the user\'s real name instead of log-in name in the autocomplete selector in the messaging feature.'),
            'type' => 'flag',
            'dependencies' => ['feature_jquery_autocomplete', 'user_show_realnames', 'feature_messages'],
            'default' => 'n',
        ],
        'user_favorites' => [
            'name' => tra('User favorites'),
            'description' => tra('Enable users to flag content as their favorite.'),
            'type' => 'flag',
            'default' => 'n',
        ],
        'user_likes' => [
            'name' => tra('User likes'),
            'description' => tra('Enable users to "like" content.'),
            'type' => 'flag',
            'default' => 'n',
        ],
        'user_must_choose_group' => [
            'name' => tra('Users must choose a group at registration'),
            'description' => tra('Users cannot register without choosing one of the groups indicated above.'),
            'type' => 'flag',
            'default' => 'n',
        ],
        'user_in_search_result' => [
            'name' => tr('Users available in search results'),
            'description' => tr('Users available within search results. Content related to the user will be included in the index.'),
            'type' => 'list',
            'dependencies' => ['feature_search'],
            'options' => [
                'none' => tr('None'),
                'all' => tr('All'),
                'public' => tr('Public'),
            ],
            'default' => 'none',
        ],
        'user_use_gravatar' => [
            'name' => tr('Use Gravatar for user profile pictures'),
            'description' => tr('Always request the Gravatar image for the user profile picture.'),
            'help' => 'http://gravatar.com',
            'type' => 'flag',
            'default' => 'n',
        ],
        'user_multilike_config' => [
            'name' => tr('Configuration for multilike'),
            'description' => tr('Separate configurations by a blank line; for example, relation_prefix=tiki.multilike ids=1,2,3 values=1,3,5 labels=Good,Great,Excellent)'),
            'help' => 'Multilike',
            'type' => 'textarea',
            'size' => 5,
            'default' => ''
        ],
        'user_force_avatar_upload' => [
            'name' => tr('Force users to upload an avatar.'),
            'description' => tr("Require the user to upload a profile picture if they haven't done so already by prompting them with a modal popup."),
            'type' => 'flag',
            'tags' => ['advanced'],
            'default' => 'n',
            'dependencies' => ['feature_userPreferences'],
        ],
    ];
}

/**
 * UserListOrder computes the value list for user_list_order preference
 *
 * @access public
 * @return array : list of values
 */
function UserListOrder()
{
    global $prefs;
    $options = [];

    if ($prefs['feature_community_list_score'] == 'y') {
        $options['score_asc'] = tra('Score ascending');
        $options['score_desc'] = tra('Score descending');
    }

    if ($prefs['feature_community_list_name'] == 'y') {
        $options['pref:realname_asc'] = tra('Name ascending');
        $options['pref:realname_desc'] = tra('Name descending');
    }

    $options['login_asc'] = tra('Login ascending');
    $options['login_desc'] = tra('Login descending');

    return $options;
}

function prefs_user_tracker_references(Tiki_Profile_Writer $writer, $values)
{
    $values = array_filter(explode(',', $values));
    $tracker = array_shift($values);

    $values = $writer->getReference('tracker_field', $values);
    array_unshift($values, $writer->getReference('tracker', $tracker));

    return implode(',', $values);
}
