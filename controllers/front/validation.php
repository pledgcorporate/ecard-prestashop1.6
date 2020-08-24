<?php

require_once _PS_MODULE_DIR_ . 'pledg/class/PledgpaiementsConfirm.php';
class PledgValidationModuleFrontController extends ModuleFrontController
{
    /**
     * @see FrontController::postProcess()
     */
    public function postProcess()
    {
        $cart = $this->context->cart;
        $idPledgpaiementsConfirm = PledgpaiementsConfirm::getByIdCart($this->context->cart->id);
        $sleep = 0;
        while ($idPledgpaiementsConfirm == 0 && $sleep++ < 10) {
            sleep(1); // Webhook is not call in real time
            $idPledgpaiementsConfirm = PledgpaiementsConfirm::getByIdCart($this->context->cart->id);
        }

        $pledgpaiementsConfirm = new PledgpaiementsConfirm($idPledgpaiementsConfirm);

        $cartValue = (int)str_replace('order_', '', Tools::getValue('reference'));
        $transactionId = Tools::getValue('transaction');

        if (
            $pledgpaiementsConfirm->id == null ||
            $cartValue != $cart->id ||
            $transactionId != $pledgpaiementsConfirm->reference_pledg ||
            $cart->id_customer == 0 ||
            $cart->id_address_delivery == 0 ||
            $cart->id_address_invoice == 0 ||
            !$this->module->active
        ) {
            Tools::redirect('index.php?controller=order&step=1');
        }

        // Check that this payment option is still available in case the customer changed his address just before the end of the checkout process
        $authorized = false;
        foreach (Module::getPaymentModules() as $module) {
            if ($module['name'] == 'pledg') {
                $authorized = true;
                break;
            }
        }

        if (!$authorized) {
            die($this->module->l('This payment method is not available.', 'validation'));
        }

        $this->context->smarty->assign([
            'params' => $_REQUEST,
        ]);

        $this->setTemplate('payment_return.tpl');


        $mailVars = array();
        $currency = $this->context->currency;
        $total = (float)$cart->getOrderTotal(true, Cart::BOTH);

        $id_customer = $cart->id_customer;
        $customer = New Customer($id_customer);

        $this->module->validateOrder((int)$cart->id, 2, $total, $this->module->displayName, null, $mailVars, (int)$currency->id, false, $customer->secure_key);

        Tools::redirect('index.php?controller=order-confirmation&id_cart='.(int)$cart->id.'&id_module='.(int)$this->module->id.'&id_order='.$this->module->currentOrder.'&key='.$customer->secure_key);
    }
}