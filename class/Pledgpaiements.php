<?php



class Pledgpaiements extends ObjectModel{

    public $id;
    public $mode;
    public $status;
    public $merchant_id;
    public $secret;

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
            'merchant_id'           => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName'],
            'secret'           => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName'],

            //Champs langue

            'title' => ['type' => self::TYPE_HTML, 'lang' => true, 'validate' => 'isCleanHtml', 'size' => 255],
            'description' => ['type' => self::TYPE_HTML, 'lang' => true, 'validate' => 'isCleanHtml'],

        ],

    ];

}