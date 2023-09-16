<?php
	require 'functions.php';

	/**
	 * Collections Plus Plugin
	 *
	 * @author	Daniele Binaghi <https://github.com/DBinaghi>
	 * @contributor	Dave Widmer <dwidmer@bgsu.edu>
	 * @contributor	Anuragji
	 */
	class CollectionsPlusPlugin extends Omeka_Plugin_AbstractPlugin
	{
		protected $theme_name = null;
		
		/**
		 * @var array  All of the hooks used in this plugin
		 */
		protected $_hooks = array(
			'install', 
			'uninstall',
			'initialize',
			'config',
			'config_form',
			'define_routes',
			'admin_head',
			'public_footer',
			'collections_browse_sql'
		);
		
		/**
		 * @var array  The filters used in this plugin.
		 */
		protected $_filters = array(
			'public_theme_name', 
			'items_browse_per_page',
			'items_browse_params',
			'collections_browse_params'
		);
		
		protected $_options = array(
			'collectionsplus_collections_sort_field'	=> 'added',
			'collectionsplus_collections_sort_dir'		=> 'd',
		);
		
		/**
		 * Installation hook.
		 */
		public function hookInstall()
		{
			$db = get_db();

			$sql = "CREATE TABLE IF NOT EXISTS `{$db->prefix}collections_plus` (
				`id` int(10) unsigned NOT NULL,
				`theme` varchar(100) NOT NULL,
				`per_page` smallint(5) unsigned NOT NULL,
				`tracking_id` varchar(20) NOT NULL,
				`items_sort_field` varchar(100) NOT NULL,
				`items_sort_dir` char(1) NOT NULL,
				PRIMARY KEY (`id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8";

			$db->query($sql);

			$per_page = get_option('per_page_public');

			$records = $db->getTable('Collection')->findAll();
			foreach ($records as $row) {
				$cp = new CollectionsPlus;
				$cp->setArray(array(
					'id' => $row->id,
					'theme' => '',
					'per_page' => $per_page,
					'tracking_id' => '',
					'items_sort_field' => 'added',
					'items_sort_dir' => 'd'
				));

				$cp->save();
			}

			$this->_installOptions();
		}

		/**
		 * Uninstalls any options that have been set.
		 */
		public function hookUninstall()
		{
			$db = get_db();
			$db->query("DROP TABLE IF EXISTS `{$db->prefix}collections_plus`");

			$this->_uninstallOptions();
		}

		public function hookInitialize()
		{
			add_translation_source(dirname(__FILE__) . '/languages');
		}

		public function hookConfigForm()
		{
			// get all elements
			$elementsTable = get_db()->getTable('Element');
			$select = $elementsTable->getSelect()
				->order('elements.element_set_id')
				->order('ISNULL(elements.order)')
				->order('elements.order');
			$elements = $elementsTable->fetchObjects($select);
			$options = array('' => __('Select Below'), 'added' => __('Date Added'));
			foreach ($elements as $element) {
				$optGroup = __($element['set_name']);
				$value = __($element['name']);
				$options[$optGroup][$element['set_name'] . ',' . $element['name']] = $value;
			}
			$formElementOptions = $options;

			include 'config_form.php';
		}

		public function hookConfig($args)
		{
			$post = $args['post'];

			// manually check exluded collections in case $_POST came back empty
			if (!isset($post['defaultsort_excluded_collections'])) {
				$post['defaultsort_excluded_collections'] = array();
			}

			foreach($post as $key=>$value) {

				// serialize our excluded collections
				if ($key == 'defaultsort_excluded_collections') {
					$value = serialize($value);
				}

				set_option($key, $value);
			}
		}

		/**
		 * Add in routes.ini
		 *
		 * @param  array $args  The route arguments
		 */
		public function hookDefineRoutes($args)
		{
			$router = $args['router'];

			$path = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'routes.ini';
			$router->addConfig(new Zend_Config_Ini($path, 'routes'));
		}

		public function hookAdminHead()
		{
			queue_js_file('collections-browse');

			$request = Zend_Controller_Front::getInstance()->getRequest();
			$controller = $request->getControllerName();
			$action = $request->getActionName();
			if ($controller == 'collections') {
				if ($action == 'show') {
					queue_js_string("
						document.addEventListener('DOMContentLoaded', function() {
							var panel = document.getElementById('edit');
							var buttons = panel.children;
							for (i=0; i < buttons.length; i++) {
								if (buttons[i].href.indexOf('/collections/edit/') > 0) {
									var cln = buttons[i].cloneNode(true);
									cln.innerHTML = '" . __('Advanced Settings') . "';
									cln.href = cln.href.replace('collections/edit', 'collections/advanced');
									buttons[i].parentNode.insertBefore(cln, buttons[i].nextSibling);
									break;
								}
							}
						}, false);
					");
				}
			}
		}

		/**
		 * Checks for the Google Analytics Tracking Id and adds it if necessary
		 *
		 * @param array $args  The hook arguments
		 */
		public function hookPublicFooter($args)
		{
			if (is_admin_theme()) {
				return;
			}

			$cp = null;
			$id = $this->getCollectionId();
			
			if (!is_null($id)) {
				$cp = get_db()->getTable('CollectionsPlus')->find($id);
			
				if (!is_null($cp) && !empty($cp->tracking_id))
				{
					echo $args['view']->partial(
						'tracking-code.php',
						array('id' => $cp->tracking_id)
					);
				}
			
				$request = Zend_Controller_Front::getInstance()->getRequest();
				$controller = $request->getControllerName();
				$action = $request->getActionName();
				$id = null;
			}
		}

		/**
		 * Hook for collections browse
		 */	
		public function hookCollectionsBrowseSql($args)
		{
			if (is_admin_theme()) {
				$select = $args['select'];
				$params = $args['params'];
				if (isset($params['empty']) && $params['empty'] == 1) {
					$sql = "`collections`.id IN 
						(SELECT c.id 
						FROM `{$this->_db->Collection}` c LEFT OUTER JOIN `{$this->_db->Item}` i ON c.id = i.collection_id
						WHERE i.id IS NULL)";
					$select->where($sql);
				}
			}
		}

		/**
		 * Intercept the name of the public theme.
		 *
		 * @param  string $name The name of the current theme
		 * @return string       The name of the theme
		 */
		public function filterPublicThemeName($name)
		{
			if ($this->theme_name === null) {
				$id = $this->getCollectionId();
				if ($id !== null) {
					$db = get_db();
					$cp = $db->getTable('CollectionsPlus')->find($id);

					if ($cp !== null && ! empty($cp->theme)) {
						$this->theme_name = $cp->theme;
						$this->loadCommon($this->theme_name);
					}
				}

				if ($this->theme_name === null) {
					$this->theme_name = $name;
				}
			}

			return $this->theme_name;
		}
		
		public function filterItemsBrowsePerPage($perPage, $args)
		{
			if (is_admin_theme()) {
				$perPage = (int) get_option('per_page_admin');
			} else {
				$perPage = (int) get_option('per_page_public');

				$collectionId = $this->getCollectionId();
				if ($collectionId !== null) {
					$cp = get_db()->getTable('CollectionsPlus')->find($collectionId);
					if ($cp !== null) $perPage = $cp->per_page;
				}
			}

			if ($perPage < 1) $perPage = null;

			return $perPage;
		}

		public function filterItemsBrowseParams($params)
		{
			// Only apply to public side
			if (!is_admin_theme()) {
				// Only apply to Items inside a Collection
				$req = Zend_Controller_Front::getInstance()->getRequest();
				$requestParams = $req->getParams();
				
				$sortParam = Omeka_Db_Table::SORT_PARAM;
				$sortDirParam = Omeka_Db_Table::SORT_DIR_PARAM;
				
				// Browse Items
				if (array_key_exists('controller', $params) & array_key_exists('action', $params)) {
					if ($requestParams['controller'] == 'items' && $requestParams['action'] == 'browse') {
						$collectionId = $this->getCollectionId();
						// Only apply the custom sort if available and no other sort has been defined
						if ($collectionId !== null && !isset($_GET['sort_field'])) {
							$cp = get_db()->getTable('CollectionsPlus')->find($collectionId);
							if ($cp !== null) {
								$params['sort_field'] = $cp->items_sort_field;
								$params['sort_dir'] = $cp->items_sort_dir;

								// Apply the default sort from the plugin
								$req->setParam($sortParam, $cp->items_sort_field);
								$req->setParam($sortDirParam, $cp->items_sort_dir);
							}
						}
					}
				}
			}

			return $params;
		}
		
		public function filterCollectionsBrowseParams($params)
		{
			// Only apply to public side
			if (!is_admin_theme()) {
				$req = Zend_Controller_Front::getInstance()->getRequest();
				$requestParams = $req->getParams();

				$sortParam = Omeka_Db_Table::SORT_PARAM;
				$sortDirParam = Omeka_Db_Table::SORT_DIR_PARAM;

				// Browse Collections
				if (array_key_exists('controller', $params) & array_key_exists('action', $params)) {
					if ($requestParams['controller'] == 'collections' && $requestParams['action'] == 'browse') {
						// Only apply the Default Sort if no other sort has been defined
						if (!isset($_GET['sort_field'])) {
							$params['sort_field'] = get_option('collectionsplus_collections_sort_field');
							$params['sort_dir'] = get_option('collectionsplus_collections_sort_dir');

							// Apply the default sort from the plugin
							$req->setParam($sortParam, $params['sort_field']);
							$req->setParam($sortDirParam, $params['sort_dir']);
						}
					}
				}
			}

			return $params;
		}

		/**
		 * The id number of the current collection.
		 *
		 * @return int|null   The collection id number or null if this isn't a collection page.
		 */
		protected function getCollectionId()
		{
			$request = Zend_Controller_Front::getInstance()->getRequest();
			$controller = $request->getControllerName();
			$action = $request->getActionName();
			$id = null;

			if ($controller === 'collections' && $action === 'show') {
				$id = $request->getParam('id');
			} else if ($controller === 'items') {
				if (in_array($action, array('browse', 'tags', 'search')) === true) {
					$id = $request->getParam('collection');
				} else if ($action === 'show') {
					$id = $this->getCollectionIdFromItem($request->getParam('id'));
				}
			}

			return $id;
		}

		/**
		 * Find the id of the collection that the given item is apart of.
		 *
		 * @param  int $id   The item id
		 * @return int|null  The collection id or null
		 */
		protected function getCollectionIdFromItem($id)
		{
			$db = get_db();
			$item = $db->getTable('Item')->find($id);

			return ($item === null) ? null : $item->collection_id;
		}

		/**
		 * Checks for a common.php file and load it up if it exists.
		 *
		 * @param  string $theme The theme name
		 */
		private function loadCommon($theme)
		{
			$file = PUBLIC_THEME_DIR . '/' . $theme . '/common.php';
			if (file_exists($file)) include_once $file;
		}
	}
?>
