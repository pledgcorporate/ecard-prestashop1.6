<?php

require_once _PS_MODULE_DIR_ . '/pledg/class/Pledgpaiements.php';
class AdminPledgController extends ModuleAdminController
{
	public function __construct()
    {
        $this->bootstrap = true; //Gestion de l'affichage en mode bootstrap 
        $this->table = Pledgpaiements::$definition['table']; //Table de l'objet
        $this->identifier = Pledgpaiements::$definition['primary']; //Clé primaire de l'objet
        $this->className = Pledgpaiements::class; //Classe de l'objet
        $this->lang = true; //Flag pour dire si utilisation de langues ou non
 
        //Appel de la fonction parente pour pouvoir utiliser la traduction ensuite
        parent::__construct();
 
        //Liste des champs de l'objet à afficher dans la liste
        $this->fields_list = [
        	'status' => [
                'title' => $this->module->l('Afficher'),
                'lang' => true, //Flag pour dire d'utiliser la langue
                'align' => 'left',
                'callback' => 'getStatus',
            ],
            'mode' => [
                'title' => $this->module->l('Mode'),
                'lang' => true, //Flag pour dire d'utiliser la langue
                'align' => 'left',
                'callback' => 'getMode',
            ],
            'title' => [
                'title' => $this->module->l('Titre'),
                'lang' => true, //Flag pour dire d'utiliser la langue
                'align' => 'left',
            ],
            'merchant_id' => [
                'title' => $this->module->l('Merchant ID'),
                'lang' => true, //Flag pour dire d'utiliser la langue
                'align' => 'left',
            ],
            'secret' => [
                'title' => $this->module->l('Secret'),
                'lang' => true, //Flag pour dire d'utiliser la langue
                'align' => 'left',
            ]
        ];
 
        //Ajout d'actions sur chaque ligne
        $this->addRowAction('edit');
        $this->addRowAction('delete');
    }

    public function getStatus($value){

    	$html = 'Non';

    	if($value == 1)
    		$html = 'Oui';

    	return $html;
    }

    public function getMode($value){

    	$html = 'Dev';

    	if($value == 1)
    		$html = 'Prod';

    	return $html;
    }

    /**
     * Gestion de la toolbar
     */
    public function initPageHeaderToolbar()
    {
 
        //Bouton d'ajout
        $this->page_header_toolbar_btn['new'] = array(
            'href' => self::$currentIndex . '&add' . $this->table . '&token=' . $this->token,
            'desc' => $this->module->l('Ajouter un paiement'),
            'icon' => 'process-icon-new'
        );
 
        parent::initPageHeaderToolbar();
    }

    /**
     * Affichage du formulaire d'ajout / création de l'objet
     * @return string
     * @throws SmartyException
     */
    public function renderForm()
    {
        //Définition du formulaire d'édition
        $this->fields_form = [
            //Entête
            'legend' => [
                'title' => $this->module->l('Mode de paiement'),
                'icon' => 'icon-cog'
            ],
            //Champs
            'input' => [
            	[
                    'type' => 'text',
                    'label' => $this->module->l('Titre'),
                    'name' => 'title',
                    'lang' => true,
                    'required' => true,
                    'empty_message' => $this->module->l('Le titre est obligatoire'),
                ],
                [
                    'type' => 'switch', //Type de champ
                    'label' => $this->module->l('Activer le paiement'), //Label
                    'name' => 'status', //Nom
                    'values' => [
                        [
                        	'id' => 'prod',
                            'value' => 1,
                            'label' => $this->l('Production')
                        ],

                        [
                        	'id' => 'dev',
                            'value' => 0,
                            'label' => $this->l('Text')
                        ],

                    ]
                ],
                [
                    'type' => 'switch', //Type de champ
                    'label' => $this->module->l('Mode Production'), //Label
                    'name' => 'mode', //Nom
                    'values' => [
                        [
                        	'id' => 'prod',
                            'value' => 1,
                            'label' => $this->l('Production')
                        ],

                        [
                        	'id' => 'dev',
                            'value' => 0,
                            'label' => $this->l('Dev')
                        ],

                    ]
                ],
                [
                    'type' => 'text',
                    'label' => $this->module->l('Merchant ID'),
                    'name' => 'merchant_id',
                    'required' => true,
                    'empty_message' => $this->module->l('Le merchant ID est obligatoire'),
                ],
                [
                    'type' => 'text',
                    'label' => $this->module->l('Secret'),
                    'name' => 'secret',
                    'required' =>true,
                    'empty_message' => $this->module->l(''),
                ],
                [
                    'type' => 'textarea',
                    'label' => $this->module->l('Description'),
                    'name' => 'description',
                    'lang' => true,
                    'autoload_rte' => true, //Flag pour éditeur Wysiwyg
                ],
            ],
            //Boutton de soumission
            'submit' => [
                'title' => $this->l('Save'), //On garde volontairement la traduction de l'admin par défaut
            ]
        ];
        return parent::renderForm();
    }

}