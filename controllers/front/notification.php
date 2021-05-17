<?php
require_once _PS_MODULE_DIR_ . 'pledg/class/Pledgpaiements.php';
require_once _PS_MODULE_DIR_ . 'pledg/class/PledgpaiementsConfirm.php';
require_once _PS_MODULE_DIR_ . '/pledg/vendor/autoload.php';

class PledgNotificationModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        parent::initContent();
        if (!isset($_GET['pledgPayment'])) {
            header('HTTP/1.0 403 Forbidden');
            echo 'pledgPayment param is missing';
            exit;
        }

        // Search Pledg Payment
        $pledgPaiement = new Pledgpaiements($_GET['pledgPayment']);
        if ($pledgPaiement->id != $_GET['pledgPayment']) {
            header('HTTP/1.0 403 Forbidden');
            echo 'pledgPayment Object doesn\'t found';
            exit;
        }

        // Retrieve data send by Pledg
        $json = file_get_contents('php://input');

        $data = json_decode($json);
        if ($data == false) {
            Logger::addLog(sprintf($this->module->l('Pledg Payment Notification JSON Decode Error : %s'),$json),2);
            header('HTTP/1.0 403 Forbidden');
            echo 'Pledg Payment Notification JSON Decode Error';
            exit;
        }

        if(count((array)$data) == 1 && !isset($data->signature)) {
            Logger::addLog(
                sprintf(
                    $this->module->l('Pledg Payment Notification Mode Transfert Exception - Signature doesn\'t found : %s'),
                    serialize($data)),
                2);
            header('HTTP/1.0 403 Forbidden');
            echo 'Signature doesn\'t found';
            exit;
        }
        elseif (count((array)$data) == 1 && isset($data->signature)) {
            // Transfer signé
            try {
                $signatureDecode = \Firebase\JWT\JWT::decode($data->signature, $pledgPaiement->secret, ['HS256']);
            } catch (Throwable $e) {
                Logger::addLog(sprintf($this->module->l('Pledg Payment Notification Mode Transfert Exception : %s'),$e->getMessage()),2);
                header('HTTP/1.0 403 Forbidden');
                echo $e->getMessage();
                exit;
            }
            Logger::addLog(
                sprintf($this->module->l('Pledg Payment Notification Mode Transfert reference : %s'),$signatureDecode->reference));
            // Validate Order
            $this->validOrder(
                $signatureDecode->reference,
                $signatureDecode->amount_cents,
                $signatureDecode->transfer_order_item_uid,
                'TRANSFERT',
                $_GET['currency']
            );
            exit;
        }
        elseif (isset($data->transfer_order_item_uid)) {
			// Mode Transfert non signé
			$dataToCheck = array(
                "reference",
				"created",
                "transfer_order_item_uid",
				"amount_cents"
            );
            Logger::addLog(sprintf($this->module->l('Pledg Payment Notification Mode Transfert NS - Data receive : %s'),serialize($data)));
            try{
                foreach ($dataToCheck as $dataCheck) {
                    if (!isset($data->{$dataCheck})) {
                        Logger::addLog(
                            sprintf(
                                $this->module->l('Pledg Payment Notification Mode Transfert NS Exception - Params %s is missing (data receive %s).'),
                                $dataCheck,serialize($data)),
                            2);
                        header('HTTP/1.0 403 Forbidden');
                        echo 'Params ' . $dataCheck . ' is missing (data receive %s)';
                        exit;
                    }
                }
            }
            catch (Throwable $e){
                Logger::addLog($e->getMessage(), 2);
            }
            
            // Validate Order
            $this->validOrder(
                $data->reference,
                $data->amount_cents,
                $data->transfer_order_item_uid,
                'TRANSFERT',
                $_GET['currency']
            );
            exit;
        }
        else {
            // Mode Back
            $dataToCheck = array(
                "created_at",
                "error",
                "id",
                "reference",
                "sandbox",
                "status"
            );
            Logger::addLog(sprintf($this->module->l('Pledg Payment Notification Mode Back - Data receive : %s'),serialize($data)),1);
            $stringToHash = '';
            foreach ($dataToCheck as $dataCheck) {
                if (!isset($data->{$dataCheck})) {
                    Logger::addLog(
                        sprintf(
                            $this->module->l('Pledg Payment Notification Mode Back Exception - Params %s is missing (data receive %s).'),
                            $dataCheck,serialize($data)),
                        2);

                    header('HTTP/1.0 403 Forbidden');
                    echo 'Params ' . $dataCheck . ' is missing (data receive %s)';
                    exit;
                }
                if ($stringToHash != '') {
                    $stringToHash .= $pledgPaiement->secret;
                }
                $stringToHash .= $dataCheck . '=' . $data->{$dataCheck};
            }
            $hash = strtoupper(hash('sha256', $stringToHash));
            if ($hash !== $data->signature) {
                Logger::addLog(
                    sprintf($this->module->l('Pledg Payment Hash doesn\'t match : Excepted : ' . $data->signature . ' - Generated : ' . $hash),
                        $data->signature,$hash));
                header('HTTP/1.0 403 Forbidden');
                echo 'Pledg Payment Hash doesn\'t match';
                exit;
            }
            // Validate Order
            $this->validOrder(
                $data->reference,
                $_GET['amount'],
                $data->additional_data->charge_id,
                'BACK',
                $_GET['currency']
            );
            exit;
        }
    }

    /**
     * Validate the order and mark it as paid
     *
     * @param $reference
     * @param $amountCents
     * @param string $mode
     */
    public function validOrder($reference, $amountCents, $chargeId, $mode = 'TRANSFERT', $currencyIso = null) {
        $cartId = intval(str_replace(Pledg::PLEDG_REFERENCE_PREFIXE, '', $reference));
        $cart = new Cart($cartId);
        try{
            $orderId = Order::getOrderByCartId($cartId);
            $order = new Order($orderId);
        }
        catch (Throwable $e){
            Logger::addLog($e->getMessage(), 2);
        }
        if (!Validate::isLoadedObject($order) && !Validate::isLoadedObject($cart)) {
            Logger::addLog(
                sprintf($this->module->l("Pledg Payment Notification Mode %s Can't load cart ID : %s"),
                    $mode,$cartId),
                2);
            header('HTTP/1.0 403 Forbidden');
            echo 'Can\'t load cart ID : ' . $cartId;
            exit;
        }
        if(!Validate::isLoadedObject($order)){
            sleep(3);
            //Waiting buffer to be sure that the validation and the notification doesn't occur at the same time
        }

        if(!Validate::isLoadedObject($order)){
            // In this case the notification cames before the validation
            // An order has then to be validated and saved in DB
            $customer = New Customer($cart->id_customer);
            $priceConverted = Tools::convertPrice($cart->getOrderTotal(), Currency::getIdByIsoCode($cart->getCurrency));
            $total = str_replace(
                '.',
                '',
                number_format($priceConverted, 2, '.', '')
            );
            if (intval($amountCents) !== intval($total)) {
                Logger::addLog(sprintf($this->module->l('Pledg Payment Notification Mode %s Exception - Total not match. %s - %s - %s'),$mode,$amountCents, $total));
                header('HTTP/1.0 403 Forbidden');
                echo 'Total not match';
                exit;
            }
	    try {
                $this->module->validateOrder(
                    (int)($cartId),
                    Configuration::get('PS_OS_PAYMENT'),
                    $priceConverted,
                    $this->module->name."_".$mode,
                    null,
                    array('transaction_id' => $chargeId),
                    null,
                    false,
                    $customer->secure_key
                );
	    } catch (Throwable $e) {
                Logger::addLog(sprintf($this->module->l('Pledg Payment Notification Exception : %s'),$e->getMessage()),2);
                header('HTTP/1.0 403 Forbidden');
                echo $e->getMessage();
                exit;
            }
		
            Logger::addLog(sprintf($this->module->l('Pledg Payment Notification Mode %s - Order validated by notication (even before validation).'),$mode));

            $pledgpaiementsConfirm = new PledgpaiementsConfirm();
            $pledgpaiementsConfirm->id_cart = $cartId;
            $pledgpaiementsConfirm->reference_pledg = $reference;
            $pledgpaiementsConfirm->save();
        }
        else{
            if($order->current_state === Configuration::get("PS_OS_OUTOFSTOCK_PAID") || $order->current_state === Configuration::get("PS_OS_PAYMENT")){
                Logger::addLog(sprintf($this->module->l('Pledg Payment Notification Mode %s Exception - Order already notified.'),$mode));
                header('HTTP/1.0 403 Forbidden');
                echo 'Already notified';
                exit;
            }
            
            $priceConverted = Tools::convertPrice($cart->getOrderTotal(), Currency::getIdByIsoCode($cart->getCurrency));
            $total = str_replace(
                '.',
                '',
                number_format($priceConverted, 2, '.', '')
            );
            if (intval($amountCents) !== intval($total)) {
                Logger::addLog(sprintf($this->module->l('Pledg Payment Notification Mode %s Exception - Total not match. %s - %s - %s'),$mode,$amountCents, $total));
                header('HTTP/1.0 403 Forbidden');
                echo 'Total not match';
                exit;
            }
            try{
                $order_state = ( $order->current_state === Configuration::get("PS_OS_OUTOFSTOCK_UNPAID")) ? "PS_OS_OUTOFSTOCK_PAID" : "PS_OS_PAYMENT";
                $history = new OrderHistory();
                $history->id_order = (int) $orderId;
                $history->changeIdOrderState((int) Configuration::get($order_state), $history->id_order);
                $history->addWithemail();
                $history->save();
                Db::getInstance()->update('order_payment', array(
                    'transaction_id' => pSQL($chargeId),
                    'payment_method' => pSQL($order->payment."_".$mode),
                ),  'order_reference = "'.pSQL($order->reference).'"');
            }
            catch (Throwable $e){
                Logger::addLog("Pledg Payment Notification Exception : " . $e->getMessage()." Can be ignored if next notification succeeds.",3);
            }
            Logger::addLog(sprintf($this->module->l('Pledg Payment Notification Mode %s - Order validated by notication.'),$mode), 1, null, null, null, true);
            
            $pledgpaiementsConfirm = new PledgpaiementsConfirm();
            $pledgpaiementsConfirm->id_cart = $order->id_cart;
            $pledgpaiementsConfirm->reference_pledg = $reference;
            $pledgpaiementsConfirm->save();
        }
        exit;
    }
}
