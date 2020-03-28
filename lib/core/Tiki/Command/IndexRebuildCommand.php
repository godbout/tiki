<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tiki\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\FormatterHelper;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class IndexRebuildCommand extends Command
{
	protected function configure()
	{
		$this
			->setName('index:rebuild')
			->setDescription('Fully rebuild the unified search index')
			->addOption(
				'force',
				null,
				InputOption::VALUE_NONE,
				'Destroy failed indexes prior to rebuild'
			)
			->addOption(
				'log',
				null,
				InputOption::VALUE_NONE,
				'Generate a log of the indexed documents, useful to track down failures or memory issues'
			)
			->addOption(
				'cron',
				null,
				InputOption::VALUE_NONE,
				'Only output error messages'
			)
			->addOption(
				'progress',
				'p',
				InputOption::VALUE_NONE,
				'Show progress bar'
			);
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		global $num_queries;
		global $prefs;

		$force = $input->getOption('force');
		if ($input->getOption('log')) {
			$log = 2;
		} else {
			$log = 0;
		}
		$cron = $input->getOption('cron');

		$unifiedsearchlib = \TikiLib::lib('unifiedsearch');

		if ($force && $unifiedsearchlib->rebuildInProgress()) {
			if (! $cron) {
				$output->writeln('<info>Removing leftovers...</info>');
			}
			$unifiedsearchlib->stopRebuild();
		}

		if (! $cron) {
			$message = '[' . \TikiLib::lib('tiki')->get_short_datetime(0) . '] Started rebuilding index...';
			if ($log) {
				$message .= ' logging to file: ' . $unifiedsearchlib->getLogFilename($log);
			}
			$output->writeln($message);
		}

		if (! $cron) {
			list($engine, $version) = $unifiedsearchlib->getCurrentEngineDetails();
			if (! empty($engine)) {
				$engineMessage = 'Unified search engine: ' . $engine;
				if (! empty($version)) {
					$engineMessage .= ', version ' . $version;
				}
				$output->writeln($engineMessage);
			}
		}

		$timer = new \timer();
		$timer->start();

		$memory_peak_usage_before = memory_get_peak_usage();

		$num_queries_before = $num_queries;

		// Apply 'Search index rebuild memory limit' setting if available
		if (! empty($prefs['allocate_memory_unified_rebuild'])) {
			$memory_limiter = new \Tiki_MemoryLimit($prefs['allocate_memory_unified_rebuild']);
		}

		if ($input->getOption('progress') && ! $cron) {

			$lastStats = \TikiLib::lib('tiki')->get_preference('unified_last_rebuild_stats', [], true);
			if (isset($lastStats['default']['counts'])) {
				if (isset($lastStats['default']['times']['total'])) {
					$steps = $lastStats['default']['times']['total'] * 1000 + 5000;	// milliseconds plus 5 seconds for prefs (guess)
				} else {
					$steps = array_sum($lastStats['default']['counts']);
				}
			} else {
				$steps = 0;
			}

			$progress = new ProgressBar($output, $steps);	// TODO consider the prefs indexing time that happens after the main one
			if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
				$progress->setOverwrite(false);
			}
			$progress->setRedrawFrequency(10);
			if ($steps) {
				$progress->setFormatDefinition('custom', ' %elapsed%/%estimated% [%bar%] -- %message%');
			} else {
				$progress->setFormatDefinition('custom', ' %current%/%max% [%bar%] -- %message%');
			}
			$progress->setFormat('custom');
			$progress->setMessage(tr('Rebuilding...'));
			$progress->start();
		} else {
			$progress = null;
		}

		$result = $unifiedsearchlib->rebuild($log, false, $progress);

		if ($progress) {
			$progress->setMessage(tr('Rebuilding preferences index'));
			$progress->advance();
		}

		// Also rebuild admin index
		\TikiLib::lib('prefs')->rebuildIndex();

		if ($progress) {
			$progress->finish();
		}

		// Back up original memory limit if possible
		if (isset($memory_limiter)) {
			unset($memory_limiter);
		}

		\Feedback::printToConsole($output, $cron);

		$queries_after = $num_queries;

		if ($result) {
			if (! $cron) {
				if ($progress) {
					$output->writeln('');
				}
				$output->writeln("Indexed");
				foreach ($result['default']['counts'] as $key => $val) {
					$output->writeln("  $key: $val");
				}
				$output->writeln('Rebuilding index done');

				list($engine, $version, $index) = $unifiedsearchlib->getCurrentEngineDetails();
				$output->writeln('Index: ' . $index);

				if ($fallbackEngineDetails = \TikiLib::lib('unifiedsearch')->getFallbackEngineDetails()) {
					if (! empty($result['fallback'])) {
						$output->writeln('Fallback index was also rebuilt');
					} else {
						$output->writeln('<comment>Fallback index was not rebuilt</comment>');
					}

					list($engine, $engineName, $version, $index) = $fallbackEngineDetails;
					$fallbackEngineMessage = 'Fallback unified search engine: ' . $engineName;
					if (! empty($version)) {
						$fallbackEngineMessage .= ', version ' . $version;
					}
					$output->writeln($fallbackEngineMessage);
					$output->writeln('Fallback index: ' . $index);
				}
				$output->writeln('Execution time: ' . FormatterHelper::formatTime($timer->stop()));
				$output->writeln('Current Memory usage: ' . FormatterHelper::formatMemory(memory_get_usage()));
				$output->writeln('Memory peak usage before indexing: ' . FormatterHelper::formatMemory($memory_peak_usage_before));
				$output->writeln('Memory peak usage after indexing: ' . FormatterHelper::formatMemory(memory_get_peak_usage()));
				$output->writeln('Number of queries: ' . ($queries_after - $num_queries_before));
			}
			return(0);
		} else {
			$output->writeln("\n<error>Search index rebuild failed. Last messages shown above.</error>");
			\TikiLib::lib('logs')->add_action('rebuild indexes', 'Search index rebuild failed.', 'system');
			return(1);
		}
	}
}
