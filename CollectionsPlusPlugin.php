<?php

require 'functions.php';

/**
 * Collections Plus Plugin
 *
 * @author	Daniele Binaghi
 * @author	Dave Widmer <dwidmer@bgsu.edu>
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
		'define_routes',
		'admin_head',
		'public_footer'
	);
	
	/**
	 * @var array  The filters used in this plugin.
	 */
	protected $_filters = array(
		'public_theme_name', 
		'items_browse_per_page'
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
			`tracking_id` varchar(100) NOT NULL,
			PRIMARY KEY (`id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8";

		$db->query($sql);

		$per_page = get_option('per_page_public');

		$records = $db->getTable('Collection')->findAll();
		foreach ($records as $row) {
			$cp = new CollectionsPlus;
			$cp->setArray(array(
				'id' => $row->id,
				'per_page' => $per_page,
				'theme' => '',
				'tracking_id' => ''
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
	 * Intercept the name of the public theme.
	 *
	 * @param  string $name The name of the current theme
	 * @return string       The name of the theme
	 */
	public function filterPublicThemeName($name)
	{
		if ($this->theme_name === null)
		{
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

			$id = $this->getCollectionId();
			if ($id !== null) {
				$cp = get_db()->getTable('CollectionsPlus')->find($id);
				if ($cp !== null)
				{
					$perPage = $cp->per_page;
				}
			}
		}

		if ($perPage < 1) {
			$perPage = null;
		}

		return $perPage;
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
		if (file_exists($file)) {
			include_once $file;
		}
	}
}