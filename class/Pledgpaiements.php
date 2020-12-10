<?php

/**
 * Class Pledgpaiements
 */
class Pledgpaiements extends ObjectModel{

    public $id;
    public $mode;
    public $status;
    public $merchant_id;
    public $secret;
    public $icon;

    public $title;
    public $description; 

    public static $definition = [

        'table' => 'pledg_paiements',

        'primary' => 'id',

        'multilang' => true,

        'fields' => [

            // Champs Standards

            'status'                => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'mode'                  => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'merchant_id'           => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true],
            'secret'                => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true],
            'icon'                  => ['type' => self::TYPE_STRING],

            //Champs langue

            'title' => ['type' => self::TYPE_HTML, 'lang' => true, 'validate' => 'isCleanHtml', 'size' => 255],
            'description' => ['type' => self::TYPE_HTML, 'lang' => true, 'validate' => 'isCleanHtml'],

        ],

    ];

    /**
     * __toString Method
     *
     * @return false|string
     */
    public function __toString()
    {
        return json_encode($this);
    }

}