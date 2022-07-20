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
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Mageplaza\AutoRelated\Block\Product\Block;
use Mageplaza\AutoRelated\Helper\Rule;
use Mageplaza\AutoRelated\Model\Rule as RuleModel;

/**
 * Class AddBlock
 * @package Mageplaza\AutoRelated\Observer
 */
class AddBlock implements ObserverInterface
{
    /**
     * @var Rule
     */
    protected $helper;

    /**
     * AddBlock constructor.
     *
     * @param Rule $helper
     */
    public function __construct(Rule $helper)
    {
        $this->helper = $helper;
    }

    /**
     * @param Observer $observer
     *
     * @return $this|void
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function execute(Observer $observer)
    {
        if (!$this->helper->isEnableArpBlock()) {
            return $this;
        }

        $types = [
            'content' => 'content',
            'related' => 'catalog.product.related',
            'upsell'  => 'product.info.upsell',
            'cross'   => 'checkout.cart.crosssell',
            'sidebar' => 'sidebar.main',
            'footer'  => 'page.bottom'
        ];

        if ($this->helper->getConfigValue('layered_navigation/general/ajax_enable')
            || $this->helper->getConfigValue('layered_navigation/general/enable')
        ) {
            $types['sidebar'] = 'layer.catalog.leftnav';
        }

        $type = array_search($observer->getElementName(), $types, true);
        if ($type !== false) {
            $transport        = $observer->getTransport();
            $output           = $transport->getOutput();
            $outputReplace    = [];
            $outputBefore     = '';
            $outputAfter      = '';
            $outputRightPopup = '';
            $outputLeftPopup  = '';
            $ruleIds          = [];
            $activeRules      = $this->helper->getActiveRules();

            /** @var RuleModel $rule */
            foreach ($activeRules as $rule) {
                if (empty($rule->getApplyProductIds())) {
                    continue;
                }
                $ruleIds[] = $rule->getRuleId();

                if ($rule->getDisplayMode() === '1') {
                    switch ($rule->getLocation()) {
                        case 'before-' . $type:
                            $outputBefore .= $this->getRuleHtml($observer->getEvent(), $rule);
                            break;
                        case 'after-' . $type:
                            $outputAfter .= $this->getRuleHtml($observer->getEvent(), $rule);
                            break;
                        case 'replace-' . $type:
                            if (!isset($outputReplace[$type])) {
                                $outputReplace[$type] = '';
                            }

                            $outputReplace[$type] .= $this->getRuleHtml($observer->getEvent(), $rule);
                            break;
                        case 'right-popup-content':
                            $outputRightPopup = $this->getRuleHtml($observer->getEvent(), $rule);
                            break;
                        case 'left-popup-content':
                            $outputLeftPopup = $this->getRuleHtml($observer->getEvent(), $rule);
                            break;
                    }
                }
            }

            if (empty($ruleIds)) {
                return $this;
            }

            if ($type === 'related' || $type === 'upsell' || $type === 'cross') {
                if (!isset($outputReplace[$type])) {
                    $outputReplace[$type] = $output;
                }

                $output = "<div id=\"mageplaza-autorelated-block-before-{$type}\">{$outputBefore}</div>"
                    . "<div id=\"mageplaza-autorelated-block-replace-{$type}\">"
                    . ($outputReplace[$type] ?: $output) . "</div>"
                    . "<div id=\"mageplaza-autorelated-block-after-{$type}\">{$outputAfter}</div>";
            } else {
                $output = "<div id=\"mageplaza-autorelated-block-before-{$type}\">{$outputBefore}</div>"
                    . $output . "<div id=\"mageplaza-autorelated-block-after-{$type}\">{$outputAfter}</div>";
            }

            $output .= ($type == 'content') ?
                "<div id=\"mageplaza-autorelated-block-right-popup-{$type}\">{$outputRightPopup}</div>"
                . "<div id=\"mageplaza-autorelated-block-left-popup-{$type}\">{$outputLeftPopup}</div>" : '';
            $transport->setOutput($output);
        }

        return $this;
    }

    /**
     * @param Observer $event
     * @param RuleModel $rule
     *
     * @return mixed
     */
    protected function getRuleHtml($event, $rule)
    {
        $layout = $event->getLayout();
        $block  = $layout->createBlock(Block::class);

        return $block->setRule($rule)->toHtml();
    }
}
