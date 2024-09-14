<?php

class KopokopoPaymentModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        parent::initContent();

        $cart = $this->context->cart;
        $customer = new Customer($cart->id_customer);
        $amount = $cart->getOrderTotal(true, Cart::BOTH);
        $currency = $this->context->currency->iso_code;
        $email = $customer->email;
        $firstName = $customer->firstname;
        $lastName = $customer->lastname;
        $orderId = $this->context->cookie->id_cart;

        $this->context->smarty->assign([
            'amount' => $amount,
            'currency' => $currency,
            'phone_number' => '',
            'order_id' => $orderId,
            'action_url' => $this->context->link->getModuleLink($this->module->name, 'process', [], true)
        ]);

        $this->setTemplate('module:kopokopo/views/templates/front/payment_execution.tpl');
    }
}
