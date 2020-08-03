<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tiki\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

/**
 * Fix Tiki coding style of modified working copy files or all Tiki files
 *
 * Uses phpcbf to fix what is possible to do automatically
 *
 * @package Tiki\Command
 */
class DevFixStyleCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('dev:fixstyle')
            ->setDescription('Fix code style of changed files')
            ->setHelp('Fixes code style issues that are correctable with phpcbf via Tiki\'s coding standard. Will only change files modified in your working copy.')
            ->addOption(
                'directory',
                'd',
                InputOption::VALUE_REQUIRED,
                'A specific directory to process.'
            )
            ->addOption(
                'all',
                'a',
                InputOption::VALUE_NONE,
                'Fixes all Tiki files instead of changed files'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Lets first check that some requirements are met.
        if (! is_callable('proc_open')) {
            $output->writeln('<error>Must enable exec() for this command</error>');
            exit(1);
        }
        if ($input->getOption('all') && $input->getOption('directory')) {
            $output->writeln('<error>--directory and --all can not be used together');
            exit(1);
        }
        if ($input->getOption('all')) {
            // apply filter only to these file types, excluding any vendor files.
            $files = $this->globRecursive(
                '*.php',
                GLOB_BRACE,
                '',
                ['vendor_', 'vendor/', 'temp/', 'lib/cypht']
            );
        } elseif ($input->getOption('directory')) {
            $dir = $input->getOption('directory');
            if (! is_dir($dir)) {
                $output->writeln("<error>$dir does not exist");
                die(1);
            }
            // apply filter only to these file types, excluding any vendor files.
            $files = $this->globRecursive(
                '*.php',
                GLOB_BRACE,
                $dir,
                ['vendor_', 'vendor/', 'temp/', 'lib/cypht']
            );
        } else {
            $processes = new Process(['git', 'diff', '--name-only']);
            $processes->run();
            $output->writeln($processes->getOutput(), OutputInterface::VERBOSITY_DEBUG);

            preg_match_all('/^.*\.php$/m', $processes->getOutput(), $matches);
            $files = $matches[0];
        }

        $totalFiles = count($files);
        $totalFiles --;						// We reduce by one to sync the numbers with the array key values
        $progress = new ProgressBar($output, count($files));
        if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
            $progress->setOverwrite(false);
        }
        $progress::setFormatDefinition('custom', ' %current%/%max% [%bar%] -- %message%');
        $progress->setFormat('custom');

        $progress->setMessage('Processing files');
        $progress->start();

        $filesUpdated = 0;
        $processing = 0;
        $processes = [];
        // Now we start multithreading the phpcbf process, because its SO SLOW!
        for ($fileProcess = 0; $fileProcess < 8; $fileProcess++) {
            if ($files[$fileProcess]) {
                $processing++;
                $processes[$processing] = new Process(
                    ['php',
                     'vendor_bundled/vendor/squizlabs/php_codesniffer/bin/phpcbf',
                     $files[$fileProcess]]
                );
                $processes[$processing]->start();
            } else {
                break;
            }
        }


        /**
         * Find if one of the processes has finished
         *
         * @param array $processes array of Process currently running, or recently finished.
         *
         * @return int The processID of the process that has finished, 0 otherwise.
         */
        $processHasFinished = static function (array &$processes) : int {
            foreach ($processes as $processId => $process) {
                if (! $process->isRunning()) {
                    return $processId;
                }
            }

            return 0;
        };

        while ($processes) {
            if ($processId = $processHasFinished($processes)) {		// If a process has finished
                preg_match('/ \'([^\']+)\'$/', $processes[$processId]->getCommandLine(), $matches);		// find the filename
                $progress->setMessage('Finished Processing ' . $matches[1]);
                $progress->advance();
                $output->writeln($processes[$processId]->getOutput(), OutputInterface::VERBOSITY_DEBUG);
                if ($processes[$processId]->getExitCode() === 1) {
                    $filesUpdated++;
                }
                if ($processing < $totalFiles) {						// If there is still more files to process
                    $processes[$processId] = new Process(
                        ['php',
                         'vendor_bundled/vendor/squizlabs/php_codesniffer/bin/phpcbf',
                         $files[$processing]]
                    );
                    $processes[$processId]->start();
                    $processing++;
                } else {
                    unset($processes[$processId]);						// if there are no more files to process, then remove the finished process.
                }
            }
        }

        if (! $filesUpdated) {
            $progress->setMessage('<comment>All files look good, no changes made.</comment>');
        } else {
            $progress->setMessage("<comment>$filesUpdated files updated, you may now review and commit.</comment>");
        }
        $progress->finish();
    }

    /**
     * Recursively calls, glob()
     *
     * @param string $pattern
     * @param int    $flags
     * @param string $startdir
     * @param array  $excludes  If this string is found within a directory name, it wont be included
     *
     * @return array
     */
    private function globRecursive($pattern = '*', $flags = 0, $startdir = '', $excludes = [])
    {
        $files = glob($startdir . $pattern, $flags);
        foreach ($files as $key => $fileName) {
            foreach ($excludes as $exclude) {
                if (strpos($fileName, $exclude)) {
                    unset($files[$key]);

                    break;
                }
            }
        }

        foreach (glob($startdir . '*', GLOB_ONLYDIR | GLOB_NOSORT | GLOB_MARK) as $dir) {
            $include = true;
            /** If the directory has not been excluded from processing */
            foreach ($excludes as $exclude) {
                if (strpos($dir, $exclude) !== false) {
                    $include = false;

                    break;
                }
            }
            if ($include) {
                $files = array_merge($files, $this->globRecursive($pattern, $flags, $dir, $excludes));
            }
        }

        return $files;
    }
}
