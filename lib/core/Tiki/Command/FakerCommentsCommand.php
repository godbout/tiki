<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tiki\Command;

use DateInterval;
use DateTimeInterface;
use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Faker\Factory as FakerFactory;
use TikiLib;
use Tiki\Faker as TikiFaker;

/**
 * Enabled the usage of Faker as a way to load random data to trackers
 */
class FakerCommentsCommand extends Command
{
	/**
	 * Configures the current command.
	 */
	protected function configure()
	{
		$this
			->setName('faker:comments')
			->setDescription('Generate comments fake data')
			->addArgument(
				'object',
				InputArgument::REQUIRED,
				'Object Id'
			)
			->addArgument(
				'type',
				InputArgument::OPTIONAL,
				'Object Type',
				'wiki page'
			)
			->addOption(
				'items',
				'i',
				InputOption::VALUE_OPTIONAL,
				'Number of comments (items) to generate',
				100
			)
			->addOption(
				'replies',
				'r',
				InputOption::VALUE_OPTIONAL,
				'Percentage of comments as replies',
				40
			)
			->addOption(
				'anonymous',
				'a',
				InputOption::VALUE_OPTIONAL,
				'Percentage of anonymous posts if permitted',
				20
			)
			->addOption(
				'minstart',
				's',
				InputOption::VALUE_OPTIONAL,
				'Earliest start date for first comment',
				'-1 year'
			)
			->addOption(
				'maxstart',
				'e',
				InputOption::VALUE_OPTIONAL,
				'Latest start date for first comment',
				'-11 months'
			)
			->addOption(
				'mingap',
				'',
				InputOption::VALUE_OPTIONAL,
				'Shortest gap between comments',
				'10 minutes'
			)
			->addOption(
				'maxgap',
				'g',
				InputOption::VALUE_OPTIONAL,
				'Longest gap between comments',
				'5 days'
			);
	}

	/**
	 * Executes the current command.
	 *
	 * @param InputInterface  $input
	 * @param OutputInterface $output
	 *
	 * @return null|int
	 * @throws Exception
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		global $prefs;

		$commentslib = TikiLib::lib('comments');
		$tikilib = TikiLib::lib('tiki');

		if (! class_exists('\Faker\Factory')) {
			$output->writeln('<error>' . tra('Please install Faker package') . '</error>');
			return null;
		}

		$objectId = $input->getArgument('object');
		$objectType = $input->getArgument('type');

		// check for object's existence
		if (! TikiLib::lib('object')->get_object_id($objectType, $objectId)) {
			$output->writeln('<error>' . tr('Object "%0" of type "%1" not found', $objectId, $objectType) . '</error>');
			return null;
		}

		$numberItems = $input->getOption('items');
		if (! is_numeric($numberItems)) {
			$output->writeln('<error>' . tra('The value of items is not a number') . '</error>');
			return null;
		}

		$numberItems = (int)$numberItems;

		$replyPercentage = $input->getOption('replies');
		$anonymousPercentage = $input->getOption('anonymous');

		// dates
		$minStart = $input->getOption('minstart');
		$maxStart = $input->getOption('maxstart');
		$minGap = $input->getOption('mingap');
		$maxGap = $input->getOption('maxgap');

		$faker = FakerFactory::create();
		$tikiFaker = new TikiFaker($faker);
		$faker->addProvider($tikiFaker);

		$startDate = $faker->dateTimeBetween($minStart, $maxStart);
		$lastDateTime = $startDate;
		$lastDateTime->add(DateInterval::createFromDateString($minGap));

		$fakerMap = [
			'title' => '',
			'data'  => 'text',
		];

		if ($prefs['comments_notitle'] !== 'y') {
			$fakerMap['title'] = ['text', [30]];
		}

		$threadId = 0;
		// need user names, jnot real
		$showRealNames = $prefs['user_show_realnames'];
		$prefs['user_show_realnames'] = 'n';

		for ($i = 0; $i < $numberItems; $i++) {

			if ($prefs['feature_comments_post_as_anonymous'] === 'y' && random_int(0, 100) < $anonymousPercentage) {
				$fakerMap['userName'] = '';
				$fakerMap['anonymous_name'] = 'name';
				$fakerMap['anonymous_email'] = 'email';
				$fakerMap['website'] = 'url';
			} else {
				$fakerMap['userName'] = ['tikiUserSelector', false,];
				$fakerMap['anonymous_name'] = '';
				$fakerMap['anonymous_email'] = '';
				$fakerMap['website'] = '';
			}

			$commentData = [];

			foreach ($fakerMap as $argument => $thisFaker) {

				if (is_array($thisFaker)) {
					$fakerAction = $thisFaker[0];
					$fakerArguments = $thisFaker[1];
					if (! is_array($fakerArguments)) {
						$fakerArguments = [$fakerArguments];
					}
					$value = call_user_func_array([$faker, $fakerAction], $fakerArguments);
				} elseif (! empty($thisFaker)) {
					$fakerAction = $thisFaker;
					$value = $faker->$fakerAction;
				} else {
					$value = '';
				}

				if (isset($value)) {
					$commentData[$argument] = $value;
				}
			}

			// special handling for commentDateTime

			$commentDateTime = $faker->dateTimeInInterval($lastDateTime->format(DateTimeInterface::ATOM), $maxGap);

			if (! empty($commentData)) {
				$message_id = '';        // returned by reference
				if ($threadId && random_int(0, 100) < $replyPercentage) {
					$parentId = $threadId;        // add thread parent here sometimes
				} else {
					$parentId = 0;
				}
				$commentDateUnix = $commentDateTime->format('U');
				if ($prefs['comments_notitle'] === 'y') {
					$commentData['title'] = 'Untitled ' . $tikilib->get_long_datetime($commentDateUnix);
				}

				try {
					$threadId = $commentslib->post_new_comment(
						"$objectType:$objectId",
						$parentId,
						$commentData['userName'],
						$commentData['title'],
						$commentData['data'],
						$message_id,
						'',    // reply to for forums only?
						'n',
						'',
						'',
						[],
						$commentData['anonymous_name'],
						$commentDateUnix,
						$commentData['anonymous_email'],
						$commentData['website'],
						[],                    // parent info for forums
						0            // version of object for save and comment
					);
				} catch (Exception $e) {
					$output->writeln('<error>' . $e->getMessage() . '</error>');
				}

				$lastDateTime = $commentDateTime;
			}
		}

		$prefs['user_show_realnames'] = $showRealNames;

		return 0;
	}


}
