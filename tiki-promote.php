<?php
// (c) Copyright 2002-2010 by authors of the Tiki Wiki/CMS/Groupware Project
// 
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id: tiki-tell_a_friend.php 27748 2010-06-23 03:01:50Z sampaioprimo $

// To include a link in your tpl do
//<a href="tiki-promote.php?url={$smarty.server.REQUEST_URI|escape:'url'}">{tr}Promote this page{/tr}</a>

require_once ('tiki-setup.php');

$access->check_feature('feature_promote_page');
$access->check_permission('tiki_p_promote_page');

// email related:
// include_once ('lib/registration/registrationlib.php'); // done in the email function
//include_once ('lib/webmail/tikimaillib.php'); // done in the email function
$smarty->assign('do_email', (isset($_REQUEST['do_email'])?$_REQUEST['do_email']:true));

// twitter/facebook related
if (isset($prefs['feature_socialnetworks']) and $prefs['feature_socialnetworks']=='y') {
	require_once ('lib/socialnetworkslib.php');
	$smarty->assign('twitterRegistered',$socialnetworkslib->twitterRegistered());
	$smarty->assign('facebookRegistered',$socialnetworkslib->facebookRegistered());
	$twitter_token=$tikilib->get_user_preference($user, 'twitter_token', '');
	$smarty->assign('twitter', ($twitter_token!=''));
	$facebook_token=$tikilib->get_user_preference($user, 'facebook_token', '');
	$smarty->assign('facebook', ($facebook_token!=''));
	$smarty->assign('do_tweet', (isset($_REQUEST['do_tweet'])?$_REQUEST['do_tweet']:true));
	$smarty->assign('do_fb', (isset($_REQUEST['do_fb'])?$_REQUEST['do_fb']:true));
} else {
	$smarty->assign('twitterRegistered',false);
	$smarty->assign('twitter',false);
	$smarty->assign('facebookRegistered',false);
	$smarty->assign('facebook',false);
}

// message related
if (isset($prefs['feature_messages']) and $prefs['feature_messages']=='y') {
	include_once ('lib/messu/messulib.php');
	include_once ('lib/logs/logslib.php');
	$smarty->assign('priority', (isset($_REQUEST['priority'])?$_REQUEST['priority']:3));
	$smarty->assign('do_message', (isset($_REQUEST['do_message'])?$_REQUEST['do_message']:true));
	$send_msg = ($tiki_p_messages=='y');
	if ($prefs['allowmsg_is_optional'] == 'y') {
		if ($tikilib->get_user_preference($user, 'allowMsgs', 'y') != 'y') {
			$send_msg=false;
		}
	}
	$smarty->assign('send_msg',$send_msg);	
} else {
	$smarty->assign('send_msg',false);
}
$smarty->assign('messageto', (isset($_REQUEST['messageto'])?$_REQUEST['messageto']:''));

if (isset($prefs['feature_forums']) and $prefs['feature_forums']=='y') {
	include_once ("lib/commentslib.php");
	$commentslib = new Comments($dbTiki); // not done in commentslib
	$sort_mode = $prefs['forums_ordering'];
	$channels = $commentslib->list_forums(0, -1, $sort_mode, '');
	Perms::bulk( array( 'type' => 'forum' ), 'object', $channels['data'], 'forumId' );
	$forums=array();
	$temp_max = count($channels["data"]);
	for ($i = 0; $i < $temp_max; $i++) {
		$forumperms = Perms::get( array( 'type' => 'forum', 'object' => $channels['data'][$i]['forumId'] ) );
		if (($forumperms->forum_post and $forumperms->forum_post_topic) or $forumperms->admin_forum) {
			$forums[]=$channels['data'][$i];
		}
	}
	$smarty->assign('forumId', (isset($_REQUEST['forumId'])?$_REQUEST['forumId']:0));
} else {
	$forums=array();
}
$smarty->assign('forums',$forums);

$smarty->assign('headtitle', tra('Promote this page'));

include_once ("textareasize.php");
$errors = array();
$ok=true;

