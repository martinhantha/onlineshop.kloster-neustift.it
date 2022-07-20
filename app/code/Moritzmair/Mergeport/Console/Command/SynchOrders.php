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

class SynchOrders extends Command
{

    /**
     * {@inheritdoc}
     */
    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ) {

        $output->writeln("Starting order synchronisation..");

        $helper = ObjectManager::getInstance()->create('Moritzmair\Mergeport\Helper\Data');

        $helper->synchOrders();

        $output->writeln("All orders has ben synched!");
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName("moritzmair_mergeport:synch_orders");
        $this->setDescription("Synch Orders");
        parent::configure();
    }
}
