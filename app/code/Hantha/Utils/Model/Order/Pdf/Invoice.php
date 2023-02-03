<?php
namespace Hantha\Utils\Model\Order\Pdf;

class Invoice extends \Magento\Sales\Model\Order\Pdf\Invoice
{
    protected function insertOrder(&$page, $obj, $putOrderId = true)
    {
        parent::insertOrder($page, $obj, $putOrderId);

        /**
         * Here we add the customer's email to the pdf
         */
        $order = $obj;
        $x = 405;
        $y = 715;
        $page->setFillColor(new \Zend_Pdf_Color_Rgb(255, 255, 255));
        $page->drawText("Email: " .$order->getShippingAddress()->getEmail(), $x, $y, 'UTF-8');
    }
}