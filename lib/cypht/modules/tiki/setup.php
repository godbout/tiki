<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

if (!defined('DEBUG_MODE')) {
    die();
}

handler_source('tiki');
output_source('tiki');

/* groupmail page */
setup_base_page('groupmail', 'core');
add_handler('groupmail', 'load_data_sources', true, 'tiki', 'message_list_type', 'after');
add_output('groupmail', 'groupmail_heading', true, 'tiki', 'content_section_start', 'after');
add_output('groupmail', 'groupmail_start', true, 'tiki', 'groupmail_heading', 'after');
add_output('groupmail', 'groupmail_end', true, 'tiki', 'groupmail_start', 'after');

/* folder list update ajax request */
add_handler('ajax_hm_folders', 'check_groupmail_setting', true, 'tiki', 'load_user_data', 'after');
add_output('ajax_hm_folders', 'groupmail_page_link', true, 'tiki', 'logout_menu_item', 'before');

/* ajax groupmail callback data */
setup_base_ajax_page('ajax_tiki_groupmail', 'imap');
add_handler('ajax_tiki_groupmail', 'prepare_groupmail_settings', true, 'imap', 'load_user_data', 'after');
add_handler('ajax_tiki_groupmail', 'load_imap_servers_from_config', true, 'imap');
add_handler('ajax_tiki_groupmail', 'imap_oauth2_token_check', true, 'imap');
add_handler('ajax_tiki_groupmail', 'close_session_early', true, 'core');
add_handler('ajax_tiki_groupmail', 'groupmail_fetch_messages', true);
add_handler('ajax_tiki_groupmail', 'save_imap_cache', true);
add_output('ajax_tiki_groupmail', 'filter_groupmail_data', true);

/* ajax take groupmail */
setup_base_ajax_page('ajax_take_groupmail', 'core');
add_handler('ajax_take_groupmail', 'prepare_groupmail_settings', true, 'tiki', 'load_user_data', 'after');
add_handler('ajax_take_groupmail', 'load_imap_servers_from_config', true, 'imap');
add_handler('ajax_take_groupmail', 'take_groupmail', true, 'tiki');
add_output('ajax_take_groupmail', 'take_groupmail_response', true);

/* ajax put back groupmail */
setup_base_ajax_page('ajax_put_back_groupmail', 'core');
add_handler('ajax_put_back_groupmail', 'prepare_groupmail_settings', true, 'tiki', 'load_user_data', 'after');
add_handler('ajax_put_back_groupmail', 'load_imap_servers_from_config', true, 'imap');
add_handler('ajax_put_back_groupmail', 'put_back_groupmail', true, 'tiki');
add_output('ajax_put_back_groupmail', 'put_back_groupmail_response', true);

/* tiki contacts store */
add_handler('contacts', 'load_tiki_contacts', true, 'tiki', 'load_contacts', 'after');
add_handler('ajax_autocomplete_contact', 'load_tiki_contacts', true, 'tiki', 'load_contacts', 'after');
add_handler('ajax_imap_message_content', 'load_tiki_contacts', true, 'tiki', 'load_contacts', 'after');
add_handler('compose', 'load_tiki_contacts', true, 'tiki', 'load_contacts', 'after');
add_handler('ajax_delete_contact', 'load_tiki_contacts', true, 'tiki', 'load_contacts', 'after');
add_handler('ajax_add_contact', 'load_tiki_contacts', true, 'tiki', 'load_contacts', 'after');
add_output('ajax_hm_folders', 'tiki_contacts_page_link', true, 'tiki', 'logout_menu_item', 'before');

/* compose page handlers */
add_handler('compose', 'check_for_tiki_redirect', true, 'smtp', 'process_compose_form_submit', 'after');
add_handler('compose', 'add_file_attachment', true, 'smtp', 'load_smtp_servers_from_config', 'before');

/* message page calendar invitation hooks */
add_handler('ajax_imap_message_content', 'check_calendar_invitations_imap', true, 'imap', 'imap_message_content', 'after');
add_output('ajax_imap_message_content', 'add_rsvp_actions', true, 'imap', 'filter_message_headers', 'after');

