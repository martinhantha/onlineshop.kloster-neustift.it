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

namespace Mageplaza\AutoRelated\Controller\Adminhtml\Condition;

use Mageplaza\AutoRelated\Controller\Adminhtml\ConditionAction;
use Mageplaza\AutoRelated\Model\RuleFactory;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\DateTime\Filter\Date;

/**
 * Class NewSimilarityActionHtml
 * @package Mageplaza\AutoRelated\Controller\Adminhtml\Condition
 */
class NewSimilarityActionHtml extends ConditionAction
{
    /**
     * @var RuleFactory
     */
    private $rule;

    /**
     * NewSimilarityActionHtml constructor.
     *
     * @param Context $context
     * @param Registry $coreRegistry
     * @param Date $dateFilter
     * @param RuleFactory $rule
     */
    public function __construct(Context $context, Registry $coreRegistry, Date $dateFilter, RuleFactory $rule)
    {
        $this->rule = $rule;

        parent::__construct($context, $coreRegistry, $dateFilter);
    }

    /**
     * @return void
     */
    public function execute()
    {
        $id      = $this->getRequest()->getParam('id');
        $typeArr = explode('|', str_replace('-', '/', $this->getRequest()->getParam('type')));
        $type    = $typeArr[0];

        $model = $this->_objectManager->create($type)
            ->setId($id)
            ->setType($type)
            ->setRule($this->rule->create())
            ->setPrefix('similarity_actions');

        if (!empty($typeArr[1])) {
            $model->setAttribute($typeArr[1]);
        }
        $model->setJsFormObject($this->getRequest()->getParam('form'));
        $html = $model->asHtmlRecursive();
        $this->getResponse()->setBody($html);
    }
}
