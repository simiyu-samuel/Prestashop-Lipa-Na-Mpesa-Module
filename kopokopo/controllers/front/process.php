<?php

class KopokopoProcessModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        parent::initContent();

        // Retrieve the order ID and phone number from the request
        $orderId = Tools::getValue('order_id');
        $phoneNumber = Tools::getValue('phone_number');
        
        // Check if phone number is provided
        // if (!$phoneNumber) {
        //     $this->context->smarty->assign('error', $this->module->l('Please enter your phone number.'));
        //     $this->setTemplate('module:kopokopo/views/templates/front/payment_execution.tpl');
        //     return;
        // }

        $action_url = $this->context->link->getModuleLink($this->module->name, 'process', [], true);
        // Retrieve cart and customer details
        $cart = $this->context->cart;
        $customer = new Customer($cart->id_customer);
        $amount = $cart->getOrderTotal(true, Cart::BOTH);
        $currency = $this->context->currency->iso_code;
        $email = $customer->email;
        $firstName = $customer->firstname;
        $lastName = $customer->lastname;
        $cartId = $this->context->cart->id;
        $currencyId = $this->context->currency->id;

        // Initiate the payment process
        $paymentUrl = $this->module->initiatePayment($amount, $currency, $phoneNumber, $email, $firstName, $lastName, $orderId, $currencyId);
        $paymentstatusUrl = $this->context->link->getModuleLink($this->module->name, 'paymentstatus', [], true);
        
        if ($paymentUrl) {
            // Payment initiation was successful
            $this->context->smarty->assign('success', $this->module->l('Payment initiated successfully. Please wait for the confirmation.'));
            $this->context->smarty->assign('order_Id', $orderId);
            $this->context->smarty->assign('p_url', $paymentstatusUrl);
            $this->setTemplate('module:kopokopo/views/templates/front/payment_completion.tpl');
        } else {
            // Assign error message if payment initiation failed
            $this->context->smarty->assign('error', $this->module->l('Payment initiation failed. Please try again.'));
            $this->context->smarty->assign('action_url', $action_url);
            $this->setTemplate('module:kopokopo/views/templates/front/payment_execution.tpl');
        }
    }
}
