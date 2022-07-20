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

namespace Mageplaza\AutoRelated\Plugin\Model\Quote;

use Magento\Framework\App\RequestInterface;
use Magento\Quote\Model\Quote\Item;
use Mageplaza\AutoRelated\Helper\Data;

/**
 * Class AutoRelatedItem
 * @package Mageplaza\AutoRelated\Plugin\Model\Condition
 */
class AutoRelatedItem
{
    /**
     * @var Data
     */
    protected $helperData;

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * AutoRelatedItem constructor.
     *
     * @param Data $helperData
     * @param RequestInterface $request
     */
    public function __construct(
        Data $helperData,
        RequestInterface $request
    ) {
        $this->helperData = $helperData;
        $this->request    = $request;
    }

    /**
     * @param Item $subject
     * @param bool $result
     *
     * @return mixed
     */
    public function afterRepresentProduct(Item $subject, $result)
    {
        if ($this->helperData->isEnabled($subject->getStoreId())) {
            $params         = $this->request->getParams();
            $options        = $subject->getOptions();
            $infoBuyRequest = [];
            foreach ($options as $option) {
                if ($option->getCode() === 'info_buyRequest') {
                    $infoBuyRequest = Data::jsonDecode($option->getValue());
                }
            }
            if (isset($params['arp_rule_token']) && isset($infoBuyRequest['arp_rule_token'])
                && $params['arp_rule_token'] !== $infoBuyRequest['arp_rule_token']) {
                return false;
            }
        }

        return $result;
    }
}
