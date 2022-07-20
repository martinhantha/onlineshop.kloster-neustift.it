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

namespace Mageplaza\AutoRelated\Block\Adminhtml\Grid\Filter\Column;

/**
 * Class Checkbox
 * @package Mageplaza\AutoRelated\Block\Adminhtml\Grid\Filter\Column
 */
class Checkbox extends \Magento\Backend\Block\Widget\Grid\Column\Filter\Checkbox
{
    /**
     * @return array
     */
    public function getCondition()
    {
        if ($this->getValue() !== null) {
            return ['eq' => $this->getValue()];
        }

        return [];
    }
}
