<?php
class kopokopoPaymentStatusModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        parent::initContent();
        
        
        $cart = $this->context->cart;

        // Check if the cart is empty
        if ($cart->nbProducts() <= 0) {
            $message = "Payment was successful! An email has been sent with the payment and order details.";
            $status = "success";
            
            $this->context->smarty->assign(['msg' => $message, 'status' => $status]);
            $this->setTemplate('module:kopokopo/views/templates/front/paymentstatus.tpl');
            return;
        } else{
        
        sleep(15);
        
        $cart = $this->context->cart;

        // Check if the cart is empty
        if ($cart->nbProducts() <= 0) {
            $message = "Payment was successful! An email has been sent with the payment and order details.";
            $status = "success";
            
            $this->context->smarty->assign(['msg' => $message, 'status' => $status]);
            $this->setTemplate('module:kopokopo/views/templates/front/paymentstatus.tpl');
            return;
        } else{
            $message = "Payment not completed! ensure to make payment within 15 seconds";
            $status = "danger";
            
            $this->context->smarty->assign(['msg' => $message, 'status' => $status]);
            $this->setTemplate('module:kopokopo/views/templates/front/paymentstatus.tpl');
        }
        
   
        
    }
        }
        
    
}
