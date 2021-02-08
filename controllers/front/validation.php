<?php

require_once _PS_MODULE_DIR_ . 'pledg/class/PledgpaiementsConfirm.php';
class PledgValidationModuleFrontController extends ModuleFrontController
{
    /**
     * @see FrontController::postProcess()
     */
    public function postProcess()
    {
        // Check if pledg module is activated
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

        $reference = null;
        $reference = $_POST['reference'] ?? $_POST['transaction'];
        Logger::addLog(sprintf($this->module->l('Pledg Payment Validation - Reference payment : %s'),$reference));

        if (empty($reference)) {
            Logger::addLog($this->module->l('Pledg Payment Validation - Reference payment is null'),2);
        }

        $cartId = intval(str_replace(Pledg::PLEDG_REFERENCE_PREFIXE, '', $reference));
        if (!is_int($cartId)) {
            Logger::addLog(
                sprintf($this->module->l('Pledg Payment Validation - Reference ID doesn\'t seems to be a associated to a Cart : %s'),
                    $cartId),
                2);
            Tools::redirect('index.php?controller=order&step=1');
            exit;
        }

        $cart = new Cart($cartId);
        $order = new Order(Order::getOrderByCartId((int)$cartId));
        if (!Validate::isLoadedObject($cart) && !Validate::isLoadedObject($order)) {
            Logger::addLog(sprintf($this->module->l('Pledg Payment Validation - Cart doesn\t exist : '),$cartId),2);
            Tools::redirect('index.php?controller=order&step=1');
            exit;
        }
        
        $customer = New Customer($cart->id_customer);
        $priceConverted = Tools::convertPrice($cart->getOrderTotal(), Currency::getIdByIsoCode($cart->getCurrency));
        
        if(!Validate::isLoadedObject($order)){
            $this->module->validateOrder(
                (int)($cartId),
                Configuration::get('PLEDG_STATE_WAITING_NOTIFICATION'),
                $priceConverted,
                $this->module->name,
                null,
                null,
                null,
                false,
                $customer->secure_key
            );
        }
        else{
            Logger::addLog(sprintf($this->module->l('Pledg Payment Validation - Reference ID has already been validated by notification : %s'),$cartId));
        }
        Tools::redirect(
            'index.php?controller=order-confirmation&id_cart='.
            (int)$cart->id.
            '&id_module='. (int)$this->module->id.
            '&id_order='.$order->id.
            '&key='.$customer->secure_key
        );
        exit;
    }
}