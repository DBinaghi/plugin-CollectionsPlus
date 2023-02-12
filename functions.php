<?php
	/**
	 * Get a list of the current search collection filters in use.
	 *
	 * @package Omeka\Function\Search
	 * @uses Omeka_View_Helper_SearchFilters::searchFilters()
	 * @params array $params Params to override the ones read from the request.
	 * @return string
	 */
	function collection_search_filters(array $params = null)
	{
		return get_view()->collectionSearchFilters($params);
	}

	function collection_theme()
	{
		if ($cp = get_advanced_settings_by_collection()) {
			return (empty($cp['theme']) ? __('Default') : $cp['theme']);
		}
	}

	function collection_per_page()
	{
		if ($cp = get_advanced_settings_by_collection()) {
			return (empty($cp['per_page']) ? get_option('per_page_public') : $cp['per_page']);
		}
	}

	function collection_google_analytics_id()
	{
		if ($cp = get_advanced_settings_by_collection()) {
			return (empty($cp['tracking_id']) ? __('N/a') : $cp['tracking_id']);
		}
	}

	function collection_items_sort_field()
	{
		if ($cp = get_advanced_settings_by_collection()) {
			return (empty($cp['items_sort_field']) ? null : $cp['items_sort_field']);
		}
	}

	function collection_items_sort_dir()
	{
		if ($cp = get_advanced_settings_by_collection()) {
			return ($cp['items_sort_dir'] == 'a' ? __('Ascending') : __('Descending'));
		}
	}

	function get_advanced_settings_by_collection($collection = null)
	{
		if (!$collection) {
			$collection = get_current_record('collection');
		}
			
		return get_db()->getTable('CollectionsPlus')->find($collection->id);
	}
?>