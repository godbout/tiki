<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

if (!defined('DEBUG_MODE')) { die(); }

handler_source('tiki');
output_source('tiki');

/* groupmail page */
setup_base_page('groupmail', 'core');
add_handler('groupmail', 'load_data_sources',  true, 'tiki', 'message_list_type', 'after');
add_output('groupmail', 'groupmail_heading', true, 'tiki', 'content_section_start', 'after');
add_output('groupmail', 'groupmail_start', true, 'tiki', 'groupmail_heading', 'after');
add_output('groupmail', 'groupmail_end', true, 'tiki', 'groupmail_start', 'after');

/* folder list update ajax request */
add_handler('ajax_hm_folders', 'check_groupmail_setting', true, 'tiki', 'load_user_data', 'after');
add_output('ajax_hm_folders', 'groupmail_page_link', true, 'tiki', 'logout_menu_item', 'before');

/* ajax groupmail callback data */
setup_base_ajax_page('ajax_tiki_groupmail', 'imap');
add_handler('ajax_tiki_groupmail', 'prepare_groupmail_settings', true, 'imap', 'load_user_data', 'after');
add_handler('ajax_tiki_groupmail', 'load_imap_servers_from_config',  true, 'imap');
add_handler('ajax_tiki_groupmail', 'imap_oauth2_token_check', true, 'imap');
add_handler('ajax_tiki_groupmail', 'close_session_early',  true, 'core');
add_handler('ajax_tiki_groupmail', 'groupmail_fetch_messages',  true);
add_handler('ajax_tiki_groupmail', 'save_imap_cache',  true);
add_output('ajax_tiki_groupmail', 'filter_groupmail_data', true);

/* ajax take groupmail */
setup_base_ajax_page('ajax_take_groupmail', 'core');
add_handler('ajax_take_groupmail', 'prepare_groupmail_settings', true, 'tiki', 'load_user_data', 'after');
add_handler('ajax_take_groupmail', 'load_imap_servers_from_config',  true, 'imap');
add_handler('ajax_take_groupmail', 'take_groupmail', true, 'tiki');
add_output('ajax_take_groupmail', 'take_groupmail_response', true);

/* ajax put back groupmail */
setup_base_ajax_page('ajax_put_back_groupmail', 'core');
add_handler('ajax_put_back_groupmail', 'prepare_groupmail_settings', true, 'tiki', 'load_user_data', 'after');
add_handler('ajax_put_back_groupmail', 'load_imap_servers_from_config',  true, 'imap');
add_handler('ajax_put_back_groupmail', 'put_back_groupmail', true, 'tiki');
add_output('ajax_put_back_groupmail', 'put_back_groupmail_response', true);

return array(
	'allowed_pages' => array(
    'groupmail',
    'ajax_tiki_groupmail',
    'ajax_take_groupmail',
    'ajax_put_back_groupmail',
	),
	'allowed_get' => array(
	),
	'allowed_output' => array(
		'operator' => array(FILTER_SANITIZE_STRING, false),
		'item_removed' => array(FILTER_VALIDATE_BOOLEAN, false)
	),
	'allowed_post' => array(
		'server_id' => FILTER_VALIDATE_INT,
		'uid' => FILTER_SANITIZE_STRING,
		'folder' => FILTER_SANITIZE_STRING,
		'msgid' => FILTER_SANITIZE_STRING,
	)
);
