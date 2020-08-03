<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tiki\Command\ProfileExport;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Init extends Command
{
    protected function configure()
    {
        $this
            ->setName('profile:export:init')
            ->setDescription('Initialize profile export for current site.')
            ->addArgument(
                'profile',
                InputArgument::REQUIRED,
                'Profile name'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $profileName = $input->getArgument('profile');

        if (! file_exists('profiles')) {
            mkdir('profiles');
        }

        $htaccess = <<<HTACCESS
<FilesMatch ".*">
    <IfModule mod_authz_core.c>
       Require all denied
    </IfModule>
    <IfModule !mod_authz_core.c>
        order deny,allow
        deny from all
    </IfModule>
</FilesMatch>
HTACCESS;
        file_put_contents("profiles/.htaccess", $htaccess);

        $definition = <<<INI
profile.name = $profileName
INI;
        file_put_contents("profiles/info.ini", $definition);

        if (! file_exists("profiles/$profileName")) {
            mkdir("profiles/$profileName");
        }
    }
}
