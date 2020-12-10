<?php



class PledgpaiementsConfirm extends ObjectModel{

    public $id;
    public $id_cart;
    public $reference_pledg;

    public static $definition = [
        'table' => 'pledg_paiements_confirm',
        'primary' => 'id',
        'fields' => [
            // Champs Standards
            'id_cart'                => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'reference_pledg'           => ['type' => self::TYPE_STRING],
        ],
    ];

    public static function getByIdCart($id_cart) {
        $query = new DbQuery();
        $query->select('id');
        $query->from('pledg_paiements_confirm', 't');
        $query->where('`id_cart` = '.(int)$id_cart);
        return (int)Db::getInstance()->getValue($query);
    }
}