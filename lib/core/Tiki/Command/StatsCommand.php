<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tiki\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use TikiDb;
use TikiLib;

class StatsCommand extends Command
{
	protected function configure()
	{
		$this
			->setName('tiki:stats')
			->setDescription('Display a table with the KPIs')
			->addOption(
				'json',
				null,
				InputOption::VALUE_NONE,
				'Output results in a json format'
			);
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$dateFormat = 'Y-m-d H:i:s';
		$dates = [];

		$userlib = TikiLib::lib('user');

		$kpis = [];

		//Get Last Admin logged in
		$usersAdmin = $userlib->get_group_users('Admins', 0, 1, '*', 'lastLogin_desc');
		$lastAdminLogin = '';
		if (isset($usersAdmin) && count($usersAdmin) > 0 && isset($usersAdmin[0]['lastLogin'])) {
			$lastAdminLogin = sprintf('%s (%s)', date($dateFormat, (int)$usersAdmin[0]['lastLogin']), $usersAdmin[0]['login']);
		}
		$kpis[] = [
			'kpi' => 'last_admin_user_login',
			'label' => tr('Last login from users in "Admins group"'),
			'value' => $lastAdminLogin
		];

		//Get Last User logged in
		$groups = $userlib->list_all_groups();
		$groups = array_diff($groups, ['Admins']);
		$users = $userlib->get_users(0, 1, 'lastLogin_desc', '', '', false, array_values($groups));

		$lastUserLogin = '';
		if (isset($users) && count($users['data']) > 0 && isset($users['data'][0]['lastLogin'])) {
			$lastUserLogin = sprintf('%s (%s)', date($dateFormat, (int)$users['data'][0]['lastLogin']), $users['data'][0]['login']);
		}
		$kpis[] = [
			'kpi' => 'last_user_login',
			'label' => tr('Last user login'),
			'value' => $lastUserLogin
		];

		//get last wiki page change
		$lastPage = TikiDb::get()->table('tiki_pages')->fetchRow([], [], ['lastModif' => 'desc']);
		if (isset($lastPage) && count($lastPage) > 0 && isset($lastPage['lastModif'])) {
			$dates[] = (int)$lastPage['lastModif'];
		}

		//get last blog page created
		$blog = TikiDb::get()->table('tiki_blog_posts')->fetchRow([], [], ['created' => 'desc']);
		if (! empty($blog) && ! empty($blog['created'])) {
			$dates[] = (int)$blog['created'];
		}

		//get last forum page created
		$lastForumPost = TikiDb::get()->table('tiki_forums')->fetchRow([], [], ['lastPost' => 'desc']);
		if (! empty($lastForumPost) && ! empty($lastForumPost['lastPost'])) {
			$dates[] = (int)$lastForumPost['lastPost'];
		}

		//get last article page created
		$lastarticlecreated = TikiDb::get()->table('tiki_articles')->fetchRow([], [], ['created' => 'desc']);
		if (! empty($lastarticlecreated) && ! empty($lastarticlecreated['created'])) {
			$dates[] = (int)$lastarticlecreated['created'];
		}

		//get last tracker modified
		$lasttrackercreated = TikiDb::get()->table('tiki_tracker_items')->fetchRow([], [], ['lastModif' => 'desc']);
		if (! empty($lasttrackercreated) && ! empty($lasttrackercreated['lastModif'])) {
			$dates[] = (int)$lasttrackercreated['lastModif'];
		}

		$daysAgo = '';
		if (! empty($dates)) {
			rsort($dates);
			$now = new \DateTime();
			$last = new \DateTime('@' . $dates[0]);
			$daysAgo = date_diff($now, $last)->format('%a');
		}
		$kpis[] = [
			'kpi' => 'days_since_last_object_change',
			'label' => tr('Days passed since last object create/change'),
			'value' => $daysAgo
		];

		if ($input->getOption('json')) {
			$output->write(json_encode($kpis));
		} else {
			$table = new Table($output);
			$table
				->setHeaders(['KPI', 'Label', 'Value'])
				->setRows($kpis);
			$table->render();
		}
	}
}
