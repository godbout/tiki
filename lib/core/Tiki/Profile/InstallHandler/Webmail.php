<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class Tiki_Profile_InstallHandler_Webmail extends Tiki_Profile_InstallHandler
{
	function getData()
	{
		if ($this->data) {
			return $this->data;
		}

		$defaults = [
			'to' => '',
			'cc' => '',
			'bcc' => '',
			'from' => '',             // specify a from email address to search for correct profile in cypht settings
			'subject' => '',
			'body' => '',
			'fattId' => null,         // add a File Gallery file as an attachment
			'pageaftersend' => null,  // defines wiki page to go to after webmail is sent
			'html' => 'y',
		];

		$data = array_merge($defaults, $this->obj->getData());

		return $this->data = $data;
	}

	function canInstall()
	{
		global $user;

		$data = $this->getData();

		if (! isset($data['to']) && ! isset($data['cc']) && ! isset($data['bcc']) && ! isset($data['subject']) && ! isset($data['body'])) {
			return false;	// nothing specified?
		}

		return true;
	}

	function _install()
	{
		global $tikilib, $tikipath, $user;
		$data = $this->getData();

		$this->replaceReferences($data);

		if (strpos($data['body'], 'wikidirect:') === 0) {
			$pageName = substr($this->content, strlen('wikidirect:'));
			$data['body'] = $this->obj->getProfile()->getPageContent($pageName);
		}

		if (! $data['html']) {
			$data['body'] = strip_tags($data['body']);
		}
		$data['to']      = trim(str_replace(["\n","\r"], "", html_entity_decode(strip_tags($data['to']))), ' ,');
		$data['cc']      = trim(str_replace(["\n","\r"], "", html_entity_decode(strip_tags($data['cc']))), ' ,');
		$data['bcc']     = trim(str_replace(["\n","\r"], "", html_entity_decode(strip_tags($data['bcc']))), ' ,');
		$data['from']    = trim(str_replace(["\n","\r"], "", html_entity_decode(strip_tags($data['from']))), ' ,');
		$data['subject'] = trim(str_replace(["\n","\r"], "", html_entity_decode(strip_tags($data['subject']))));

		$smtp_id = '';
		if ($data['from']) {
			$config = TikiLib::lib('tiki')->get_user_preference($user, 'cypht_user_config');
			$config = json_decode($config);
			if (! empty($config->smtp_servers)) {
				foreach ($config->smtp_servers as $key => $server) {
					if ($server->name == $data['from']) {
						$smtp_id = $key;
					}
				}
			}
		}

		$drafts = $_SESSION['cypht']['compose_drafts'] ?? [];
		$drafts[] = [
			'draft_to' => $data['to'],
			'draft_cc' => $data['cc'],
			'draft_bcc' => $data['bcc'],
			'draft_subject' => $data['subject'],
			'draft_body' => $data['body'],
			'draft_fattId' => $data['fattId'],
			'draft_smtp' => $smtp_id,
		];
		$draft_id = count($drafts)-1;
		$_SESSION['cypht']['compose_drafts'] = $drafts;
		$_SESSION['cypht']['pageaftersend'] = $data['pageaftersend'];

		$webmailUrl = $tikilib->tikiUrl(
			'tiki-webmail.php',
			[
				'page' => 'compose',
				'draft_id' => $draft_id,
				'useHTML' => $data['html'] ? 'y' : 'n'
			]
		);

		header('Location: ' . $webmailUrl);
		exit;	// means this profile never gets "remembered" - a good thing?
	}
}
