<?php

/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Moritzmair\Mergeport\Cron;

use Magento\Framework\App\ObjectManager;

class SynchProducts
{

    protected $logger;

    /**
     * Constructor
     *
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(\Psr\Log\LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Execute the cron
     *
     * @return void
     */
    public function execute()
    {
        $this->logger->info("Cronjob SynchProducts is executed.");
        $helper = ObjectManager::getInstance()->create('Moritzmair\Mergeport\Helper\Data');

        $helper->synchProducts();
    }
}