/* message page rsvp actions to an event */
setup_base_ajax_page('ajax_rsvp_action', 'core');
add_handler('ajax_rsvp_action', 'check_calendar_invitations_imap', true, 'imap', 'imap_message_content', 'after');
add_handler('ajax_rsvp_action', 'load_imap_servers_from_config', true, 'imap');
add_handler('ajax_rsvp_action', 'load_smtp_servers_from_config', true, 'smtp', 'load_imap_servers_from_config', 'after');
add_handler('ajax_rsvp_action', 'add_smtp_servers_to_page_data', true, 'smtp', 'load_smtp_servers_from_config', 'after');
add_handler('ajax_rsvp_action', 'compose_profile_data', true, 'profiles', 'add_smtp_servers_to_page_data', 'after');
add_handler('ajax_rsvp_action', 'imap_message_content', true, 'imap', 'compose_profile_data', 'after');
add_handler('ajax_rsvp_action', 'event_rsvp_action', true, 'tiki', 'imap_message_content', 'after');

/* message page add to calendar function */
setup_base_ajax_page('ajax_add_to_calendar', 'core');
add_handler('ajax_add_to_calendar', 'check_calendar_invitations_imap', true, 'imap', 'imap_message_content', 'after');
add_handler('ajax_add_to_calendar', 'load_imap_servers_from_config', true, 'imap');
add_handler('ajax_add_to_calendar', 'imap_message_content', true, 'imap', 'load_imap_servers_from_config', 'after');
add_handler('ajax_add_to_calendar', 'add_to_calendar', true, 'tiki', 'imap_message_content', 'after');

/* message page update participant status function */
setup_base_ajax_page('ajax_update_participant_status', 'core');
add_handler('ajax_update_participant_status', 'check_calendar_invitations_imap', true, 'imap', 'imap_message_content', 'after');
add_handler('ajax_update_participant_status', 'load_imap_servers_from_config', true, 'imap');
add_handler('ajax_update_participant_status', 'imap_message_content', true, 'imap', 'load_imap_servers_from_config', 'after');
add_handler('ajax_update_participant_status', 'update_participant_status', true, 'tiki', 'imap_message_content', 'after');

/* message page remove event from calendar function */
setup_base_ajax_page('ajax_remove_from_calendar', 'core');
add_handler('ajax_remove_from_calendar', 'check_calendar_invitations_imap', true, 'imap', 'imap_message_content', 'after');
add_handler('ajax_remove_from_calendar', 'load_imap_servers_from_config', true, 'imap');
add_handler('ajax_remove_from_calendar', 'imap_message_content', true, 'imap', 'load_imap_servers_from_config', 'after');
add_handler('ajax_remove_from_calendar', 'remove_from_calendar', true, 'tiki', 'imap_message_content', 'after');

/* debug mode */
add_handler('settings', 'process_debug_mode', true, 'tiki', 'save_user_settings', 'before');
add_output('settings', 'debug_mode_setting', true, 'tiki', 'start_unread_settings', 'before');

return [
    'allowed_pages' => [
    'groupmail',
    'ajax_tiki_groupmail',
    'ajax_take_groupmail',
    'ajax_put_back_groupmail',
    'ajax_rsvp_action',
    'ajax_add_to_calendar',
    'ajax_update_participant_status',
    'ajax_remove_from_calendar'
    ],
    'allowed_get' => [
    ],
    'allowed_output' => [
        'operator' => [FILTER_SANITIZE_STRING, false],
        'item_removed' => [FILTER_VALIDATE_BOOLEAN, false]
    ],
    'allowed_post' => [
        'imap_server_id' => FILTER_VALIDATE_INT,
        'imap_msg_uid' => FILTER_SANITIZE_STRING,
        'folder' => FILTER_SANITIZE_STRING,
        'msgid' => FILTER_SANITIZE_STRING,
    'rsvp_action' => FILTER_SANITIZE_STRING,
    'calendar_id' => FILTER_VALIDATE_INT,
    'debug_mode' => FILTER_VALIDATE_INT,
    ]
];
