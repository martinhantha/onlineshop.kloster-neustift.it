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

namespace Mageplaza\AutoRelated\Block\Adminhtml\Rule\Edit\Tab;

use Magento\Backend\Block\Widget\Form\Generic;
use Mageplaza\AutoRelated\Model\Config\Source\Type;

/**
 * Class Place
 * @package Mageplaza\AutoRelated\Block\Adminhtml\Rule\Edit\Tab
 */
class Place extends Generic
{
    /**
     * @return bool
     */
    public function isCmsPageRule()
    {
        return $this->_coreRegistry->registry('autorelated_type') === Type::CMS_PAGE;
    }
}
