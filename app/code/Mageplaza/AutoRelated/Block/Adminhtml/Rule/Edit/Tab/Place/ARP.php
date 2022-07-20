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

namespace Mageplaza\AutoRelated\Block\Adminhtml\Rule\Edit\Tab\Place;

use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Form\Generic;
use Magento\Backend\Block\Widget\Tab\TabInterface;
use Magento\Framework\Data\Form;
use Magento\Framework\Data\FormFactory;
use Magento\Framework\Phrase;
use Magento\Framework\Registry;
use Mageplaza\AutoRelated\Model\Config\Source\LocationOptionsProvider;

/**
 * Class ARP
 * @package Mageplaza\AutoRelated\Block\Adminhtml\Rule\Edit\Tab\Place
 */
class ARP extends Generic implements TabInterface
{
    /**
     * @var LocationOptionsProvider
     */
    protected $locationOptions;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param FormFactory $formFactory
     * @param LocationOptionsProvider $locationOptions
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        FormFactory $formFactory,
        LocationOptionsProvider $locationOptions,
        array $data = []
    ) {
        $this->locationOptions          = $locationOptions;

        parent::__construct($context, $registry, $formFactory, $data);
    }

    /**
     * @inheritdoc
     */
    protected function _prepareForm()
    {
        $model = $this->_coreRegistry->registry('autorelated_rule');

        /** @var Form $form */
        $form = $this->_formFactory->create();
        $form->setHtmlIdPrefix('block_config_rule_');

        $fieldset = $form->addFieldset('place_base_fieldset', ['legend' => __('Where To Display Related Products')]);

        $fieldset->addField('location', 'select', [
            'name'   => 'location',
            'label'  => __('Select'),
            'title'  => __('Select'),
            'values' => $this->locationOptions->toOptionArray(),
            'note'   => __('Select the position to display block.')
        ]);

        $form->setValues($model->getData());
        $this->setForm($form);

        return parent::_prepareForm();
    }

    /**
     * @return Phrase|string
     */
    public function getTabLabel()
    {
        return __('Where to Display Related Products');
    }

    /**
     * @return Phrase|string
     */
    public function getTabTitle()
    {
        return __('Where to Display Related Products');
    }

    /**
     * @return bool
     */
    public function canShowTab()
    {
        return true;
    }

    /**
     * @return false
     */
    public function isHidden()
    {
        return false;
    }
}
