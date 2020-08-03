<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tiki\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Allows the migration of images from the Image Gallery (deprecated) to the File Gallery
 */
class GalleryMigrateCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('gallery:migrate')
            ->setDescription(tra('Migrate images from the Image Gallery to the File Gallery'));
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $fileGalLib = \TikiLib::lib('filegal');

        if ($fileGalLib->is_default_gallery_writable()) {
            $fileGalLib->migrateFilesFromImageGalleries();

            $output->writeln('<info>' . tr('All files migrated!') . '</info>');
        } else {
            $output->writeln('<error>' . tr('No files migrated, default file gallery path is not writable.') . '</error>');
        }
    }
}
