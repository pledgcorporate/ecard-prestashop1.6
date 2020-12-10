<?php

class PledgLogModuleFrontController extends ModuleFrontController
{
    public function initContent(){

        parent::initContent();

        if(
            !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest'
        ) {
            $message = Tools::getValue("message");
            $type = in_array(Tools::getValue("type"), ['success', 'info']) ? 1 : 3;
            $id = Tools::getValue("id");
            $class = Tools::getValue("class");

            Logger::addLog($message, 1, null, $class, $id);

            $json = array(
                'status' => Logger::addLog($message, $type, null, $class, $id, true),
            );
            die(Tools::jsonEncode($json));
        }

        $json = array();
        die(Tools::jsonEncode($json));
    }
}