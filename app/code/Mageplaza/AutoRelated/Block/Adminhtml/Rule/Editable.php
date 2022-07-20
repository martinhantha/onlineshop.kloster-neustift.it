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

namespace Mageplaza\AutoRelated\Block\Adminhtml\Rule;

use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Data\Form\Element\Renderer\RendererInterface;

/**
 * Class Editable
 * @package Mageplaza\AutoRelated\Block\Adminhtml\Rule
 */
class Editable extends \Magento\Rule\Block\Editable
{
    /**
     * Render element
     *
     * @param AbstractElement $element
     *
     * @return string
     *
     * @see RendererInterface::render()
     */
    public function render(AbstractElement $element)
    {
        return '';
    }
}
