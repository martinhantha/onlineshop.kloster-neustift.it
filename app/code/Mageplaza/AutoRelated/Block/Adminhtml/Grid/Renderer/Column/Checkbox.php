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

use Magento\Backend\Block\Context;
use Magento\Framework\Registry;
use Magento\Backend\Block\Widget\Grid\Column\Renderer\Options\Converter;
use Mageplaza\AutoRelated\Model\ResourceModel\CmsPageRule\CollectionFactory as CmsPageRuleCollection;

/**
 * Class Checkbox
 * @package Mageplaza\AutoRelated\Block\Adminhtml\Grid\Renderer\Column
 */
class Checkbox extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\Checkbox
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
     * Checkbox constructor.
     *
     * @param Context $context
     * @param Converter $converter
     * @param Registry $registry
     * @param CmsPageRuleCollection $cmsPageRuleCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Converter $converter,
        Registry $registry,
        CmsPageRuleCollection $cmsPageRuleCollection,
        array $data = []
    ) {
        $this->registry              = $registry;
        $this->cmsPageRuleCollection = $cmsPageRuleCollection;

        parent::__construct($context, $converter, $data);
    }

    /**
     * @param string $value
     * @param bool $checked
     *
     * @return string
     */
    protected function _getCheckboxHtml($value, $checked)
    {
        $arpRule          = $this->registry->registry('autorelated_rule');
        $checkedAttribute = '';
        if ($ruleId = $arpRule->getId()) {
            $cmsPageRuleCollection = $this->cmsPageRuleCollection->create()
                ->addFieldToFilter('rule_id', ['eq' => $ruleId])
                ->addFieldToFilter('page_id', ['eq' => $value]);
            if ($cmsPageRuleCollection->getFirstItem()->getId()) {
                $checkedAttribute = ' checked="checked"';
            }
        }

        $html = '<label class="data-grid-checkbox-cell-inner" ';
        $html .= ' for="id_' . $this->escapeHtml($value) . '">';
        $html .= '<input style="display:none"; type="checkbox" ';
        $html .= 'name="' . $this->getColumn()->getFieldName() . '_' . $value . '" ';
        $html .= 'id="id_' . $this->escapeHtml($value) . '" ';
        $html .= 'class="' .
            ($this->getColumn()->getInlineCss() ? $this->getColumn()->getInlineCss() : 'checkbox') .
            ' admin__control-checkbox' . '"';
        $html .= $checkedAttribute . $this->getDisabled() . '/>';
        $html .= '<label for="id_' . $this->escapeHtml($value) . '"></label>';
        $html .= '</label>';

        return $html;
    }
}
