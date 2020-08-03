<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tiki\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command that allows to parses all the pages to refresh the list of plugins
 */
class PluginRefreshRunCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('plugin:refresh')
            ->setDescription(tr('Parses all the pages to refresh the list of plugin invocations/calls'));
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $logger = new ConsoleLogger($output);

        $parserLib = \TikiLib::lib('parser');
        $parserLib->pluginRefresh($logger);

        $output->writeln(tr('Plugin list refreshed with success'));
    }
}