if (empty($_REQUEST['url']) && !empty($_SERVER['HTTP_REFERER'])) {
	$u = parse_url($_SERVER['HTTP_REFERER']);
	if ($u['host'] != $_SERVER['SERVER_NAME']) {
		$smarty->assign('msg', tra('Incorrect param'));
		$smarty->display('error.tpl');
		die;
	}
	$_REQUEST['url'] = $_REQUEST['HTTP_REFERER'];
}
if (empty($_REQUEST['url'])) {
	$smarty->assign('msg', tra('missing parameters'));
	$smarty->display('error.tpl');
	die;
}
$_REQUEST['url'] = urldecode($_REQUEST['url']);
if (strstr($_REQUEST['url'], 'tiki-promote.php')) {
	$_REQUEST['url'] = preg_replace('/.*tiki-promote.php\?url=/', '', $_REQUEST['url']);
	header('location: tiki-promote.php?url=' . $_REQUEST['url']);
}
$url_for_friend = $tikilib->httpPrefix( true ) . $_REQUEST['url'];
if( $prefs['auth_token_promote'] == 'y' && $prefs['auth_token_access'] == 'y' && isset($_POST['share_access']) ) {
	require_once 'lib/auth/tokens.php';
	$tokenlib = AuthTokens::build( $prefs );
	$url_for_friend = $tokenlib->includeToken( $url_for_friend, $globalperms->getGroups() );
}

$smarty->assign('url', $_REQUEST['url']);
$smarty->assign('prefix', $tikilib->httpPrefix( true ));
$smarty->assign( 'url_for_friend', $url_for_friend );
$smarty->assign('shorturl', $url_for_friend);

if (isset($_REQUEST['send'])) {
	
	if (!empty($_REQUEST['comment'])) {
		$smarty->assign('comment', $_REQUEST['comment']);	
	}
	if (!empty($_REQUEST['subject'])) {
		$subject = $_REQUEST['subject'];
		$smarty->assign('subject', $subject);
	} else {
		$subject = $smarty->fetch('mail/promote_subject.tpl');
	}
	$smarty->assign('subject', $subject);

	check_ticket('promote');
	if (empty($user) && $prefs['feature_antibot'] == 'y' && !$captchalib->validate()) {
		$errors[] = $captchalib->getErrors();
	}
	if (isset ($_REQUEST['do_email']) and $_REQUEST['do_email']==1) {
		$emailSent = sendMail($_REQUEST['email'], $_REQUEST['addresses'], $subject);
		$smarty->assign_by_ref('email', $_REQUEST['email']);
		if (!empty($_REQUEST['addresses'])) {
			$smarty->assign('addresses', $_REQUEST['addresses']);
		}
		if (!empty($_REQUEST['name'])) {
			$smarty->assign('name', $_REQUEST['name']);
		}
		$smarty->assign('emailSent', $emailSent);
		$ok = $ok && $emailSent;
	} // do_ema$smarty->assignil
	if (isset ($_REQUEST['do_tweet']) and $_REQUEST['do_tweet']==1) {
		$tweet=substr($_REQUEST['tweet'],0,140);
		if (strlen($tweet)==0) {
			$ok=false;
			$errors[]=tra("No text given for tweet");
		} else {
			$tweetId=$socialnetworkslib->tweet($tweet, $user);
			if ($tweetId>0) {
				$smarty->assign('tweetId',$tweetId);
			} else {
				$ok=false;
				$tweetId=-$tweetId;
				$errors[]=tra("Error sending tweet:")." $tweetId";
			}
		}
	} // do_tweet
	if (isset ($_REQUEST['do_fb']) and $_REQUEST['do_fb']==1) {
		$msg=$_REQUEST['comment'];
		$linktitle=$_REQUEST['fblinktitle'];
		$facebookId=$socialnetworkslib->facebookWallPublish($user, $msg, $url_for_friend, $linktitle, $_REQUEST['subject']);
		$smarty->assign('facebookId', $facebookId);
		$ok=$ok && ($facebookId!=false);
	} // do_fb
	
	if (isset($_REQUEST['do_message']) and $_REQUEST['do_message']==1) {
		$messageSent=sendMessage($_REQUEST['messageto'], $subject);
		$smarty->assign('messageSent', $messageSent);
		$ok = $ok && $messageSent;
	} // do_message
	if (isset($_REQUEST['do_forum']) and $_REQUEST['do_forum']==1) {
		if (isset($_REQUEST['forumId'])) {
			$threadId=postForum($_REQUEST['forumId'], $subject);
			$smarty->assign('threadId',$threadId);
			$ok=$ok && ($threadId!=0);
		}
	} // do_forum
	
	$smarty->assign_by_ref('errors', $errors);
	$smarty->assign('errortype', 'no_redirect_login');
	if ($ok) {
		//$access->redirect( $_REQUEST['url'], tra('Your link was sent.') );
	}
	$smarty->assign('sent',true);
} else {
	$smarty->assign_by_ref('name', $user);
	$smarty->assign('email', $userlib->get_user_email($user));
}
ask_ticket('promote');
$smarty->assign('mid', 'tiki-promote.tpl');
$smarty->display('tiki.tpl');

