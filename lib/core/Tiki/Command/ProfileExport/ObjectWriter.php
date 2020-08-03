<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tiki\Command\ProfileExport;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

abstract class ObjectWriter extends Command
{
    private $initialized = false;

    protected function configure()
    {
        $this->initialized = true;

        $this
            ->addOption(
                'reference',
                null,
                InputOption::VALUE_REQUIRED,
                'Re-apply profiles when already installed.'
            );
    }

    protected function getProfileWriter(InputInterface $input)
    {
        $ini = parse_ini_file('profiles/info.ini');
        $activeProfile = ! empty($ini['profile.name']) ? $ini['profile.name'] : '';

        $writer = new \Tiki_Profile_Writer("profiles", $activeProfile);

        if ($this->initialized && $ref = $input->getOption('reference')) {
            $writer->pushReference($ref);
        }

        return $writer;
    }
}
