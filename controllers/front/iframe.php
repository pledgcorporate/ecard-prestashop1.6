<?php

/**
 * Controller appelé pour la redirection vers la plateforme de paiement
 *
 * Class PledgIframeModuleFrontController
 */
class PledgIframeModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        parent::initContent();        
        $cart = $this->context->cart;      

        if (!$this->module->checkCurrency($cart)) {            
            Tools::redirect('index.php?controller=order');       
        }        

        $products = $cart->getProducts();        
        $title = array();        
        foreach ($products as $product) {
            array_push($title, $product['name']);
        }

        $id_customer = $cart->id_customer;        
        $customer = New Customer($id_customer);        
        $total = str_replace('.', '', number_format($cart->getOrderTotal(), 2, '.', ''));        
        $id_address_delivery = $cart->id_address_delivery;        
        $address = new Address($id_address_delivery);        
        $id_country = $address->id_country;        
        $country_iso_code = Country::getIsoById($id_country);        
        $currency = New Currency($cart->id_currency);        
        $DATA = [            
            'merchantUid' => $_POST['merchantUid'],            
            'title' => ( ($title)? implode(', ', $title) : '' ),            
            'reference' => $cart->id,            
            'amountCents' => $total,            
            'currency' =>  $currency->iso_code,            
            'paymentNotificationUrl' => '',            
            'metadata'  => [                
                'departure-date' => date('Y-m-d')            
            ],            
            'civility' => ( ($customer->id_gender == 1)? 'Mr' : 'Mme' ),            
            'firstName' => $customer->firstname,            
            'lastName' =>  $customer->lastname,            
            'email' => $customer->email,            
            'phoneNumber' => $address->phone,            
            'birthDate' => ( ($customer->birthday != '0000-00-00')? $customer->birthday : date('Y-m-d')),            
            'birthCity' => '',            
            'birthStateProvince' => '',            
            'birthCountry' => '',            
            'redirectUrl' => $this->context->link->getModuleLink($this->module->name, 'validation', array(), true),            
            'cancelUrl' => $this->context->link->getModuleLink($this->module->name, 'cancel', array(), true),            
            'address' => [                
                'street' => $address->address1,                
                'city' => $address->city,                
                'zipcode' => $address->postcode,                
                'stateProvince' => '',                
                'country' => $country_iso_code            
            ],            
            'shippingAddress' => [                
                'street' => $address->address1,                
                'city' => $address->city,                
                'zipcode' => $address->postcode,                
                'stateProvince' => '',                
                'country' => $country_iso_code            
            ],                        
            'showCloseButton' => true,          
        ];      

        $DATA['metadata'] = json_encode($DATA['metadata']);        
        $DATA['address'] = json_encode($DATA['address']);        
        $DATA['shippingAddress'] = json_encode($DATA['shippingAddress']);        
        Tools::redirect($_POST['mode'] . '/purchase?' . http_build_query($DATA));
    }
}