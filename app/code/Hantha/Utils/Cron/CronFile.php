<?php

namespace Hantha\Utils\Cron;

use Magento\Reports\Model\ResourceModel\Refresh\Collection;

class CronFile extends Collection
{
   protected $logger;
   protected $reportTypes;

   public function __construct(
      \Magento\Framework\Data\Collection\EntityFactory $entityFactory,
      \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
      \Magento\Reports\Model\FlagFactory $reportsFlagFactory,
      \Psr\Log\LoggerInterface $logger,
      array $reportTypes
   ) {
      $this->logger = $logger;
      $this->reportTypes = $reportTypes;
      parent::__construct($entityFactory, $localeDate, $reportsFlagFactory);
   }
   /**
    * @return $this
    */
   public function execute()
   {
      $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

      try {
         $codes = $this->loadData();

         foreach ($codes->_items as $codek => $codev) {
            $objectManager->create($this->reportTypes[$codek])->aggregate();
         }
      } catch (\Magento\Framework\Exception\LocalizedException $e) {
         $this->logger->critical($e->getMessage());
      } catch (\Exception $e) {
         $this->logger->critical($e->getMessage());
      }
      return $this;
   }
}
