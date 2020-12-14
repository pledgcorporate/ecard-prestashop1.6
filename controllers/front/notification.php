<?php
/**
 * Controller appelé lors de l'annulation d'un paiement
 *
 * Class PledgCancelModuleFrontController
 */

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
        Logger::addLog(
            sprintf($this->module->l('Pledg Payment Notification URL return : %s'), $json)
        );

        $data = json_decode($json);
        if ($data == false) {
            Logger::addLog(
                sprintf(
                    $this->module->l('Pledg Payment Notification JSON Decode Error : %s'),
                    $json
                ),
                2,
                null,
                null,
                null,
                true
            );
            header('HTTP/1.0 403 Forbidden');
            echo 'Pledg Payment Notification JSON Decode Error';
            exit;
        }
        Logger::addLog(
            sprintf(
                $this->module->l('Pledg Payment Notification JSON DECODE : %s'),
                serialize($data)
            ),
            1,
            null,
            null,
            null,
            true
        );
        Logger::addLog(
            sprintf(
                $this->module->l('Pledg Payment Notification JSON DECODE COUNT: %s'),
                count((array)$data)
            ),
            1,
            null,
            null,
            null,
            true
        );


        // Mode transfert :
        if (count((array)$data) == 1 && isset($data->signature)) {
            try {
                $signatureDecode = \Firebase\JWT\JWT::decode($data->signature, $pledgPaiement->secret, ['HS256']);
            } catch (Exception $e) {
                Logger::addLog(
                    sprintf(
                        $this->module->l('Pledg Payment Notification Mode Transfert Exception : %s'),
                        $e->getMessage()
                    ),
                    2,
                    null,
                    null,
                    null,
                    true
                );
                header('HTTP/1.0 403 Forbidden');
                echo $e->getMessage();
                exit;
            }

            // Log Data receive
            Logger::addLog(
                sprintf(
                    $this->module->l('Pledg Payment Notification Mode Transfert signature decode : %s'),
                    serialize($signatureDecode)
                ),
                1,
                null,
                null,
                null,
                true
            );
            Logger::addLog(
                sprintf(
                    $this->module->l('Pledg Payment Notification Mode Transfert reference : %s'),
                    $signatureDecode->reference
                ),
                1,
                null,
                null,
                null,
                true
            );
            Logger::addLog(
                sprintf(
                    $this->module->l('Pledg Payment Notification Mode Transfert ORDER PREFIXE : %s'),
                    Pledg::PLEDG_REFERENCE_PREFIXE
                ),
                1,
                null,
                null,
                null,
                true
            );

            // Validate Order
            $this->validOrder(
                $signatureDecode->reference,
                $signatureDecode->amount_cents,
                $signatureDecode->transfer_order_item_uid,
                'TRANSFERT',
                $_GET['currency']
            );

        } elseif(count((array)$data) == 1 && !isset($data->signature)) {
            Logger::addLog(
                sprintf(
                    $this->module->l(
                        'Pledg Payment Notification Mode Transfert Exception - Signature doesn\'t found : %s'
                    ),
                    serialize($data)
                ),
                2,
                null,
                null,
                null,
                true
            );

            header('HTTP/1.0 403 Forbidden');
            echo 'Signature doesn\'t found';
            exit;

        } else if (isset($data->transfer_order_item_uid)) {
			// Mode Transfert non signé
			$dataToCheck = array(
                "reference",
				"created",
                "transfer_order_item_uid",
				"amount_cents"
            );

            Logger::addLog(
                sprintf(
                    $this->module->l(
                        'Pledg Payment Notification Mode Transfert NS - Data receive : %s'
                    ),
                    serialize($data)
                ),
                1,
                null,
                null,
                null,
                true
            );

            foreach ($dataToCheck as $dataCheck) {
                if (!isset($data->{$dataCheck})) {
                    Logger::addLog(
                        sprintf(
                            $this->module->l(
                                'Pledg Payment Notification Mode Transfert NS Exception - Params %s is missing (data receive %s).'
                            ),
                            $dataCheck,
                            serialize($data)
                        ),
                        2,
                        null,
                        null,
                        null,
                        true
                    );

                    header('HTTP/1.0 403 Forbidden');
                    echo 'Params ' . $dataCheck . ' is missing (data receive %s)';
                    exit;
                }
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
			
        } else {
            // Mode Back
            $dataToCheck = array(
                "created_at",
                "error",
                "id",
                "reference",
                "sandbox",
                "status"
            );

            Logger::addLog(
                sprintf(
                    $this->module->l(
                        'Pledg Payment Notification Mode Back - Data receive : %s'
                    ),
                    serialize($data)
                ),
                1,
                null,
                null,
                null,
                true
            );

            $stringToHash = '';
            foreach ($dataToCheck as $dataCheck) {
                if (!isset($data->{$dataCheck})) {
                    Logger::addLog(
                        sprintf(
                            $this->module->l(
                                'Pledg Payment Notification Mode Back Exception - Params %s is missing (data receive %s).'
                            ),
                            $dataCheck,
                            serialize($data)
                        ),
                        2,
                        null,
                        null,
                        null,
                        true
                    );

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

            if ($hash != $data->signature) {
                Logger::addLog(
                    sprintf(
                        $this->module->l('Pledg Payment Hash doesn\'t match : Excepted : ' . $data->signature . ' - Generated : ' . $hash),
                        $data->signature,
                        $hash
                    ),
                    1,
                    null,
                    null,
                    null,
                    true
                );
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
     * Valide Order
     *
     * @param $reference
     * @param $amountCents
     * @param string $mode
     */
    public function validOrder($reference, $amountCents, $chargeId, $mode = 'TRANSFERT', $currencyIso = null) {
        $cartId = intval(str_replace(Pledg::PLEDG_REFERENCE_PREFIXE, '', $reference));
        if (!is_int($cartId)) {

            Logger::addLog(
                sprintf(
                    $this->module->l(
                        'Pledg Payment Notification Mode %s - Reference ID doesn\'t seems to be a associated to a Cart : %s'
                    ),
                    $mode,
                    $cartId
                ),
                2,
                null,
                null,
                null,
                true
            );

            header('HTTP/1.0 403 Forbidden');
            echo 'Reference ID doesn\'t seems to be a associated to a Cart : ' . $cartId;
            exit;
        }

        // Search CART
        $cart = new Cart($cartId);
        if (!Validate::isLoadedObject($cart)) {
            Logger::addLog(
                sprintf(
                    $this->module->l('Pledg Payment Notification Mode %s Can\'t load cart ID : %s'),
                    $mode,
                    $cartId
                ),
                2,
                null,
                null,
                null,
                true
            );

            header('HTTP/1.0 403 Forbidden');
            echo 'Can\'t load cart ID : ' . $cartId;
            exit;
        }
        Logger::addLog(
            sprintf(
                $this->module->l('Pledg Payment Notification Mode %s Cart Found : %s'),
                $mode,
                $cart->id
            ),
            1,
            null,
            null,
            null,
            true
        );

        // Compare if cart amount match
        $priceConcerted = Tools::convertPrice($cart->getOrderTotal(), Currency::getIdByIsoCode($currencyIso));

        $total = str_replace(
            '.',
            '',
            number_format($priceConcerted, 2, '.', '')
        );

        Logger::addLog(
            sprintf(
                $this->module->l(
                    'Pledg Payment Notification Mode %s price converted : %s (base : %s), Amount cent : %s,
                     amount_cents (data receive by Pledg) : %s'
                ),
                $mode,
                $priceConcerted,
                serialize($cart->getOrderTotal()),
                $total,
                $amountCents
            ),
            1,
            null,
            null,
            null,
            true
        );

        Logger::addLog(
            sprintf(
                $this->module->l('Pledg Payment Notification Mode %s - BEFORE COMPARE.'),
                $mode
            ),
            1,
            null,
            null,
            null,
            true
        );

        if ($amountCents != $total) {
            Logger::addLog(
                sprintf(
                    $this->module->l('Pledg Payment Notification Mode %s Exception - Total not match.'),
                    $mode
                ),
                1,
                null,
                null,
                null,
                true
            );

            header('HTTP/1.0 403 Forbidden');
            echo 'Total not match';
            exit;
        }

        Logger::addLog(
            sprintf(
                $this->module->l('Pledg Payment Notification Mode %s - AFTER COMPARE.'),
                $mode
            ),
            1,
            null,
            null,
            null,
            true
        );

        Logger::addLog(
            sprintf(
                $this->module->l('Pledg Payment Notification Mode %s - Total match.'),
                $mode
            ),
            1,
            null,
            null,
            null,
            true
        );

        // On peut valider la commande
        $id_customer = $cart->id_customer;
        $customer = New Customer($id_customer);

        $this->module->validateOrder(
            (int)$cart->id,
            _PS_OS_PAYMENT_,
            $priceConcerted,
            $this->module->name,
            null,
            ['transaction_id' => $chargeId],
            null,
            false,
            $customer->secure_key
        );
        Logger::addLog(
            sprintf(
                $this->module->l('Pledg Payment Notification Mode %s - Order validated by notication.'),
                $mode
            ),
            1,
            null,
            null,
            null,
            true
        );

        $pledgpaiementsConfirm = new PledgpaiementsConfirm();
        $pledgpaiementsConfirm->id_cart = $cart->id;
        $pledgpaiementsConfirm->reference_pledg = $reference;
        $pledgpaiementsConfirm->save();

        exit;
    }
}