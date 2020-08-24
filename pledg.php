<?php

/*

* 2007-2015 PrestaShop

*

* NOTICE OF LICENSE

*

* This source file is subject to the Academic Free License (AFL 3.0)

* that is bundled with this package in the file LICENSE.txt.

* It is also available through the world-wide-web at this URL:

* http://opensource.org/licenses/afl-3.0.php

* If you did not receive a copy of the license and are unable to

* obtain it through the world-wide-web, please send an email

* to license@prestashop.com so we can send you a copy immediately.

*

* DISCLAIMER

*

* Do not edit or add to this file if you wish to upgrade PrestaShop to newer

* versions in the future. If you wish to customize PrestaShop for your

* needs please refer to http://www.prestashop.com for more information.

*

*  @author PrestaShop SA <contact@prestashop.com>
*  @author Ginidev <gildas@ginidev.com>

*  @copyright  2007-2015 PrestaShop SA

*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)

*  International Registered Trademark & Property of PrestaShop SA

*/

require_once _PS_MODULE_DIR_ . '/pledg/class/Pledgpaiements.php';
require_once _PS_MODULE_DIR_ . '/pledg/vendor/autoload.php';
use \Firebase\JWT\JWT;

if (!defined('_PS_VERSION_')) {
    exit;
}

class Pledg extends PaymentModule{

    private $html = '';
    private $postErrors = array();
    public $serviceID;
    public $secretKey;
    public $gateway='https://www.fastpay.com/pay';

    public function __construct(){

        $this->name = 'pledg';
        $this->tab = 'payments_gateways';
        $this->version = '1.0.1';
        $this->author = 'GiniDev';
        $this->controllers = array('payment', 'validation', 'notification');
        $this->currencies = true;
        $this->currencies_mode = 'checkbox';
        $this->bootstrap = true;        
        $this->displayName = $this->l('Split the payment');
        $this->description = $this->l('This module allows you to accept payments by pledg.');
        $this->confirmUninstall = $this->l('Are you sure you want to delete these details?');
        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);

        if (!count(Currency::checkPaymentCurrencies($this->id))) {
            $this->warning = $this->l('No currency has been set for this module.');
        }

