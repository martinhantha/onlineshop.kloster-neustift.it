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

namespace Mageplaza\AutoRelated\Block\Adminhtml\Grid\Renderer\Column;

use Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer;
use Magento\Framework\DataObject;
use Magento\Framework\Registry;
use Magento\Backend\Block\Context;
use Mageplaza\AutoRelated\Model\ResourceModel\CmsPageRule\CollectionFactory as CmsPageRuleCollection;

/**
 * Class Select
 * @package Mageplaza\AutoRelated\Block\Adminhtml\Grid\Renderer\Column
 */
class Select extends AbstractRenderer
{
    /**
     * @var Registry
     */
    private $registry;
    /**
     * @var CmsPageRuleCollection
     */
    private $cmsPageRuleCollection;

    /**
     * Select constructor.
     *
     * @param Context $context
     * @param Registry $registry
     * @param CmsPageRuleCollection $cmsPageRuleCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        CmsPageRuleCollection $cmsPageRuleCollection,
        array $data = []
    ) {
        $this->registry              = $registry;
        $this->cmsPageRuleCollection = $cmsPageRuleCollection;

        parent::__construct($context, $data);
    }

    /**
     * Renders grid column
     *
     * @param DataObject $row
     *
     * @return  string
     */
    public function render(DataObject $row)
    {
        $arpRule = $this->registry->registry('autorelated_rule');
        $pageId  = $row->getData('page_id');
        $value   = null;
        if ($ruleId = $arpRule->getId()) {
            $cmsPageRuleCollection = $this->cmsPageRuleCollection->create()
                ->addFieldToFilter('rule_id', ['eq' => $ruleId])
                ->addFieldToFilter('page_id', ['eq' => $row->getData('page_id')]);

            $cmsPageRuleItem = $cmsPageRuleCollection->getFirstItem();
            if ($cmsPageRuleItem->getId()) {
                $value = $cmsPageRuleItem->getPosition();
            }
        }

        $name = $this->getColumn()->getName() ?: $this->getColumn()->getId();
        $html = '<select name="' . $this->escapeHtml($name) . '_' .
            $pageId . '" ' . $this->getColumn()->getValidateClass() . '>';
        foreach ($this->getColumn()->getOptions() as $val => $label) {
            $selected = $val === $value && $value !== null ? ' selected="selected"' : '';
            $html     .= '<option value="' . $this->escapeHtml($val) . '"' . $selected . '>';
            $html     .= $this->escapeHtml($label) . '</option>';
        }
        $html .= '</select>';

        return $html;
    }
}
