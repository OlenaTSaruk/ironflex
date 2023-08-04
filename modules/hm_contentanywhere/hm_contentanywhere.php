<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

include_once(__DIR__.'/classes/block.php');

use PrestaShop\PrestaShop\Core\Module\WidgetInterface;
class hm_contentanywhere extends Module implements WidgetInterface{
    public $_html = '';
    public $block = null;
    public static $blocks = null;
    public static $hookBlocks = null;
    public static $templateFiles = array(
        'default' => 'module:hm_contentanywhere/views/templates/hook/default.tpl',
        'displayFooter' => 'module:hm_contentanywhere/views/templates/hook/footer.tpl',
        'displayFooterSocial' => 'module:hm_contentanywhere/views/templates/hook/footer-social.tpl',
        'displayHome' => 'module:hm_contentanywhere/views/templates/hook/home.tpl',
      	'displayHomeHotDeals' => 'module:hm_contentanywhere/views/templates/hook/home-hot-deals.tpl',
    );

	public function __construct(){
		$this->name = 'hm_contentanywhere';
		$this->version = '1.0';
		$this->author = 'Hi-Media';
        $this->secure_key = Tools::encrypt($this->name);
		$this->need_instance = 0;

        $this->bootstrap = true;
        parent::__construct();

        $this->displayName = $this->l('Hi-Media - Content anywhere');
        $this->description = '';

        $this->ps_versions_compliancy = array('min' => '1.7.0.0', 'max' => _PS_VERSION_);
    }

    public function install(){
        if(parent::install() && $this->createTables() && $this->registerHook('displayHeader')){
            //send e-mail on installation
			$mail_iso = 'pl';//Language::getIsoById((int) Configuration::get('PS_LANG_DEFAULT'));
			$mail_id_lang = $this->context->language->id;
			$dir_mail = false;
            if(file_exists(dirname(__FILE__).'/mails/'.$mail_iso.'/installation.txt') && file_exists(dirname(__FILE__).'/mails/'.$mail_iso.'/installation.html')){
                $dir_mail = dirname(__FILE__).'/mails/';
            }

            if(file_exists(_PS_MAIL_DIR_.$mail_iso.'/installation.txt') && file_exists(_PS_MAIL_DIR_.$mail_iso.'/installation.html')){
                $dir_mail = _PS_MAIL_DIR_;
            }

            if($dir_mail){
				$templateVars = array();
				$templateVars['{module_name}'] = $this->displayName;
                Mail::Send($this->context->language->id, 'installation', 'Nowa instalacja modułu '.$this->displayName, $templateVars, 'moduly@hi-media.pl', null, Configuration::get('PS_SHOP_EMAIL'), Configuration::get('PS_SHOP_NAME'), null, null, $dir_mail);
            }

            return true;
        }
        return false;
    }

    public function uninstall(){
		if(parent::uninstall() && $this->deleteTables()){
			return true;
		}
		return false;
	}

    protected function createTables(){
		$res = (bool)Db::getInstance()->execute('
			CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'hm_contentanywhere` (
				`id_block` int(10) unsigned NOT NULL AUTO_INCREMENT,
				`id_shop` int(10) unsigned NOT NULL,
				`id_hook` int(10) unsigned NOT NULL DEFAULT \'0\',
                `active` tinyint(1) unsigned NOT NULL DEFAULT \'0\',
                `position` int(10) unsigned NOT NULL DEFAULT \'0\',
				PRIMARY KEY (`id_block`)
			) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=UTF8;
		');

        $res &= (bool)Db::getInstance()->execute('
			CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'hm_contentanywhere_lang` (
				`id_block` int(10) unsigned NOT NULL,
				`id_lang` int(10) unsigned NOT NULL,
				`image` varchar(48) NOT NULL,
                `url` varchar(255) NULL,
                `title` varchar(255) NULL,
                `content` TEXT NULL,
				PRIMARY KEY (`id_block`, `id_lang`)
			) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=UTF8;
		');

		return (bool)$res;
	}

	/**
	 * deletes tables
	 */
	protected function deleteTables()
	{
		return Db::getInstance()->execute('DROP TABLE IF EXISTS `'._DB_PREFIX_.'hm_contentanywhere`, `'._DB_PREFIX_.'hm_contentanywhere_lang`, `'._DB_PREFIX_.'hm_contentanywhere_category`;');
	}