/**
 * 
 * Validates the given recipients and returns false on error or an array containing the recipients on success
 * @param array|string	$recipients		list of recipients as an array or a comma/semicolon separated list	
 */
function checkAddresses($recipients) {
	global $errors;
	global $registrationlib; include_once ('lib/registration/registrationlib.php');
	$e=array();
	if (!is_array($recipients)) {
		$recipients=preg_split('/[\s*?](,|;)[\s*?]/',$recipients);
	}
	$ok=true;
	foreach($recipients as &$recipient) {
		$recipient=trim($recipient);
		if (function_exists('validate_email')) {
			$ok = validate_email($recipient, $prefs['validateEmail']);
		} else {
			$ret = $registrationlib->SnowCheckMail($recipient, '', 'mini');
			$ok = $ret[0];
		}
		if (!$ok) {
			$e[] = tra('One of the email addresses you typed is invalid') . ': ' . $recipient;
		}
	}
	if(count($e) != 0) {
		$errors=array_merge($errors, $e);
		return false;
	} else {
		return $recipients;
	}
}

/**
 * 
 * Sends a promotional email to the given recipients
 * @param string		$sender		Sender e-Mail address
 * @param string|array	$recipients	List of recipients either as array or comma/semi colon separated string
 * @param string		$subject	E-Mail subject
 * @param string		$url_for_friend		URL to promote
 * @return bool						true on success / false if the supplied parameters were incorrect/missing or an error occurred sending the mail
 */
function sendMail($sender, $recipients, $subject) {
	global $errors, $prefs, $smarty;
	global $registrationlib; include_once ('lib/registration/registrationlib.php');
	
	if (empty($sender)) {
		$errors[] = tra('Your email is mandatory');
		return false;
	}
	if (function_exists('validate_email')) {
		$ok = validate_email($sender, $prefs['validateEmail']);
	} else {
		$ret = $registrationlib->SnowCheckMail($sender, '', 'mini');
		$ok=$ret[0];
	}
	if ($ok) {
		$from = str_replace(array("\r", "\n"), '', $sender);
	} else {
		$errors[] = tra('Invalid email') . ': ' . $_REQUEST['email'];
		return false;
	}
	$recipients=checkAddresses($recipients);
	if ($recipients===false) {
		return false;
	}
	include_once ('lib/webmail/tikimaillib.php');
	$mail = new TikiMail();
	$smarty->assign_by_ref('mail_site', $_SERVER['SERVER_NAME']);
	$mail->setFrom($from);
	$mail->setHeader("Return-Path", "<$from>");
	$mail->setHeader("Reply-To", "<$from>");

	$txt = $smarty->fetch('mail/promote.tpl');
	$mail->setSubject($subject);
	$mail->setText($txt);
	$mail->buildMessage();
	$ok = true;
	foreach($recipients as $recipient) {
		$mailsent = $mail->send(array($recipient));
		if (!$mailsent) {
			$errors[] = tra("Error sending mail to"). " $email";
		}
		$ok = $ok && $mailsent;
	}
	return $ok;
}

