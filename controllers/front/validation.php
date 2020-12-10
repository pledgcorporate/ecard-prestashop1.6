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

        Logger::addLog(
            sprintf(
                $this->module->l('Pledg Payment Validation - Load Cart : %s'),
                    $this->context->cart->id
            ),
            1,
            null,
            null,
            null,
            true
        );
        Logger::addLog(
            sprintf(
                $this->module->l('Pledg Payment Validation - Data receive : %s'),
                serialize($_POST)
            ),
            1,
            null,
            null,
            null,
            true
        );

        $reference = null;
        $reference = $_POST['reference'] ?? $_POST['transaction'];
        Logger::addLog(
            sprintf(
                $this->module->l('Pledg Payment Validation - Reference payment : '),
                $reference
            ),
            1,
            null,
            null,
            null,
            true
        );

        if (empty($reference)) {
            Logger::addLog(
                $this->module->l('Pledg Payment Validation - Reference payment is null'),
                2,
                null,
                null,
                null,
                true
            );
        }

        $cartId = intval(str_replace(Pledg::PLEDG_REFERENCE_PREFIXE, '', $reference));
        if (!is_int($cartId)) {
            Logger::addLog(
                sprintf(
                    $this->module->l(
                        'Pledg Payment Validation - Reference ID doesn\'t seems to be a associated to a Cart : %s'
                    ),
                    $cartId
                ),
                2,
                null,
                null,
                null,
                true
            );
            Tools::redirect('index.php?controller=order&step=1');
            exit;
        }

        $cart = new Cart($cartId);
        if (!Validate::isLoadedObject($cart)) {
            Logger::addLog(
                sprintf(
                    $this->module->l('Pledg Payment Validation - Cart doesn\t exist : '),
                    $cartId
                ),
                2,
                null,
                null,
                null,
                true
            );
            Tools::redirect('index.php?controller=order&step=1');
            exit;
        }

        $sleep = 0;
        $order = null;
        while (!Validate::isLoadedObject($order) && $sleep++ < 20) {
            $orderId = Order::getOrderByCartId((int)($cartId));
            $order = new Order($orderId);

            if (Validate::isLoadedObject($order) && $order->getCurrentState() != _PS_OS_PAYMENT_) {
                Logger::addLog(
                    sprintf(
                        $this->module->l('Order found but status is wrong : %s'),
                        serialize($order->getCurrentState())
                    ),
                    1,
                    null,
                    null,
                    null,
                    true
                );
                if ($sleep < 20) {
                    $order = null;
                }
            }

            sleep(1); // Webhook is not call in real time
            Logger::addLog(
                sprintf(
                    $this->module->l('Pledg Payment Validation - Load Order By cart Id, try N°: %s'),
                    $sleep
                ),
                1,
                null,
                null,
                null,
                true
            );
        }

        if (!Validate::isLoadedObject($order)) {
            Logger::addLog(
                sprintf(
                    $this->module->l('Pledg Payment Validation - Can\'t load order with cart ID : %s'),
                    (int)($cartId)
                ),
                2,
                null,
                null,
                null,
                true
            );
            Tools::redirect('index.php?controller=order&step=1');
            exit;
        }

        // Check if order is validated
        if ($order->getCurrentState() != _PS_OS_PAYMENT_) {
            Logger::addLog(
                sprintf(
                    $this->module->l('Pledg Payment Validation - Order exist but status is Wrong %s (STATUS: %s)'),
                    (int)($cartId),
                    $order->getCurrentState()
                ),
                2,
                null,
                null,
                null,
                true
            );
        } else {
            Logger::addLog(
                sprintf(
                    $this->module->l('Pledg Payment Validation - Order exists and was validated %s'),
                    (int)($order->id)
                ),
                1,
                null,
                null,
                null,
                true
            );
        }

        $id_customer = $cart->id_customer;
        $customer = New Customer($id_customer);

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