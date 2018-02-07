<?php
use PrestaShop\PrestaShop\Core\Module\WidgetInterface;
class thnxblockcategories extends Module implements WidgetInterface
{
	public $css_files = array(
		array(
			'key' => 'thnxblockcategories_css',
			'src' => 'thnxblockcategories.css',
			'priority' => 50,
			'media' => 'all',
			'load_theme' => false,
		),
	);
	public $js_files = array(
		array(
			'key' => 'treeManagement',
			'src' => 'treeManagement.js',
			'priority' => 50,
			'position' => 'bottom', // bottom or head
			'load_theme' => false,
		),
	);
	public function __construct()
	{
		$this->name = 'thnxblockcategories';
		$this->tab = 'front_office_features';
		$this->version = '1.0.0';
		$this->author = 'thanksit.com';
		$this->bootstrap = true;
		parent::__construct();
		$this->displayName = $this->l('Platinum Theme Categories block list');
		$this->description = $this->l('Platinum Theme Adds a block featuring product categories.');
		$this->ps_versions_compliancy = array('min' => '1.7', 'max' => _PS_VERSION_);
	}
	public function install()
	{
		$tab = new Tab();
		$tab->active = 1;
		$tab->class_name = 'AdminthnxblockCategories';
		$tab->name = array();
		foreach (Language::getLanguages(true) as $lang)
			$tab->name[$lang['id_lang']] = 'Block Categories';
		$tab->id_parent = -1;
		$tab->module = $this->name;
		if (!$tab->add() ||
			!parent::install() ||
			!$this->registerHook('header') ||
			!$this->registerHook('displayLeftColumn') ||
			!Configuration::updateValue('thnxBLOCK_CATEG_MAX_DEPTH', 4) ||
			!Configuration::updateValue('thnxBLOCK_CATEG_DHTML', 1) ||
			!Configuration::updateValue('thnxBLOCK_CATEG_ROOT_CATEGORY', 1))
				return false;
		return true;
	}
	public function uninstall()
	{
		$id_tab = (int)Tab::getIdFromClassName('AdminthnxblockCategories');
		if ($id_tab)
		{
			$tab = new Tab($id_tab);
			$tab->delete();
		}
		if (!parent::uninstall() ||
			!Configuration::deleteByName('thnxBLOCK_CATEG_MAX_DEPTH') ||
			!Configuration::deleteByName('thnxBLOCK_CATEG_DHTML') ||
			!Configuration::deleteByName('thnxBLOCK_CATEG_ROOT_CATEGORY'))
			return false;
		return true;
	}
	public function getContent()
	{
		$output = '';
		if (Tools::isSubmit('submitthnxblockcategories'))
		{
			$maxDepth = (int)(Tools::getValue('thnxBLOCK_CATEG_MAX_DEPTH'));
			$dhtml = Tools::getValue('thnxBLOCK_CATEG_DHTML');
			if ($maxDepth < 0)
				$output .= $this->displayError($this->l('Maximum depth: Invalid number.'));
			elseif($dhtml != 0 && $dhtml != 1)
				$output .= $this->displayError($this->l('Dynamic HTML: Invalid choice.'));
			else
			{
				Configuration::updateValue('thnxBLOCK_CATEG_MAX_DEPTH', (int)$maxDepth);
				Configuration::updateValue('thnxBLOCK_CATEG_DHTML', (int)$dhtml);
				Configuration::updateValue('thnxBLOCK_CATEG_SORT_WAY', Tools::getValue('thnxBLOCK_CATEG_SORT_WAY'));
				Configuration::updateValue('thnxBLOCK_CATEG_SORT', Tools::getValue('thnxBLOCK_CATEG_SORT'));
				Configuration::updateValue('thnxBLOCK_CATEG_ROOT_CATEGORY', Tools::getValue('thnxBLOCK_CATEG_ROOT_CATEGORY'));
				Tools::redirectAdmin(AdminController::$currentIndex.'&configure='.$this->name.'&token='.Tools::getAdminTokenLite('AdminModules').'&conf=6');
			}
		}
		return $output.$this->renderForm();
	}
	public function getTree($resultParents, $resultIds, $maxDepth, $id_category = null, $currentDepth = 0)
	{
		if (is_null($id_category))
			$id_category = $this->context->shop->getCategory();
		$children = array();
		if (isset($resultParents[$id_category]) && count($resultParents[$id_category]) && ($maxDepth == 0 || $currentDepth < $maxDepth))
			foreach ($resultParents[$id_category] as $subcat)
				$children[] = $this->getTree($resultParents, $resultIds, $maxDepth, $subcat['id_category'], $currentDepth + 1);
		if (isset($resultIds[$id_category])) 
		{
			$link = $this->context->link->getCategoryLink($id_category, $resultIds[$id_category]['link_rewrite']);
			$name = $resultIds[$id_category]['name'];
			$desc = $resultIds[$id_category]['description'];
		}
		else
			$link = $name = $desc = '';
		$return = array(
			'id' => $id_category,
			'link' => $link,
			'name' => $name,
			'desc'=> $desc,
			'children' => $children
		);
		return $return;
	}
	public function renderWidget($hookName = null, array $configuration = [])
	{
	    $this->smarty->assign($this->getWidgetVariables($hookName,$configuration));
	    return $this->fetch('module:'.$this->name.'/'.$this->name.'.tpl');	
	}
	public function getWidgetVariables($hookName = null, array $configuration = [])
	{
		$return_arr = array();
	    $this->setLastVisitedCategory();
	    $phpself = $this->context->controller->php_self;
	    $current_allowed_controllers = array('category');
	    if ($phpself != null && in_array($phpself, $current_allowed_controllers) && Configuration::get('thnxBLOCK_CATEG_ROOT_CATEGORY') && isset($this->context->cookie->last_visited_category) && $this->context->cookie->last_visited_category)
	    {
	    	$category = new Category($this->context->cookie->last_visited_category, $this->context->language->id);
	    	if (Configuration::get('thnxBLOCK_CATEG_ROOT_CATEGORY') == 2 && !$category->is_root_category && $category->id_parent)
	    		$category = new Category($category->id_parent, $this->context->language->id);
	    	elseif (Configuration::get('thnxBLOCK_CATEG_ROOT_CATEGORY') == 3 && !$category->is_root_category && !$category->getSubCategories($category->id, true))
	    		$category = new Category($category->id_parent, $this->context->language->id);
	    }
	    else{
	    	$category = new Category((int)Configuration::get('PS_HOME_CATEGORY'), $this->context->language->id);
	    }
    	$range = '';
    	$maxdepth = Configuration::get('thnxBLOCK_CATEG_MAX_DEPTH');
    	if (Validate::isLoadedObject($category))
    	{
    		if ($maxdepth > 0)
    			$maxdepth += $category->level_depth;
    		$range = 'AND nleft >= '.(int)$category->nleft.' AND nright <= '.(int)$category->nright;
    	}
    	$resultIds = array();
    	$resultParents = array();
    	$result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('
    	SELECT c.id_parent, c.id_category, cl.name, cl.description, cl.link_rewrite
    	FROM `'._DB_PREFIX_.'category` c
    	INNER JOIN `'._DB_PREFIX_.'category_lang` cl ON (c.`id_category` = cl.`id_category` AND cl.`id_lang` = '.(int)$this->context->language->id.Shop::addSqlRestrictionOnLang('cl').')
    	INNER JOIN `'._DB_PREFIX_.'category_shop` cs ON (cs.`id_category` = c.`id_category` AND cs.`id_shop` = '.(int)$this->context->shop->id.')
    	WHERE (c.`active` = 1 OR c.`id_category` = '.(int)Configuration::get('PS_HOME_CATEGORY').')
    	AND c.`id_category` != '.(int)Configuration::get('PS_ROOT_CATEGORY').'
    	'.((int)$maxdepth != 0 ? ' AND `level_depth` <= '.(int)$maxdepth : '').'
    	'.$range.'
    	AND c.id_category IN (
    		SELECT id_category
    		FROM `'._DB_PREFIX_.'category_group`
    		WHERE `id_group` IN ('.pSQL(implode(', ', Customer::getGroupsStatic((int)$this->context->customer->id))).')
    	)
    	ORDER BY `level_depth` ASC, '.(Configuration::get('thnxBLOCK_CATEG_SORT') ? 'cl.`name`' : 'cs.`position`').' '.(Configuration::get('thnxBLOCK_CATEG_SORT_WAY') ? 'DESC' : 'ASC'));
    	foreach ($result as &$row)
    	{
    		$resultParents[$row['id_parent']][] = &$row;
    		$resultIds[$row['id_category']] = &$row;
    	}
    	$blockCategTree = $this->getTree($resultParents, $resultIds, $maxdepth, ($category ? $category->id : null));
    	$return_arr['blockCategTree'] = $blockCategTree;
    	if ((Tools::getValue('id_product') || Tools::getValue('id_category')) && isset($this->context->cookie->last_visited_category) && $this->context->cookie->last_visited_category)
    	{
    		$category = new Category($this->context->cookie->last_visited_category, $this->context->language->id);
    		if (Validate::isLoadedObject($category)){
    			$return_arr['currentCategory'] = $category;
    			$return_arr['currentCategoryId'] = $category->id;
    		}
    	}
    	$return_arr['isDhtml'] = Configuration::get('thnxBLOCK_CATEG_DHTML');
    	if (file_exists(_PS_THEME_DIR_.'modules/thnxblockcategories/thnxblockcategories.tpl')){
    		$return_arr['branche_tpl_path'] = 'module:'.$this->name.'/category-tree-branch.tpl';
    	}else{
    		$return_arr['branche_tpl_path'] = 'module:'.$this->name.'/category-tree-branch.tpl';
    	}
    	$return_arr['hookName'] = $hookName;
    	return $return_arr;
	}
	public function setLastVisitedCategory()
	{
			if (method_exists($this->context->controller, 'getCategory') && ($category = $this->context->controller->getCategory()))
				$this->context->cookie->last_visited_category = $category->id;
			elseif (method_exists($this->context->controller, 'getProduct') && ($product = $this->context->controller->getProduct()))
				if (!isset($this->context->cookie->last_visited_category)
					|| !Product::idIsOnCategoryId($product->id, array(array('id_category' => $this->context->cookie->last_visited_category)))
					|| !Category::inShopStatic($this->context->cookie->last_visited_category, $this->context->shop))
						$this->context->cookie->last_visited_category = (int)$product->id_category_default;
		return $this->context->cookie->last_visited_category;
	}
    public static function isEmptyFileContet($path = null){
    	if($path == null)
    		return false;
    	if(file_exists($path)){
    		$content = Tools::file_get_contents($path);
    		if(empty($content)){
    			return false;
    		}else{
    			return true;
    		}
    	}else{
    		return false;
    	}
    }
    public function Register_Css()
    {
        if(isset($this->css_files) && !empty($this->css_files)){
        	$theme_name = $this->context->shop->theme_name;
    		$page_name = $this->context->controller->php_self;
    		$root_path = _PS_ROOT_DIR_.'/';
        	foreach($this->css_files as $css_file):
        		if(isset($css_file['key']) && !empty($css_file['key']) && isset($css_file['src']) && !empty($css_file['src'])){
        			$media = (isset($css_file['media']) && !empty($css_file['media'])) ? $css_file['media'] : 'all';
        			$priority = (isset($css_file['priority']) && !empty($css_file['priority'])) ? $css_file['priority'] : 50;
        			$page = (isset($css_file['page']) && !empty($css_file['page'])) ? $css_file['page'] : array('all');
        			if(is_array($page)){
        				$pages = $page;
        			}else{
        				$pages = array($page);
        			}
        			if(in_array($page_name, $pages) || in_array('all', $pages)){
        				if(isset($css_file['load_theme']) && ($css_file['load_theme'] == true)){
        					$theme_file_src = 'themes/'.$theme_name.'/assets/css/'.$css_file['src'];
        					if(self::isEmptyFileContet($root_path.$theme_file_src)){
        						$this->context->controller->registerStylesheet($css_file['key'], $theme_file_src , ['media' => $media, 'priority' => $priority]);
        					}
        				}else{
        					$module_file_src = 'modules/'.$this->name.'/css/'.$css_file['src'];
        					if(self::isEmptyFileContet($root_path.$module_file_src)){
        						$this->context->controller->registerStylesheet($css_file['key'], $module_file_src , ['media' => $media, 'priority' => $priority]);
        					}
        				}
    				}
        		}
        	endforeach;
        }
        return true;
    }
    public function Register_Js()
    {
        if(isset($this->js_files) && !empty($this->js_files)){
	    	$theme_name = $this->context->shop->theme_name;
			$page_name = $this->context->controller->php_self;
			$root_path = _PS_ROOT_DIR_.'/';
        	foreach($this->js_files as $js_file):
        		if(isset($js_file['key']) && !empty($js_file['key']) && isset($js_file['src']) && !empty($js_file['src'])){
        			$position = (isset($js_file['position']) && !empty($js_file['position'])) ? $js_file['position'] : 'bottom';
        			$priority = (isset($js_file['priority']) && !empty($js_file['priority'])) ? $js_file['priority'] : 50;
        			$page = (isset($css_file['page']) && !empty($css_file['page'])) ? $css_file['page'] : array('all');
        			if(is_array($page)){
        				$pages = $page;
        			}else{
        				$pages = array($page);
        			}
        			if(in_array($page_name, $pages) || in_array('all', $pages)){
	        			if(isset($js_file['load_theme']) && ($js_file['load_theme'] == true)){
	        				$theme_file_src = 'themes/'.$theme_name.'/assets/js/'.$js_file['src'];
	        				if(self::isEmptyFileContet($root_path.$theme_file_src)){
	        					$this->context->controller->registerJavascript($js_file['key'], $theme_file_src , ['position' => $position, 'priority' => $priority]);
	        				}
	        			}else{
		        			$module_file_src = 'modules/'.$this->name.'/js/'.$js_file['src'];
	        				if(self::isEmptyFileContet($root_path.$module_file_src)){
		        				$this->context->controller->registerJavascript($js_file['key'], $module_file_src , ['position' => $position, 'priority' => $priority]);
	        				}
	        			}
        			}
        		}
        	endforeach;
        }
        return true;
    }
	public function hookHeader()
	{
		$this->Register_Css();
		$this->Register_Js();
	}
	public function renderForm()
	{
		$fields_form = array(
			'form' => array(
				'legend' => array(
					'title' => $this->l('Settings'),
					'icon' => 'icon-cogs'
				),
				'input' => array(
					array(
						'type' => 'radio',
						'label' => $this->l('Category root'),
						'name' => 'thnxBLOCK_CATEG_ROOT_CATEGORY',
						'hint' => $this->l('Select which category is displayed in the block. The current category is the one the visitor is currently browsing.'),
						'values' => array(
							array(
								'id' => 'home',
								'value' => 0,
								'label' => $this->l('Home category')
							),
							array(
								'id' => 'current',
								'value' => 1,
								'label' => $this->l('Current category')
							),
							array(
								'id' => 'parent',
								'value' => 2,
								'label' => $this->l('Parent category')
							),
							array(
								'id' => 'current_parent',
								'value' => 3,
								'label' => $this->l('Current category, unless it has no subcategories, in which case the parent category of the current category is used')
							),
						)
					),
					array(
						'type' => 'text',
						'label' => $this->l('Maximum depth'),
						'name' => 'thnxBLOCK_CATEG_MAX_DEPTH',
						'desc' => $this->l('Set the maximum depth of category sublevels displayed in this block (0 = infinite).'),
					),
					array(
						'type' => 'switch',
						'label' => $this->l('Dynamic'),
						'name' => 'thnxBLOCK_CATEG_DHTML',
						'desc' => $this->l('Activate dynamic (animated) mode for category sublevels.'),
						'values' => array(
									array(
										'id' => 'active_on',
										'value' => 1,
										'label' => $this->l('Enabled')
									),
									array(
										'id' => 'active_off',
										'value' => 0,
										'label' => $this->l('Disabled')
									)
								),
					),
					array(
						'type' => 'radio',
						'label' => $this->l('Sort'),
						'name' => 'thnxBLOCK_CATEG_SORT',
						'values' => array(
							array(
								'id' => 'name',
								'value' => 1,
								'label' => $this->l('By name')
							),
							array(
								'id' => 'position',
								'value' => 0,
								'label' => $this->l('By position')
							),
						)
					),
					array(
						'type' => 'radio',
						'label' => $this->l('Sort order'),
						'name' => 'thnxBLOCK_CATEG_SORT_WAY',
						'values' => array(
							array(
								'id' => 'name',
								'value' => 1,
								'label' => $this->l('Descending')
							),
							array(
								'id' => 'position',
								'value' => 0,
								'label' => $this->l('Ascending')
							),
						)
					),
				),
				'submit' => array(
					'title' => $this->l('Save'),
				)
			),
		);
		$helper = new HelperForm();
		$helper->show_toolbar = false;
		$helper->table = $this->table;
		$lang = new Language((int)Configuration::get('PS_LANG_DEFAULT'));
		$helper->default_form_language = $lang->id;
		$helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
		$helper->identifier = $this->identifier;
		$helper->submit_action = 'submitthnxblockcategories';
		$helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false).'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
		$helper->token = Tools::getAdminTokenLite('AdminModules');
		$helper->tpl_vars = array(
			'fields_value' => $this->getConfigFieldsValues(),
			'languages' => $this->context->controller->getLanguages(),
			'id_language' => $this->context->language->id
		);
		return $helper->generateForm(array($fields_form));
	}
	public function getConfigFieldsValues()
	{
		return array(
			'thnxBLOCK_CATEG_MAX_DEPTH' => Tools::getValue('thnxBLOCK_CATEG_MAX_DEPTH', Configuration::get('thnxBLOCK_CATEG_MAX_DEPTH')),
			'thnxBLOCK_CATEG_DHTML' => Tools::getValue('thnxBLOCK_CATEG_DHTML', Configuration::get('thnxBLOCK_CATEG_DHTML')),
			'thnxBLOCK_CATEG_SORT_WAY' => Tools::getValue('thnxBLOCK_CATEG_SORT_WAY', Configuration::get('thnxBLOCK_CATEG_SORT_WAY')),
			'thnxBLOCK_CATEG_SORT' => Tools::getValue('thnxBLOCK_CATEG_SORT', Configuration::get('thnxBLOCK_CATEG_SORT')),
			'thnxBLOCK_CATEG_ROOT_CATEGORY' => Tools::getValue('thnxBLOCK_CATEG_ROOT_CATEGORY', Configuration::get('thnxBLOCK_CATEG_ROOT_CATEGORY'))
		);
	}
}