        parent::__construct();
    }



    public function install(){

        return parent::install()
        && $this->_installTab()
        && $this->_installSql()
        && $this->registerHook('payment')
        && $this->registerHook('paymentOptions')
        && $this->registerHook('paymentReturn')
        && $this->registerHook('displayBackOfficeHeader');

    }

    protected function _installTab()
    {
        $tab = new Tab();
        $tab->class_name = 'AdminPledg';
        $tab->module = $this->name;
        $tab->id_parent = (int)Tab::getIdFromClassName('DEFAULT');
        $tab->icon = 'settings_applications';
        $languages = Language::getLanguages();
        foreach ($languages as $lang) {
            $tab->name[$lang['id_lang']] = $this->l('Pledg - Paiements');
        }
        try {
            $tab->save();
        } catch (Exception $e) {
            echo $e->getMessage();
            return false;
        }
 
        return true;
    }

    protected function _installSql()
    {
        $sqlCreate = "CREATE TABLE IF NOT EXISTS `" . _DB_PREFIX_ . "pledg_paiements` (
                `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                `status` int(11) NULL,
                `mode` int(11) NULL,
                `position` int(11) NULL,
                `merchant_id` varchar(255) NULL,
                `secret` varchar(255) NULL,
                PRIMARY KEY (`id`)
                ) ENGINE=" . _MYSQL_ENGINE_ . " DEFAULT CHARSET=utf8;";
        $sqlCreate .= "CREATE TABLE IF NOT EXISTS `" . _DB_PREFIX_ . "pledg_paiements_confirm` (
                `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                `id_cart` int(11) NULL,
                `reference_pledg` varchar(255) NULL,
                PRIMARY KEY (`id`)
                ) ENGINE=" . _MYSQL_ENGINE_ . " DEFAULT CHARSET=utf8;";
 
        $sqlCreateLang = "CREATE TABLE IF NOT EXISTS `" . _DB_PREFIX_ . "pledg_paiements_lang` (
              `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
              `id_lang` int(11) NOT NULL,
              `title` varchar(255) NULL,
              `description` text,
              PRIMARY KEY (`id`,`id_lang`)
            ) ENGINE=" . _MYSQL_ENGINE_ . " DEFAULT CHARSET=utf8;";
 
        return Db::getInstance()->execute($sqlCreate) && Db::getInstance()->execute($sqlCreateLang);
    }

    public function uninstall()
    {
        return $this->_uninstallSql()
            && $this->_uninstallTab()
            && $this->unregisterHook('displayBackOfficeHeader')
            && parent::uninstall();

    }

    protected function _uninstallTab()
    {
        $idTab = (int)Tab::getIdFromClassName('AdminPledg');
        if ($idTab) {
            $tab = new Tab($idTab);
            try {
                $tab->delete();
            } catch (Exception $e) {
                echo $e->getMessage();
                return false;
            }
        }
        return true;
    }

    protected function _uninstallSql()
    {
        $sql = "DROP TABLE IF EXISTS `". _DB_PREFIX_ ."pledg_paiements`, `". _DB_PREFIX_ ."pledg_paiements_lang`, `". _DB_PREFIX_ ."pledg_paiements_confirm`;";
        return Db::getInstance()->execute($sql);
    }

    public function hookPaymentOptions($params){

        if (!$this->active) {
            return;
        }

        if (!$this->checkCurrency($params['cart'])) {
            return;
        }

        $payment_options = [];

        $sql = 'SELECT p.merchant_id, p.mode, pl.title, pl.description 
                FROM '. _DB_PREFIX_ .Pledgpaiements::$definition['table'] . ' AS p 
                LEFT JOIN '. _DB_PREFIX_ .Pledgpaiements::$definition['table'] . '_lang AS pl ON pl.id = p.id
                WHERE p.status = 1 AND pl.id_lang = ' . $this->context->language->id;
        if ($results = Db::getInstance()->ExecuteS($sql)):

            foreach($results as $result):

                $this->context->smarty->assign([
                    'description'=> $result['description']
                ]);

                $newOption = new PaymentOption();
                $newOption->setModuleName($result['title']);
                $newOption->setCallToActionText($result['title']);
                $newOption->setAction($this->context->link->getModuleLink($this->name, 'iframe', array(), true));
                $newOption->setAdditionalInformation($this->fetch('module:pledg/views/templates/front/payment_infos.tpl'));
                $newOption->setInputs([
                    'merchantUid' => [
                        'name' =>'merchantUid',
                        'type' =>'hidden',
                        'value' => $result['merchant_id'],
                    ],
                    'mode' => [
                        'name' =>'mode',
                        'type' =>'hidden',
                        'value' => ( ($result['mode'] == 1)? 'https://front.ecard.pledg.co' : 'https://staging.front.ecard.pledg.co' ),
                    ],
                ]);

                array_push($payment_options, $newOption);
                


            endforeach;

        endif;        

        return $payment_options;

    }

    public function checkCurrency($cart){

        $currency_order = new Currency((int)($cart->id_currency));
        $currencies_module = $this->getCurrency((int)$cart->id_currency);

        if (is_array($currencies_module)) {
            foreach ($currencies_module as $currency_module) {
                if ($currency_order->id == $currency_module['id_currency']) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Payment Page
     * @param $params
     */
    public function hookPayment($params)
    {

        $cart = $this->context->cart;

        // On liste les moyens de de paiements disponibles
        $payments_pledg = [];

        // Title
        $products = $cart->getProducts();
        $title = array();
        foreach ($products as $product) {
            array_push($title, $product['name']);
        }

        // Customer
        $id_customer = $cart->id_customer;
        $customer = New Customer($id_customer);

        // Total
        $total = str_replace('.', '', number_format($cart->getOrderTotal(), 2, '.', ''));
        $id_address_delivery = $cart->id_address_delivery;
        $address = new Address($id_address_delivery);
        $id_country = $address->id_country;
        $country_iso_code = Country::getIsoById($id_country);

        // Currency
        $currency = New Currency($cart->id_currency);

        $sql = 'SELECT p.id, p.merchant_id, p.mode, pl.title, pl.description, p.secret 
                FROM '. _DB_PREFIX_ .Pledgpaiements::$definition['table'] . ' AS p 
                LEFT JOIN '. _DB_PREFIX_ .Pledgpaiements::$definition['table'] . '_lang AS pl ON pl.id = p.id
                WHERE p.status = 1 AND pl.id_lang = ' . $this->context->language->id;
        if ($results = Db::getInstance()->ExecuteS($sql)) {
            foreach ($results as $result) {
                $paramsPledg = [];


                $paramsPledg = array(
                    'id' => $result['id'],
                    'titlePayment' => $result['title'],
                    'merchantUid' => $result['merchant_id'],
                    'mode' => (($result['mode'] == 1) ? 'master' : 'staging'),
                    'title' => ( ($title)? implode(', ', $title) : '' ),
                    'reference' => 'order_' . $cart->id,
                    'amountCents' => $total,
                    'currency' =>  $currency->iso_code,
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
                    'redirectUrl' => $this->context->link->getModuleLink($this->name, 'validation', array(), true),
                    'cancelUrl' => $this->context->link->getModuleLink($this->name, 'cancel', array(), true),
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
                );


                if (isset($result['secret'])) {

                    $arrayPayload['data']['merchantUid'] = $result['merchant_id'];
                    $arrayPayload['data']['amountCents'] = $total;
                    $arrayPayload['data']['title'] = $paramsPledg['title'];
                    $arrayPayload['data']['email'] = $paramsPledg['email'];
                    $arrayPayload['data']['reference'] = $paramsPledg['reference'];
                    $arrayPayload['data']['firstName'] = $paramsPledg['firstName'];
                    $arrayPayload['data']['lastName'] = $paramsPledg['lastName'];
                    $arrayPayload['data']['address'] = $paramsPledg['shippingAddress'];

                    $paramsPledg['signature'] = JWT::encode($arrayPayload, $result['secret']);
                }

                $paramsPledg['notificationUrl'] = $this->context->link->getModuleLink($this->name, 'notification', array('pledgPayment' => $result['id']), true);
                $payments_pledg[] = $paramsPledg;

                //
            }
        }

        $this->context->controller->addCss($this->_path.'assets/css/pledg.css');

        $this->context->smarty->assign([
            'payments_pledg'=> $payments_pledg,
            'pledg_action' => $this->context->link->getModuleLink($this->name, 'iframe', array(), true)
        ]);

        return $this->display(__FILE__, 'payment.tpl');
    }

    public function hookPaymentReturn($params){
        if (!$this->active) {
            return;
        }

        $state = $params['objOrder']->getCurrentState();
        if (in_array($state, array(Configuration::get('PS_OS_PAYMENT'), Configuration::get('PS_OS_OUTOFSTOCK'), Configuration::get('PS_OS_OUTOFSTOCK_UNPAID'))))
        {
            $this->smarty->assign(array(
                'total_to_pay' => Tools::displayPrice($params['total_to_pay'], $params['currencyObj'], false),
                'status' => 'ok',
                'id_order' => $params['objOrder']->id
            ));
            if (isset($params['objOrder']->reference) && !empty($params['objOrder']->reference)) {
                $this->smarty->assign('reference', $params['objOrder']->reference);
            }
        }
        else {
            $this->smarty->assign('status', 'failed');
        }

        return $this->display(__FILE__, 'payment_return.tpl');
    }

    public function hookDisplayBackOfficeHeader() {
        $this->context->controller->addCss($this->_path.'assets/css/tab.css');
    }

}