/**
 * sends a message via the internal messaging to a list of recipients
 * @param string|array	$recipients	comma separated list (or array)  of recipients
 * @param string		$subject	subject of the message
 * @return bool						true on success/sent to all users successfully
 */
function sendMessage($recipients, $subject) {
	global $errors, $prefs, $smarty, $user, $userlib, $tikilib;
	global $messulib, $logslib;
	
	$ok=true;
	if (!is_array($recipients)) {
		$arr_to = preg_split('/\s*(?<!\\\);\s*/', $recipients);
	} else {
		$arr_to=$recipients;
	}
	$users = array();
	foreach($arr_to as $a_user) {
		if (!empty($a_user)) {
			$a_user = str_replace('\\;', ';', $a_user);
			if ($userlib->user_exists($a_user)) {
				// mail only to users with activated message feature
				if ($prefs['allowmsg_is_optional'] != 'y' || $tikilib->get_user_preference($a_user, 'allowMsgs', 'y') == 'y') {
					// only send mail if nox mailbox size is defined or not reached yet
					if (($messulib->count_messages($a_user) < $prefs['messu_mailbox_size']) || ($prefs['messu_mailbox_size'] == 0)) {
						$users[] = $a_user;
					} else {
						$errors[] = tra("User %s can not receive messages, mailbox is full");
						$ok=false;
					}
				} else {
					$errors[] = tra("User %s can not receive messages");
					$ok=false;
				}
			} else {
				$errors[] = tra("Invalid user: %s");
				$ok=false;
			}
		}
	}				
	$users = array_unique($users);
	$txt = $smarty->fetch('mail/promote.tpl');
	foreach($users as $a_user) {
		$messulib->post_message($a_user, $user, $a_user, '', $subject, $txt, $_REQUEST['priority'], $_REQUEST['replyto_hash']);
		if ($prefs['feature_score'] == 'y') {
			$tikilib->score_event($user, 'message_send');
			$tikilib->score_event($a_user, 'message_receive');
		}
	}
	// Insert a copy of the message in the sent box of the sender
	$messulib->save_sent_message($user, $user, $recipients, '', $subject, $txt, $_REQUEST['priority'], $_REQUEST['replyto_hash']);
	if ($prefs['feature_actionlog'] == 'y') {
		$logslib->add_action('Posted', '', 'message', 'add=' . strlen($_REQUEST['body']));
	}
	return $ok;
}

function postForum($forumId, $subject) {
	global $errors, $prefs, $smarty, $user, $userlib, $tikilib, $_REQUEST;
	global $commentslib;
	global $feedbacks;

	$forum_info = $commentslib->get_forum($forumId);
	$forumperms = Perms::get( array( 'type' => 'forum', 'object' => $forumId ) );
	if (!($forumperms->forum_post and $forumperms->forum_post_topic) or !$forumperms->admin_forum) {
		$errors[]=tra('Permission to post in forum denied');
		return 0;
	}
	if ($forum_info['is_locked'] == 'y') {
		// this is a "die" in $commentslib->post_in_forum so we must check here
		$errors[]=tra("This forum is locked");
		return 0;
	}
	
	$postErrors = array();
	$feedbacks = array();
	$txt = $smarty->fetch('mail/promote.tpl');
	$data=array('comments_title' => $subject,
				'comments_data'  => $txt,
				'password' => $_REQUEST['forum_password'],
				'comments_threadId' => 0,
				'forumId' => $forumId,
				);
	$threadId = $commentslib->post_in_forum($forum_info, $data, $feedbacks, $postErrors);
	if (count($postErrors)>0) {
		$errors = array_merge($errors, $postErrors);
	}
	$smarty->assign('feedbacks', $feedbacks);
	return $threadId;
}