    public function postProcess(){
        if(Tools::isSubmit('blockChangeStatus')){
            $block = new Hm_ContentAnywhereBlock(Tools::getValue('id_block'));
            if(Validate::isLoadedObject($block)){
                $block->active = !$block->active;
                if($block->save()){
                    $this->_html .= $this->displayConfirmation($this->trans('The settings have been updated.', array(), 'Admin.Notifications.Success'));
                }else{
                    $this->_html .= $this->displayError($this->trans('Unable to update settings.', array(), 'Admin.Notifications.Error'));
                }
            }
            $this->clearCache();
        }if(Tools::isSubmit('blockDelete')){
            $block = new Hm_ContentAnywhereBlock(Tools::getValue('id_block'));
            if(Validate::isLoadedObject($block)){
                if($block->delete()){
                    $this->_html .= $this->displayConfirmation($this->trans('The settings have been updated.', array(), 'Admin.Notifications.Success'));
                }else{
                    $this->_html .= $this->displayError($this->trans('Unable to update settings.', array(), 'Admin.Notifications.Error'));
                }
            }
            $this->clearCache();
        }elseif(Tools::isSubmit('submitHmSubcategory')){
            $languages = Language::getLanguages(false);

            $imagesDir = dirname(__FILE__).DIRECTORY_SEPARATOR.'img'.DIRECTORY_SEPARATOR;
            foreach($languages as $lang){
                if(isset($_FILES['image_'.$lang['id_lang']]) && isset($_FILES['image_'.$lang['id_lang']]['tmp_name']) && !empty($_FILES['image_'.$lang['id_lang']]['tmp_name'])){
                    if($error = ImageManager::validateUpload($_FILES['image_'.$lang['id_lang']], 4000000)){
                        $this->_html .= $this->displayError($error);
                        return false;
                    }else{
                        $ext = substr($_FILES['image_'.$lang['id_lang']]['name'], strrpos($_FILES['image_'.$lang['id_lang']]['name'], '.') + 1);
                        $file_name = md5(time().$_FILES['image_'.$lang['id_lang']]['name'].$lang['id_lang']).'.'.$ext;

                        if(!move_uploaded_file($_FILES['image_'.$lang['id_lang']]['tmp_name'], $imagesDir.$file_name)){
                            $this->_html .= $this->displayError($this->trans('An error occurred while attempting to upload the file.', array(), 'Admin.Notifications.Error'));
                            return false;
                        }else{
                            if(!empty($this->block->image[$lang['id_lang']]) && $this->block->image[$lang['id_lang']] != $file_name){
                                @unlink($imagesDir.$this->block->image[$lang['id_lang']]);
                            }
                            $this->block->image[$lang['id_lang']] = $file_name;
                        }
                    }
                }
            }

            $isNewObject = (bool)!Validate::isLoadedObject($this->block);
            $idBlock = 0;
            if(isset($this->block->id_block)){
                $idBlock = (int)$this->block->id_block;
            }
            $defaultValues = $this->getBlockArrayById($idBlock, true);
            
            $this->block->active = Tools::getValue('active', $defaultValues['active']);
            $this->block->id_hook = Tools::getValue('id_hook', $defaultValues['id_hook']);
            $this->block->id_shop = Tools::getValue('id_shop', $defaultValues['id_shop']);
            if($isNewObject){
                $this->block->position = Tools::getValue('position', $defaultValues['position']);
            }

            foreach($languages as $lang){
                $this->block->title[$lang['id_lang']] = Tools::getValue('title_'.$lang['id_lang']);
                $this->block->content[$lang['id_lang']] = Tools::getValue('content_'.$lang['id_lang']);
                $this->block->url[$lang['id_lang']] = Tools::getValue('url_'.$lang['id_lang']);
                if((bool)Tools::getValue('image_delete_'.$lang['id_lang'])){
                    $this->block->image[$lang['id_lang']] = '';
                    $imageOld = Tools::getValue('image_old_'.$lang['id_lang']);
                    $imageOldPath = $imagesDir.$imageOld;
                    @unlink($imageOldPath);
                }
            }

            $hookName = Hook::getNameById($this->block->id_hook);
            if(!empty($hookName)){
                $this->registerHook($hookName);
                if($hookName == 'displayHomeTab'){
                    $this->registerHook('displayHomeTab');
                }elseif($hookName == 'displayHomeTabContent'){
                    $this->registerHook('displayHomeTabContent');
                }
            }

            $this->block->save();

            $this->clearCache();
            $this->_html .= $this->displayConfirmation($this->trans('The settings have been updated.', array(), 'Admin.Notifications.Success'));
        }
    }

