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
    public $min;
    public $max;
    public $icon;
    public $priority;
    public $shops;

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
            'secret'                => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => false],
            'icon'                  => ['type' => self::TYPE_STRING],
            'min'                  => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'max'                  => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'priority'                  => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'shops'                  => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => false],

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