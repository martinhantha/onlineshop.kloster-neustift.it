<?php
/**
 * Mageplaza
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Mageplaza.com license that is
 * available through the world-wide-web at this URL:
 * https://www.mageplaza.com/LICENSE.txt
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    Mageplaza
 * @package     Mageplaza_AutoRelated
 * @copyright   Copyright (c) Mageplaza (https://www.mageplaza.com/)
 * @license     https://www.mageplaza.com/LICENSE.txt
 */

namespace Mageplaza\AutoRelated\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order;

/**
 * Class QuoteSubmitSuccess
 * @package Mageplaza\AutoRelated\Observer
 */
class QuoteSubmitSuccess implements ObserverInterface
{
    /**
     * @param Observer $observer
     *
     * @return $this
     */
    public function execute(Observer $observer)
    {
        /** @var Order $order */
        $order = $observer->getEvent()->getData('order');
        foreach ($order->getAllVisibleItems() as $orderItem) {
            $options = $orderItem->getProductOptions();
            if (!empty($options['info_buyRequest']) && isset($options['info_buyRequest']['arp_rule_token'])) {
                $orderItem->setData('arp_rule_token', $options['info_buyRequest']['arp_rule_token'])->save();
            }
        }

        return $this;
    }
}