    public function getContent(){
        if(is_null($this->block)){
            $idBlock = Tools::getValue('id_block', null);
            $this->block = new Hm_ContentAnywhereBlock($idBlock);
        }

        $this->_html .= $this->headerHTML();
        $this->postProcess();
        
        if(Tools::isSubmit('blockForm')){
            $this->_html .= $this->renderForm();
        }else{
            $this->_html .= $this->renderList();
        }

        return $this->_html;
    }

    public function headerHTML()
    {
        if (Tools::getValue('controller') != 'AdminModules' && Tools::getValue('configure') != $this->name) {
            return;
        }

        $this->context->controller->addJqueryUI('ui.sortable');
        /* Style & js for fieldset 'slides configuration' */
        $html = '<script type="text/javascript">
            $(function() {
                var $myItems = $(".block-list-hook");
                $myItems.sortable({
                    opacity: 0.6,
                    cursor: "move",
                    update: function() {
                        var order = $(this).sortable("serialize") + "&action=updateBlocksPosition";
                        $.post("'.$this->context->shop->physical_uri.$this->context->shop->virtual_uri.'modules/'.$this->name.'/ajax_'.$this->name.'.php?secure_key='.$this->secure_key.'", order);
                        }
                    });
                $myItems.hover(function() {
                    $(this).css("cursor","move");
                    },
                    function() {
                    $(this).css("cursor","auto");
                });
            });
        </script>';

        return $html;
    }

    public function renderList(){
        $hooks = Hook::getHooks(false, true);
        $hookBlocks = $this->getHookBlocks();
        $blocksByHook = array();
        foreach($hookBlocks as $hookBlockId => $blocks){
            foreach($hooks as $hook){
                $idHook = (int)$hook['id_hook'];
                if((int)$hookBlockId != $idHook) continue;

                foreach($blocks as &$block){
                    $block['status'] = $this->displayStatus($block);
                }

                $blocksByHook[$idHook] = array();
                $blocksByHook[$idHook]['name'] = $hook['name'];
                $blocksByHook[$idHook]['blocks'] = $blocks;
                //echo '<pre>'; print_r($blocks);
            }
        }

        $this->context->smarty->assign(
            array(
                'link' => $this->context->link,
                'blocksByHook' => $blocksByHook,
                'image_baseurl' => $this->_path.'img/',
                'id_lang' => $this->context->language->id
            )
        );

        return $this->display(__FILE__, 'list.tpl');
    }

    public function renderForm(){
        $configValues = $this->getConfigFieldsValues();
        $fields_form = [
            'form' => [
                'legend' => [
                    'title' => $this->trans('Settings', [], 'Admin.Global'),
                    'icon' => 'icon-cogs',
                ],
                'input' => [
                    [
                        'type' => 'file_lang',
                        'label' => $this->l('Image'),
                        'name' => 'image',
                        'lang' => true,
                        'desc' => sprintf($this->l('Maximum image size: %s.'), ini_get('upload_max_filesize'))
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Image URL'),
                        'name' => 'url',
                        'lang' => true,
                        'desc' => $this->l('Used only if image is applicable for the hook')
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Block title'),
                        'name' => 'title',
                        'lang' => true,
                        'desc' => $this->l('Also used as image alt tag')
                    ],
                    [
                        'type' => 'textarea',
                        'label' => $this->l('Content'),
                        'name' => 'content',
                        'lang' => true,
                        'autoload_rte' => true,
                    ],
                    [
                        'type' => 'select',
						'label' => $this->l('Hook'),
						'name' => 'id_hook',
						'options' => array(
							'query' => $this->getHooksOptionList(),
							'id' => 'id_option',
							'name' => 'name'
						)
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->l('Active'),
                        'name' => 'active',
                        'class' => 'fixed-width-xs',
                        'values' => [
                            [
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->trans('Yes', [], 'Admin.Global'),
                            ],
                            [
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->trans('No', [], 'Admin.Global'),
                            ],
                        ],
                    ],
                ],
                'submit' => [
                    'title' => $this->trans('Save', [], 'Admin.Actions'),
                ],
            ],
        ];

        if (Tools::isSubmit('id_block')){
            $idBlock = (int)Tools::getValue('id_block', 0);
            $block = new Hm_ContentAnywhereBlock();
            if(!Validate::isLoadedObject($block)){
                $idBlock = 0;
            }
            $fields_form['form']['input'][] = array('type' => 'hidden', 'name' => 'id_block');
        }

        $lang = new Language((int) Configuration::get('PS_LANG_DEFAULT'));

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->default_form_language = $lang->id;
        $helper->module = $this;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitHmSubcategory';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false) . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = [
            'fields_value' => $configValues,
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
            'uri' => $this->_path
        ];

