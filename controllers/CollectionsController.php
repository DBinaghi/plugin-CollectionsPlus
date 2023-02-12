<?php
	require_once CONTROLLER_DIR . '/CollectionsController.php';

	/**
	 * @author	Daniele Binaghi
	 * @author 	Dave Widmer <dave@davewidmer.net>
	 */
	class CollectionsPlus_CollectionsController extends CollectionsController
	{
		public function advancedAction() 
		{
			$collection = $this->_helper->db->findById();
			$cp = $this->_helper->db->getTable('CollectionsPlus')->find($collection->id);

			if ($this->getRequest()->isPost()) {
				$cp = $this->handleSettingsPost($cp, $this->getRequest()->getPost(), $collection);
			}

			$this->view->collection = $collection;
			$this->view->themes = $this->getThemes();
			$this->view->elements = $this->_getFormElementOptions();

			$this->view->settings = $cp ? $cp->toArray() : $this->getDefaults();
		}

		/**
		 * Gets theme defaults.
		 *
		 * @return array
		 */
		protected function getDefaults()
		{
			return array(
				'slug' => '',
				'per_page' => get_option('per_page_public'),
				'theme' => '',
				'items_sort_field' => 0,
				'items_sort_dir' => ''
			);
		}

		/**
		 * Saves the posted data to the database.
		 *
		 * @param  CollectionsPlus	$cp			The advanced collection to update settings on.
		 * @param  array			$data		The posted data
		 * @param  Collection		$collection The collection object for messages
		 * @return CollectionsPlus				The modified collection object
		 */
		protected function handleSettingsPost($cp, array $data, $collection)
		{
			if ($cp === null) {
				$cp = $this->prepareNew($data);
			} else {
				$cp->setPostData($data);
			}

			if ($cp->save(false)) {
				$message = $this->_getEditSuccessMessage($collection);

				if ($message !== '') {
					$this->_helper->flashMessenger($message, 'success');
				}

				$this->_helper->redirector->gotoRoute(array(
					'controller' => 'collections',
					'action' => 'index'
				), 'default');
			} else {
				$this->_helper->flashMessenger($cp->getErrors());
			}

			return $cp;
		}
		
		protected function _getEditSuccessMessage($collection)
		{
			$collectionTitle = $this->_getElementMetadata($collection, 'Dublin Core', 'Title');
			if ($collectionTitle != '') {
				return __('The advanced settings of the collection "%s" were successfully changed!', $collectionTitle);
			} else {
				return __('The advanced settings of the collection #%s were successfully changed!', strval($collection->id));
			}
		}

		/**
		 * Prepares a new Advanced object.
		 *
		 * @param  array  $data The data to add
		 * @return CollectionsPlus
		 */
		protected function prepareNew(array $data)
		{
			$data['id'] = $this->_request->getParam('id');
			unset($data['submit']);

			$obj = new CollectionsPlus;
			$obj->setArray($data);
			return $obj;
		}

		/**
		 * Gets an array of the available themes
		 *
		 * @return  array
		 */
		protected function getThemes()
		{
			$themes = array('' => __('Current Public Theme'));

			foreach (Theme::getAllThemes() as $name => $theme) {
				$themes[$name] = $theme->title;
			}

			return $themes;
		}

		/**
		 * Get an array to be used in formSelect() containing all elements, 
		 * with the ones already assigned marked.
		 *
		 * @return array
		 */
		private function _getFormElementOptions()
		{
			$db = $this->_helper->db->getDb();
			$sql = "
				SELECT es.name AS element_set_name,
					e.id AS element_id,
					e.name AS element_name
				FROM {$db->ElementSet} es
				JOIN {$db->Element} e ON es.id = e.element_set_id
				WHERE es.record_type IS NULL OR es.record_type = 'Item'
				ORDER BY es.name, e.name";
			$elements = $db->fetchAll($sql);
			$options = array('' => __('Select Below'));
			foreach ($elements as $element) {
				$optGroup = __($element['element_set_name']);
				$value = __($element['element_name']);
				$options[$optGroup][$element['element_set_name'] . ',' . $element['element_name']] = $value;
			}
			return $options;
		}
	}
?>