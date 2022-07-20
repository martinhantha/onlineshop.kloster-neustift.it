<?php

/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Moritzmair\Mergeport\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\Framework\App\ObjectManager;

class SynchProducts extends Command
{

    // API URL
    const API_URL = 'https://ordering.mergeport.com/v4/hooks/main/';

    /**
     * {@inheritdoc}
     */
    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ) {
        $output->writeln("Starting product synchronisation..");

        $helper = ObjectManager::getInstance()->create('Moritzmair\Mergeport\Helper\Data');

        $helper->synchProducts();

        $output->writeln("All products has ben synched!");
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName("moritzmair_mergeport:synch_products");
        $this->setDescription("Synch Products");
        parent::configure();
    }
}
