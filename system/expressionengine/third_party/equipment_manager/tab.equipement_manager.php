<?php if(!defined('BASEPATH')) exit('No direct script access allowed');

class Equipment_manager_tab {

	function __construct() {
		$this->EE =& get_instance();
	}

	/*
	* Allows the creation of fields on the publish page. 
	* The field is populated by the existing file records.
	*/
	function publish_tabs($channel_id, $entry_id = '') {
		$settings 		= array();
		$selected 		= array();
		$existing_files = array();

		//$id_instructions = lang('id_field_instructions');

		// Load the module lang file for the field label
		$this->EE->lang->loadfile('equipment_manager');

		$settings[] = array(
			'field_id'			=> 'equipment_manager_field_ids',
			'field_label' 		=> $this->EE->lang->line('equipment_manager_label'),
			'field required'	=> 'n',
			'field_data'		=> $selected,
			'field_list_items'	=> $existing_files,
			'filed_fmt'			=> '',
			'field_instructions'=> $id_instructions,
			'field_show_fmt'	=> 'n',
			'field_pre_populate'=> 'n',
			'field_text_direction'=> 'ltr',
			'field_type'		=> 'multi_select'
			);
		return $settings;
	}

	function validate_publish($params)
	{
		return FALSE;
	}

	// Use this func to add db entries like new locations, equipment, etc.
	function publish_data_db($params)
	{
		
	}
	/**
	* This function is called when entries are deleted, and allows you to synchronize
	* your module tables and make any other adjustments necessary when an entry that may be
	* associated with module data is deleted.
	*/
	function publish_data_delete_db($params) {
		// Remove existing
		
	}

	
}