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

namespace Mageplaza\AutoRelated\Model\Config\Source;

/**
 * Class CmsPosition
 * @package Mageplaza\AutoRelated\Model\Config\Source
 */
class CmsPosition
{
    const TOP    = 'top';
    const BOTTOM = 'bottom';

    /**
     * @return array
     */
    public function getPosition()
    {
        return [
            self::TOP    => __('TOP'),
            self::BOTTOM => __('BOTTOM'),
        ];
    }
}
