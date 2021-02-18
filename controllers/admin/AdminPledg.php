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
                'title' => $this->module->l('Display'),
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
                'title' => $this->module->l('Title'),
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
            ],
            'position' => [
                'title' => $this->module->l('Position'),
                'lang' => true, //Flag pour dire d'utiliser la langue
                'align' => 'right',
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
            'desc' => $this->module->l('Add Payment'),
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
        $img = ($this->object->icon) ? (_MODULE_DIR_ . $this->object->icon) : null ;
        $img = ($img) ? '<img src="' . $img . '" class="img-thumbnail" width="400">' : "";
        $shops = [];
        foreach (Shop::getShops(false) as $key => $shop) {
            $shops[] = array(
                'key' => $shop['id_shop'],
                'name' => $shop['name']
            );
        }
        $this->fields_value['shops[]'] = explode(',',$this->object->shops);
        //Définition du formulaire d'édition
        $this->fields_form = [
            //Entête
            'legend' => [
                'title' => $this->module->l('Payment mode'),
                'icon' => 'icon-cog'
            ],
            //Champs
            'input' => [
            	[
                    'type' => 'text',
                    'label' => $this->module->l('Title'),
                    'name' => 'title',
                    'lang' => true,
                    'required' => true,
                    'empty_message' => $this->module->l('Title is required'),
                ],
                [
                    'type' => 'switch', //Type de champ
                    'label' => $this->module->l('Activated payment'), //Label
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
                            'label' => $this->l('Test')
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
                    'empty_message' => $this->module->l('Merchant ID is required'),
                ],
                [
                    'type' => 'text',
                    'label' => $this->module->l('Secret'),
                    'name' => 'secret',
                    'required' =>false,
                    'empty_message' => $this->module->l(''),
                ],
                [
                    'type' => 'text',
                    'label' => $this->module->l('Min'),
                    'name' => 'min',
                    'required' =>false,
                    'hint' => $this->module->l('Must be a number. Minimum transaction amount, zero does not define a minimum'),
                    'empty_message' => $this->module->l(''),
                ],
                [
                    'type' => 'text',
                    'label' => $this->module->l('Max'),
                    'name' => 'max',
                    'required' =>false,
                    'hint' => $this->module->l('Must be a number. Maximum transaction amount, zero does not define a maximum'),
                    'empty_message' => $this->module->l(''),
                ],
                [
                    'type' => 'file',
                    'label' => $this->module->l('Icon'),
                    'name' => 'icon',
                    'required' =>false,
                    'empty_message' => $this->module->l(''),
                    'image' => $img,
                ],
                [
                    'type' => 'textarea',
                    'label' => $this->module->l('Description'),
                    'name' => 'description',
                    'lang' => true,
                    'autoload_rte' => true, //Flag pour éditeur Wysiwyg
                ],
                [
                    'type' => 'text',
                    'label' => $this->module->l('Position'),
                    'name' => 'position',
                    'required' =>false,
                    'empty_message' => $this->module->l(''),
                ],
                [
                    'type' => 'select',
                    'label' => $this->module->l('Disabled shops'),
                    'name' => 'shops[]',
                    'multiple' => 'true',
                    'options' => array(
                        'query' => $shops,
                        'id' => 'key',
                        'name' => 'name'
                    )
                ]
            ],
            //Boutton de soumission
            'submit' => [
                'title' => $this->l('Save'), //On garde volontairement la traduction de l'admin par défaut
                'name' => 'submitpledgadmin'
            ]
        ];
        return parent::renderForm();
    }

    /**
     * Process Add method
     * @return bool
     */
    public function processAdd() {
        return $this->checkUploadIcon('add') ? parent::processAdd() : false;
    }

    /**
     * Process Update method
     * @return bool
     */
    public function processUpdate() {
        return $this->checkUploadIcon('edit') ? parent::processUpdate() : false;
    }

    public function postProcess()
	{
        if (Tools::isSubmit('submitpledgadmin')) 
		{
            if(is_array(Tools::getValue('shops'))){
                $_POST['shops'] = implode(',', Tools::getValue('shops'));
            }
            else{
                $_POST['shops'] = Tools::getValue('shops');
            }
 		}
		parent::postProcess();
	}

    /**
     * Check if icon uploaded is an image file
     * @param string $display
     * @return bool
     */
    private function checkUploadIcon($display = 'add') {
        if ($_FILES['icon']['error'] == UPLOAD_ERR_NO_FILE) {
            return true;
        }

        if ($_FILES['icon']['error'] !== UPLOAD_ERR_OK) {
            $this->errors[] = sprintf($this->l('Upload failed with error code %s'), $_FILES['icon']['error']);
            $this->display = $display;

            return false;
        }

        $info = getimagesize($_FILES['icon']['tmp_name']);
        if ($info === FALSE) {
            $this->errors[] = $this->l('Unable to determine image type of uploaded file');
            $this->display = $display;

            return false;
        }

        if (($info[2] !== IMAGETYPE_GIF) && ($info[2] !== IMAGETYPE_JPEG) && ($info[2] !== IMAGETYPE_PNG)) {
            $this->errors[] = $this->l('Please give a gif, jpeg or png file.');
            $this->display = $display;

            return false;
        }

        return true;
    }

    /**
     * Upload icon and return path
     *
     * @param $object
     * @return string
     */
    private function uploadIcon($object) {
        if ($_FILES['icon']['error'] == UPLOAD_ERR_NO_FILE) {
            return '';
        }

        $name = '/pledg/assets/img/' . $object->id . '-' .$_FILES['icon']['name'];

        if (!file_exists(_PS_MODULE_DIR_ . '/pledg/assets/img/')) {
            mkdir ( _PS_MODULE_DIR_ . '/pledg/assets/img/', 0777, true);
        }

        if (
            move_uploaded_file(
                $_FILES['icon']['tmp_name'],
                _PS_MODULE_DIR_ . $name
            )
        ) {
            return $name;
        } else {
            $this->errors[] = $this->l('Error on uploaded icon.');
            return '';
        }

    }

    /**
     * After Add Object
     *
     * @param $object
     * @return bool
     */
    protected function afterAdd($object)
    {
        $object->icon = $this->uploadIcon($object);
        $object->save();

        Logger::addLog($this->l('Create Pledg Payment #') . $object->id . ' : ' . $object->__toString(), 1, null, get_class($object), $object->id);

        return true;
    }

    /**
     * After Update Object
     *
     * @param $object
     * @return bool
     */
    protected function afterUpdate($object)
    {
        $icon = $this->uploadIcon($object);
        $object->icon = $icon == '' ? $object->icon : $icon;
        $object->save();

        Logger::addLog($this->l('Update Pledg Payment #') . $object->id . ' : ' . $object->__toString(), 1, null, get_class($object), $object->id);

        return true;
    }
}