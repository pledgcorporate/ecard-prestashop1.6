<?php
/**
 * Controller appelé lors de l'annulation d'un paiement
 *
 * Class PledgCancelModuleFrontController
 */

class PledgCancelModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        parent::initContent();        
        $cart = $this->context->cart;
        Tools::redirect('index.php?controller=order');
    }
}