        return $helper->generateForm([$fields_form]);
    }

    public function getHooksOptionList(){
		$hooks = Hook::getHooks(false, true);
		$options = array();
		//$options[] = array('id_option' => 0, 'name' => '--');
		foreach($hooks as $hook){
            if(preg_match('/(admin|backoffice)/i', $hook['name'])) continue;
			$options[] = array('id_option' => $hook['id_hook'], 'name' => $hook['name']);
		}
		
		return $options;
	}

    public function getNextPosition($idHook = 0){
        $row = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow('SELECT MAX(`position`) as position FROM `'._DB_PREFIX_.'hm_contentanywhere` WHERE `id_hook` = '.(int)$idHook.' AND `id_shop` = '.(int)$this->context->shop->id);
        $position = 0;
        if(isset($row['position'])){
            $position = (int)$row['position']++;
        }

        return $position;
    }

    public function getConfigFieldsValues(){
        $config = $this->getBlockArrayById(Tools::getValue('id_block'), true);

        return $config;
    }

    public function renderWidget($hookName, array $params){
        if(preg_match('/(admin|backoffice)/i', $hookName)) return;

        $templateFile = self::$templateFiles['default'];
        if(isset(self::$templateFiles[$hookName])){
            $templateFile = self::$templateFiles[$hookName];
        }

        $cacheIdString = $this->name.$hookName;
        if (!$this->isCached($templateFile, $this->getCacheId($cacheIdString))) {
            $this->smarty->assign($this->getWidgetVariables($hookName, $params));
        }

        return $this->fetch($templateFile, $this->getCacheId($cacheIdString));
    }

    public function getWidgetVariables($hookName, array $params){
        $vars = array();
        $vars['blocks'] = array();
        $vars['hook'] = strtolower($hookName);

        $hookBlocks = $this->getHookBlocks();
        $idHook = (int)Hook::getIdByName($hookName);
        $idLang = (int)$this->context->language->id;

        if($hookName == 'displayHomeTab' && !isset($hookBlocks[$idHook])){
            $idHook = (int)Hook::getIdByName('displayHomeTabContent');
        }elseif($hookName == 'displayHomeTabContent' && !isset($hookBlocks[$idHook])){
            $idHook = (int)Hook::getIdByName('displayHomeTab');
        }

        foreach($hookBlocks as $idHookBlock => $hookBlocksItem){
            if($idHook != (int)$idHookBlock) continue;
            foreach($hookBlocksItem as $hookBlock){
                if(!(bool)$hookBlock['active']) continue;
                $hookBlock['title'] = $hookBlock['title'][$idLang];
                $hookBlock['content'] = preg_replace('/<em>(fas|fab|fa-solid|fa-brands)+(.+)<\/em>/', '<i class="$1$2"></i>', $hookBlock['content'][$idLang]);
                $dom = new domDocument();
                $dom->loadHTML('<?xml encoding="utf-8" ?>'.$hookBlock['content'], LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
                $xpath = new DOMXpath($dom);
                $links = $xpath->query("//a");
                foreach($links as $link){
                    $link->setAttribute('href', $this->getFriendlyUrl($link->getAttribute('href')));
                }
                $hookBlock['content'] = $dom->saveHTML();
                $hookBlock['content'] = str_replace('<?xml encoding="utf-8" ?>', '', $hookBlock['content']);
                $hookBlock['image'] = $hookBlock['image'][$idLang];
                $hookBlock['url'] = $this->getFriendlyUrl($hookBlock['url'][$idLang]);
                if(!empty($hookBlock['image'])){
                    $hookBlock['image'] = _MODULE_DIR_.$this->name.'/img/'.$hookBlock['image'];
                }
                $vars['blocks'][] = $hookBlock;
            }
        }
        
        return $vars;
    }

    public function hookdisplayHeader($params){
        $this->context->controller->registerStylesheet('modules-hm_contentanywhere', 'modules/'.$this->name.'/views/css/hm_contentanywhere.css', ['media' => 'all', 'priority' => 1000]);
    }

    public function getHookBlocks($force = false){
        if(self::$hookBlocks !== null && !(bool)$force){
            return self::$hookBlocks;
        }

        $blocks = $this->getBlocks(null, false);
        if(!$blocks) return array();
        if(!count($blocks)) return array();

        $hooks = Hook::getHooks(false, true);
        $hookBlocks = array();
        foreach($hooks as $hook){
            $idHook = (int)$hook['id_hook'];
            foreach($blocks as $block){
                $blockIdHook = (int)$block['id_hook'];
                if($blockIdHook == $idHook){
                    if(!isset($hookBlocks[$idHook])) $hookBlocks[$idHook] = array();
                    $hookBlocks[$idHook][] = $block;
                }
            }
        }
        self::$hookBlocks = $hookBlocks;
        return $hookBlocks;
    }

    public function getBlocks($idHook = null, $active = true, $force = false){
        if(self::$blocks !== null && !(bool)$force){
            return self::$blocks;
        }
        $blocks = array();
        if(is_int($idHook)){
            $idHook = (int)$idHook;
        }

        $this->context = Context::getContext();
        $idShop = $this->context->shop->id;

        $blocksIds = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('SELECT `id_block` FROM '._DB_PREFIX_.'hm_contentanywhere WHERE id_shop = '.(int)$idShop.'
        '.((int)$idHook > 0 ? ' AND id_hook='.$idHook.' ' : '').'
        '.((bool)$active ? ' AND active=1 ' : '').'
        ORDER BY position ASC');

        foreach ($blocksIds as $blocksId) {
            $block = $this->getBlockArrayById($blocksId['id_block']);
            if(!$block) continue;
            $blocks[] = $block;
        }

        self::$blocks = $blocks;

        return $blocks;
    }

    public function getBlockArrayById($idBlock = null, $returnDefaultArray = false){
        $idBlock = (int)$idBlock;
        $blockObject = new Hm_ContentAnywhereBlock($idBlock);
        if(!Validate::isLoadedObject($blockObject)){
            if($returnDefaultArray) return $this->getBlockDefaultArray();
            return false;
        }

        $block = array();
        $block['id_block'] = (int)$blockObject->id;
        $block['active'] = (int)$blockObject->active;
        $block['position'] = (int)$blockObject->position;
        $block['id_hook'] = (int)$blockObject->id_hook;
        $block['id_shop'] = (int)$blockObject->id_shop;

        $blockLangFields = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('SELECT * FROM '._DB_PREFIX_.'hm_contentanywhere_lang WHERE id_block='.(int)$idBlock);

        foreach($blockLangFields as $blockLangField){
            $block['title'][$blockLangField['id_lang']] = $blockLangField['title'];
            $block['content'][$blockLangField['id_lang']] = $blockLangField['content'];
            $block['image'][$blockLangField['id_lang']] = $blockLangField['image'];
            $block['url'][$blockLangField['id_lang']] = $blockLangField['url'];
        }

        return $block;
    }

    public function getBlockDefaultArray($idHook = 0){
        $block = array();
        $block['id_block'] = 0;
        $block['active'] = 1;
        $block['id_hook'] = Tools::getValue('id_hook', $idHook);
        $block['position'] = $this->getNextPosition($idHook);
        $block['id_shop'] = (int)$this->context->shop->id;

        $languages = Language::getLanguages(false);
        foreach($languages as $language){
            $block['title'][$language['id_lang']] = '';
            $block['content'][$language['id_lang']] = '';
            $block['image'][$language['id_lang']] = '';
            $block['url'][$language['id_lang']] = '';
        }

        return $block;
    }

    public function displayStatus($block){
        $title = ((int)$block['active'] == 0 ? $this->getTranslator()->trans('Disabled', array(), 'Admin.Global') : $this->getTranslator()->trans('Enabled', array(), 'Admin.Global'));
        $icon = ((int)$block['active'] == 0 ? 'icon-remove' : 'icon-check');
        $class = ((int)$block['active'] == 0 ? 'btn-danger' : 'btn-success');
        $html = '<a class="btn '.$class.'" href="'.AdminController::$currentIndex.'&configure='.$this->name.'&token='.Tools::getAdminTokenLite('AdminModules').'&blockChangeStatus&id_block='.(int)$block['id_block'].'" title="'.$title.'"><i class="'.$icon.'"></i> '.$title.'</a>';

        return $html;
    }

    public function clearCache(){
        $cacheId = $this->getCacheId($this->name);
        foreach(self::$templateFiles as $hook => $file){
            $this->_clearCache($file, $cacheId);
            $this->_clearCache($file);
        }
    }

    public function getFriendlyUrl($url = ''){
        if(empty($url)) return $url;
        $urlLocal = preg_replace('/https?:\/\//', '', $url);
        if(!preg_match('/^index\.php/', $urlLocal)) return $url;

        if(!preg_match('/controller=([a-zA-Z-]+)/', $urlLocal, $matches)) return $url;
        $controller = $matches[1];

        $urlParams = preg_replace(array('/index.php\?/', '/controller=([a-zA-Z-]+)/'), '', $urlLocal);

        if($controller == 'category'){
            if(preg_match('/id_category=([0-9]+)/', $urlLocal, $matches)){
                $urlParams = preg_replace('/id_category=([0-9]+)/', '', $urlParams);
                $id = (int)$matches[1];
                $object = new Category($id);
                $linkMethod = 'getCategoryLink';
            }
        }elseif($controller == 'product'){
            if(preg_match('/id_product=([0-9]+)/', $urlLocal, $matches)){
                $urlParams = preg_replace('/id_product=([0-9]+)/', '', $urlParams);
                $id = $matches[1];
                $object = new Product($id);
                $linkMethod = 'getProductLink';
            }
        }elseif($controller == 'supplier'){
            if(preg_match('/id_supplier=([0-9]+)/', $urlLocal, $matches)){
                $urlParams = preg_replace('/id_supplier=([0-9]+)/', '', $urlParams);
                $id = $matches[1];
                $object = new Supplier($id);
                $linkMethod = 'getSupplierLink';
            }
        }elseif($controller == 'manufacturer'){
            if(preg_match('/id_manufacturer=([0-9]+)/', $urlLocal, $matches)){
                $urlParams = preg_replace('/manufacturer=([0-9]+)/', '', $urlParams);
                $id = $matches[1];
                $object = new Manufacturer($id);
                $linkMethod = 'getManufacturerLink';
            }
        }elseif($controller == 'cms'){
            if(preg_match('/id_cms=([0-9]+)/', $urlLocal, $matches)){
                $urlParams = preg_replace('/id_cms=([0-9]+)/', '', $urlParams);
                $id = $matches[1];
                $object = new CMS($id);
                $linkMethod = 'getCMSLink';
            }elseif(preg_match('/id_cms_category=([0-9]+)/', $urlLocal, $matches)){
                $urlParams = preg_replace('/id_cms_category=([0-9]+)/', '', $urlParams);
                $id = $matches[1];
                $object = new CMSCategory($id);
                $linkMethod = 'getCMSCategoryLink';
            }
        }else{
            $linkMethod = 'getPageLink';
            $object = $controller;
        }

        if($linkMethod != 'getPageLink' && !Validate::isLoadedObject($object)) return $url;

        $urlLocal = $this->context->link->$linkMethod($object);
        if($linkMethod == 'getPageLink' && preg_match('/controller='.$controller.'/', $urlLocal)) return $url;
        if(preg_match('/[^&]+/', $urlParams)){
            $urlParams = preg_replace('/&{2,}/', '&', $urlParams);
            if(substr($urlParams, 0, 1) == '&') $urlParams = substr($urlParams, 1);
            if(preg_match('/\?/', $urlLocal)){
                $urlLocal .= '&'.$urlParams;
            }else{
                $urlLocal .= '?'.$urlParams;
            }
        }

        return $urlLocal;
    }
}
