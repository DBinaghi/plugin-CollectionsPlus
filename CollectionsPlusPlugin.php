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
		 * Cached collection id for the current request.
		 *
		 * @var int|null|false  false = not yet resolved; null = not a collection page; int = resolved id
		 */
		protected $_cachedCollectionId = false;
		
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

			foreach (array_keys($this->_options) as $key) {
				if (isset($post[$key])) {
					set_option($key, $post[$key]);
				}
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

		/**
		 * Enqueue JS and inline scripts only on relevant admin pages.
		 */
		public function hookAdminHead()
		{
			$request = Zend_Controller_Front::getInstance()->getRequest();
			$controller = $request->getControllerName();
			$action = $request->getActionName();

			if ($controller === 'collections') {
				// JS for the browse view (details toggle, quick filter)
				if ($action === 'index') {
					queue_js_file('collections-browse');
				}

				// Inline JS to inject the "Advanced Settings" button on the show page
				if ($action === 'show') {
					queue_js_string("
						document.addEventListener('DOMContentLoaded', function() {
							var panel = document.getElementById('edit');
							var buttons = panel.children;
							for (var i = 0; i < buttons.length; i++) {
								if (buttons[i].href && buttons[i].href.indexOf('/collections/edit/') > 0) {
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
		 * Checks for the Google Analytics Tracking Id and adds it if necessary.
		 *
		 * @param array $args  The hook arguments
		 */
		public function hookPublicFooter($args)
		{
			if (is_admin_theme()) {
				return;
			}

			$id = $this->getCollectionId();
			if ($id === null) {
				return;
			}

			$cp = get_db()->getTable('CollectionsPlus')->find($id);
			if ($cp !== null && !empty($cp->tracking_id)) {
				echo $args['view']->partial(
					'tracking-code.php',
					array('id' => $cp->tracking_id)
				);
			}
		}

		/**
		 * Hook for collections browse: filter by empty collections on admin side.
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
					$cp = get_db()->getTable('CollectionsPlus')->find($id);

					if ($cp !== null && !empty($cp->theme)) {
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
			if (is_admin_theme()) {
				return $params;
			}

			$req = Zend_Controller_Front::getInstance()->getRequest();
			if (!$req) return $params;

			$requestParams = $req->getParams();
			$controller = $requestParams['controller'];
			$action = $requestParams['action'];

			$isItemsBrowse = ($controller === 'items' && $action === 'browse'
				&& array_key_exists('controller', $params) && array_key_exists('action', $params));
			$isCollectionShow = ($controller === 'collections' && $action === 'show');

			if ($isItemsBrowse || $isCollectionShow) {
				$collectionId = $this->getCollectionId();
				if ($collectionId !== null && !isset($_GET['sort_field'])) {
					$cp = get_db()->getTable('CollectionsPlus')->find($collectionId);
					if ($cp !== null) {
						$params['sort_field'] = $cp->items_sort_field;
						$params['sort_dir'] = $cp->items_sort_dir;

						$req->setParam(Omeka_Db_Table::SORT_PARAM, $cp->items_sort_field);
						$req->setParam(Omeka_Db_Table::SORT_DIR_PARAM, $cp->items_sort_dir);
					}
				}
			}

			return $params;
		}
		
		public function filterCollectionsBrowseParams($params)
		{
			// Only apply to public side
			if (is_admin_theme()) {
				return $params;
			}

			$req = Zend_Controller_Front::getInstance()->getRequest();
			if (!$req) return $params;

			$requestParams = $req->getParams();

			// Browse Collections — note: && instead of & (bitwise AND bug fix)
			if (array_key_exists('controller', $params) && array_key_exists('action', $params)) {
				if ($requestParams['controller'] === 'collections' && $requestParams['action'] === 'browse') {
					if (!isset($_GET['sort_field'])) {
						$sortField = get_option('collectionsplus_collections_sort_field');
						$sortDir   = get_option('collectionsplus_collections_sort_dir');

						$params['sort_field'] = $sortField;
						$params['sort_dir']   = $sortDir;

						$req->setParam(Omeka_Db_Table::SORT_PARAM, $sortField);
						$req->setParam(Omeka_Db_Table::SORT_DIR_PARAM, $sortDir);
					}
				}
			}

			return $params;
		}

		/**
		 * The id number of the current collection.
		 * Result is cached for the duration of the request.
		 *
		 * @return int|null   The collection id number or null if this isn't a collection page.
		 */
		protected function getCollectionId()
		{
			if ($this->_cachedCollectionId !== false) {
				return $this->_cachedCollectionId;
			}

			$request = Zend_Controller_Front::getInstance()->getRequest();
			$controller = $request->getControllerName();
			$action = $request->getActionName();
			$id = null;

			if ($controller === 'collections' && $action === 'show') {
				$id = $request->getParam('id');
			} elseif ($controller === 'items') {
				if (in_array($action, array('browse', 'tags', 'search'))) {
					$id = $request->getParam('collection');
				} elseif ($action === 'show') {
					$id = $this->getCollectionIdFromItem($request->getParam('id'));
				}
			}

			$this->_cachedCollectionId = $id;
			return $id;
		}

		/**
		 * Find the id of the collection that the given item belongs to.
		 *
		 * @param  int $id   The item id
		 * @return int|null  The collection id or null
		 */
		protected function getCollectionIdFromItem($id)
		{
			$item = get_db()->getTable('Item')->find($id);
			return ($item === null) ? null : $item->collection_id;
		}

		/**
		 * Checks for a common.php file and loads it if it exists.
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
