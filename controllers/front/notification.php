<?php
/**
 * Controller appelé lors de l'annulation d'un paiement
 *
 * Class PledgCancelModuleFrontController
 */

require_once _PS_MODULE_DIR_ . 'pledg/class/Pledgpaiements.php';
require_once _PS_MODULE_DIR_ . 'pledg/class/PledgpaiementsConfirm.php';

class PledgNotificationModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        parent::initContent();

        if (!isset($_GET['pledgPayment'])) {
            exit;
        }

        $pledgPaiement = new Pledgpaiements($_GET['pledgPayment']);
        if ($pledgPaiement->id != $_GET['pledgPayment']) {
            exit;
        }

        $json = file_get_contents('php://input');
        $data = json_decode($json);
        if ($data == false) {
            exit;
        }

        $dataToCheck = array(
            "created_at",
            "error",
            "id",
            "reference",
            "sandbox",
            "status"
        );

        $stringToHash = '';
        foreach ($dataToCheck as $dataCheck) {
            if (!isset($data->{$dataCheck})) {
                exit;
            }
            if ($stringToHash != '') {
                $stringToHash .= $pledgPaiement->secret;
            }
            $stringToHash .= $dataCheck . '=' . $data->{$dataCheck};
         }

        $hash = strtoupper(hash('sha256', $stringToHash));

        if ($hash != $data->signature) {
            exit;
        }

        $cart = new Cart((int)str_replace('order_', '', $data->reference));
        if ($cart->id == null) {
            exit;
        }

        $pledgpaiementsConfirm = new PledgpaiementsConfirm();
        $pledgpaiementsConfirm->id_cart = $cart->id;
        $pledgpaiementsConfirm->reference_pledg = $data->id;
        $pledgpaiementsConfirm->save();


        exit;
    }
}