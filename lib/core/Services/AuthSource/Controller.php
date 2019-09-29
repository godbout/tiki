<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class Services_AuthSource_Controller
{
	function setUp()
	{
		if (! Perms::get()->admin) {
			throw new Services_Exception(tr('Permission Denied'), 403);
		}
	}

	private function sources()
	{
		//table does not have an autoincrement field
		return TikiDb::get()->table('tiki_source_auth', false);
	}

	function action_list($input)
	{
		return $this->sources()->fetchColumn('identifier', []);
	}

	function action_save($input)
	{
		$url = $input->url->url();
		$info = parse_url($url);

		$identifier = $input->identifier->text();
		$method = $input->method->alpha();
		$arguments = $input->arguments->none();

		if (! $info || ! $identifier || ! $method || ! $arguments) {
			throw new Services_Exception(tr('Invalid data'), 406);
		}
		$util = new Services_Utilities();
		if ($util->isActionPost()) {
			if (empty($info['path'])) {
				$info['path'] = '/';
			}
			$result = $this->sources()->insertOrUpdate(
				[
					'scheme'    => $info['scheme'],
					'domain'    => $info['host'],
					'path'      => $info['path'],
					'method'    => $method,
					'arguments' => json_encode($arguments),
				],
				['identifier' => $identifier,]
			);
			if ($result && $result->numrows()) {
				Feedback::success(tr('Authentication added or modified'));
			} else {
				Feedback::error(tr('Authentication not added or modified'));
			}
			return ['identifier' => $identifier];
		}
	}

	function action_fetch($input)
	{
		$data = $this->sources()->fetchFullRow(
			['identifier' => $input->identifier->text(),]
		);

		$data['arguments'] = json_decode($data['arguments'], true);
		$data['url'] = "{$data['scheme']}://{$data['domain']}{$data['path']}";

		return $data;
	}

	function action_delete($input)
	{
		$util = new Services_Utilities();
		if ($util->isActionPost()) {
			$result = $this->sources()->delete(
				['identifier' => $input->identifier->text(),]
			);
			if ($result && $result->numrows()) {
				Feedback::success(tr('Authentication deleted'));
			} else {
				Feedback::error(tr('Authentication not deleted'));
			}
		}
	}
}
