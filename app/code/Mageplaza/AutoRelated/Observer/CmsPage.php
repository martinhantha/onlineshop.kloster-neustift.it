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

use Magento\Cms\Model\Page;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Mageplaza\AutoRelated\Model\ResourceModel\CmsPageRule\Collection;
use Mageplaza\AutoRelated\Model\ResourceModel\CmsPageRule\CollectionFactory as CmsPageRuleCollection;
use Mageplaza\AutoRelated\Model\Config\Source\CmsPosition;

/**
 * Class CmsPage
 * @package Mageplaza\AutoRelated\Observer
 */
class CmsPage implements ObserverInterface
{
    /**
     * @var CmsPageRuleCollection
     */
    private $cmsPageRuleCollection;

    /**
     * AddBlock constructor.
     *
     * @param CmsPageRuleCollection $cmsPageRuleCollection
     */
    public function __construct(CmsPageRuleCollection $cmsPageRuleCollection)
    {
        $this->cmsPageRuleCollection = $cmsPageRuleCollection;
    }

    /**
     * @param Observer $observer
     *
     * @return $this
     */
    public function execute(Observer $observer)
    {
        /** @var Page $cmsPage */
        $cmsPage = $observer->getEvent()->getData('page');

        /** @var Collection $cmsPageRuleCollection */
        $cmsPageRuleCollection = $this->cmsPageRuleCollection->create()->addFieldToFilter('page_id', $cmsPage->getId());
        $content               = $cmsPage->getContent();
        foreach ($cmsPageRuleCollection->getItems() as $item) {
            $cmsContent = '{{block class="Mageplaza\AutoRelated\Block\Widget" rule_id="' . $item->getRuleId() . '"}}';
            if ($item->getPosition() === CmsPosition::TOP) {
                $content = $cmsContent . $content;
            } else {
                $content .= $cmsContent;
            }
        }

        $cmsPage->setContent($content);

        return $this;
    }
}
