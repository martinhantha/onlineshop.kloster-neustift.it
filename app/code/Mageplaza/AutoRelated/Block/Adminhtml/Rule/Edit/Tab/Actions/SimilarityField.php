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

namespace Mageplaza\AutoRelated\Block\Adminhtml\Rule\Edit\Tab\Actions;

use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Form\Generic;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Data\Form;
use Magento\Framework\Data\FormFactory;
use Magento\Framework\Registry;
use Mageplaza\AutoRelated\Model\Config\Source\Type as RuleType;
use Magento\Config\Model\Config\Source\Yesno;

/**
 * Class SimilarityField
 * @package Mageplaza\AutoRelated\Block\Adminhtml\Rule\Edit\Tab\Actions
 */
class SimilarityField extends Generic
{
    /**
     * @var Http
     */
    protected $request;

    /**
     * @var Yesno
     */
    private $yesNo;

    /**
     * SimilarityField constructor.
     *
     * @param Context $context
     * @param Registry $registry
     * @param FormFactory $formFactory
     * @param Http $request
     * @param Yesno $yesNo
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        FormFactory $formFactory,
        Http $request,
        Yesno $yesNo,
        array $data = []
    ) {
        $this->request = $request;
        $this->yesNo   = $yesNo;

        parent::__construct($context, $registry, $formFactory, $data);
    }

    /**
     * @inheritdoc
     */
    protected function _prepareForm()
    {
        if ($this->request->getParam('type') === RuleType::DEFAULT_TYPE_PAGE) {
            $model = $this->_coreRegistry->registry('autorelated_rule');

            /** @var Form $form */
            $form = $this->_formFactory->create();
            $form->setHtmlIdPrefix('block_config_rule_');

            $fieldset = $form->addFieldset('similarity_base_fieldset', ['legend' => __('Similarity')]);

            $fieldset->addField('apply_similarity', 'select', [
                'name'   => 'apply_similarity',
                'label'  => __('Apply "Similarity" Condition'),
                'title'  => __('Apply "Similarity" Condition'),
                'values' => $this->yesNo->toOptionArray()
            ]);

            $form->setValues($model->getData());

            $this->setForm($form);
        }

        return parent::_prepareForm();
    }
}
