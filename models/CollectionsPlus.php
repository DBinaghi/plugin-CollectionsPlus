<?php
	/**
	 * The Collections Plus model
	 *
	 * @package Collections Plus
	 * @author	Daniele Binaghi
	 * @author	Dave Widmer <dwidmer@bgsu.edu>
	 */
	class CollectionsPlus extends Omeka_Record_AbstractRecord
	{
		/**
		 * @var string  The name of the theme to display
		 */
		public $theme;

		/**
		 * @var int     The number of records to display per page
		 */
		public $per_page;

		/**
		 * @var string  Google Analytics tracking id
		 */
		public $tracking_id;

		/**
		 * @var string	Browse items sort field
		 */
		public $items_sort_field;

		/**
		 * @var string	Browse items sort direction
		 */
		public $items_sort_dir;

		/**
		 * Template method for defining record validation rules.
		 */
		protected function _validate()
		{
			$errors = array();

			if (!Zend_Validate::is($this->per_page, 'Digits')) {
				$this->addError(__('Items Per Page'), __('Items Per Page must be a number'));
				return;
			}

			if ($this->per_page === '') {
				$this->addError(__('Items Per Page'), __('Please enter the number of Items per page to be displayed'));
				return;
			}

			if (!Zend_Validate::is($this->per_page, 'GreaterThan', array('min' => 0))) {
				$this->addError(__('Items Per Page'), __('You must display at least 1 Item per page'));
			}
		}
	}
?>
