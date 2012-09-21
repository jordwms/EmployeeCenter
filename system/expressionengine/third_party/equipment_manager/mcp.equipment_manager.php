<? if (!defined('BASEPATH')) exit('No direct script access allowed');

require_once('api.equipment_manager.php');

class Equipment_manager_mcp {

	var $vars = array();
	var $api;
	var $mod_uri_base;
	var $theme_folder_url;

	function __construct() {
		$this->EE =& get_instance();
		$this->EE->load->library('javascript');
		$this->api = new Equipment_manager_api();

		$this->mod_uri_base = 'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=equipment_manager';

		// Set page title
		$this->EE->cp->set_variable('cp_page_title', $this->EE->lang->line('Equipment Manager'));

		$this->EE->cp->set_right_nav(array(
			'equipment_list'  => BASE.AMP.$this->mod_uri_base.AMP.'method=equipment_list',		
			'equipment_types' => BASE.AMP.$this->mod_uri_base.AMP.'method=equipment_types',		
			'facility_list'   => BASE.AMP.$this->mod_uri_base.AMP.'method=facility_list',
			'log_actions'     => BASE.AMP.$this->mod_uri_base.AMP.'method=show_log'
		));

		// Load CSS
		$theme_folder_url = $this->EE->config->item('theme_folder_url');
		if(substr($theme_folder_url, -1) != '/' ){ $theme_folder_url .= '/'; }
		$theme_folder_url .= "third_party/equipment_manager/";
		
		$css = 'css/mcp.css';
		$this->EE->cp->add_to_head("<link rel='stylesheet' type='text/css' href='".$theme_folder_url.$css."' />"); 
		// $this->EE->cp->load_package_css($css); # Do not know where to put css file.
	}

	function index() {
		return $this->equipment_list();
	}

	/**
	* Corresponds to the equipment_list.php file in the local 'views' folder
	*
	* @return array sent to 'equipment_list' view. Array indices's are variables in the view file.
	*/
	function equipment_list() {
		$this->EE->load->library('javascript');
		$this->EE->load->library('table');
		$this->EE->load->helper('form');

		// Set page title
		$this->EE->cp->set_variable('cp_page_title', $this->EE->lang->line('equipment_manager_module_name'));

		// Set view files
		$vars['views'] = array('equipment_list', 'javascript/ajax');

		$vars['status_code']['action_url']			= $this->mod_uri_base.AMP.'method=set_status';
		$vars['status_code']['attributes']			= array('class' => 'status_code_select');

		$vars['assigned_member']['action_url']		= $this->mod_uri_base.AMP.'method=set_assigned_member';
		$vars['assigned_member']['attributes']		= array('class' => 'assigned_member_select');

		$vars['storage_facility']['action_url']		= $this->mod_uri_base.AMP.'method=set_storage_facility';
		$vars['storage_facility']['attributes']		= array('class' => 'storage_facility_select');


		$vars['options'] = array(
			'edit'		=> lang('edit_selected'),
			'delete'	=> lang('delete_selected')
			);

		// Set URIs
		$vars['edit_link'] 		= BASE.AMP.$this->mod_uri_base.AMP.'method=edit_item'.AMP.'id=';
		$vars['show_eq_type'] 	= BASE.AMP.$this->mod_uri_base.AMP.'method=equipment_types';
		$vars['add_item_link'] 	= BASE.AMP.$this->mod_uri_base.AMP.'method=new_equipment';

		// Get all equipment, storage facilities, equipment types and members
		$vars['location']       = $this->api->facility->details();
		$vars['eq_type']        = $this->api->eq->types();
		$vars['members']        = $this->api->member_list();
		$vars['items']		    = $this->api->eq->details();

		// round current_activity to 2 decimals
		foreach($vars['items'] as &$entry) {
			$entry['current_activity'] = round($entry['current_activity'], 2);
		}

		return $this->EE->load->view('equipment_list', $vars, TRUE);
	}

	/*
	 *	Given a serial number, this method displays editable details about a given piece of equipment.
	 *	Details include: 
	 *	1. Editable details textbox
	 *	2. Storage Facility dropdown 
	 *	3. Assign to user dropdown
	 *	4. Link to Maintenance page 
	 *	The func checks to see if data came from the page.
	 *	When a form is submitted, the function checks for alterations to the dataset and 
	 *	alters the entry accordingly.
	 */
	function edit_item() {
		// if (! $this->EE->cp->allowed_group('can_access_content')  OR ! $this->EE->cp->allowed_group('can_access_files')) {
		// 	show_error($this->EE->lang->line('unauthorized_access'));
		// }

		if( is_numeric($this->EE->input->get('id')) ) {
			$eq_id = $this->EE->input->get('id');
		} else {
			show_error('invalid id given');
		}

		$this->EE->load->model('member_model');
		$this->EE->load->helper(array('form', 'date'));
		$this->EE->load->library('table');

		$this->EE->cp->set_variable('cp_page_title', $this->EE->lang->line('equipment_manager_edit_item'));
		$this->EE->cp->set_breadcrumb(BASE.AMP.$this->mod_uri_base, $this->EE->lang->line('equipment_manager_module_name'));

		$vars['views'] = array('edit_item', 'javascript/ajax');

		// Set form helper values
		$vars['serial_form_action_url'] 		= $this->mod_uri_base.AMP.'method=set_serialnum';
		$vars['serial_form_hidden'] 			= array('eq_id' => $eq_id);
		$vars['serial_form_attributes'] 		= array('class' => 'serial form');

		$vars['description_action_url'] 		= $this->mod_uri_base.AMP.'method=set_description';
		$vars['description_form_hidden'] 		= array('eq_id' => $eq_id);
		$vars['description_form_attributes'] 	= array('class' => 'description form');

		$vars['current_user_action_url'] 		= $this->mod_uri_base.AMP.'method=set_current_member';
		$vars['current_user_form_hidden'] 		= array('eq_id' => $eq_id);
		$vars['current_user_form_attributes'] 	= array('class' => 'current_user form');

		$vars['assigned_user_action_url'] 		= $this->mod_uri_base.AMP.'method=set_assigned_member';
		$vars['assigned_user_form_hidden'] 		= array('eq_id' => $eq_id);
		$vars['assigned_user_form_attributes'] 	= array('class' => 'assigned_user form');

		$vars['location_action_url'] 			= $this->mod_uri_base.AMP.'method=set_storage_facility';
		$vars['location_form_hidden'] 			= array('eq_id' => $eq_id);
		$vars['location_form_attributes'] 		= array('class' => 'location form');

		$vars['parent_eq_action_url'] 			= $this->mod_uri_base.AMP.'method=set_parent';
		$vars['parent_eq_form_hidden']		 	= array('eq_id' => $eq_id);
		$vars['parent_eq_form_attributes']		= array('class' => 'parent_eq form');

		$vars['child_eq_action_url'] 			= $this->mod_uri_base.AMP.'method=edit_item';
		$vars['child_eq_form_hidden'] 			= array('eq_id' => $eq_id);
		$vars['child_eq_form_attributes'] 		= array('class' => 'child_eq form');

		$vars['type_eq_action_url'] 			= $this->mod_uri_base.AMP.'method=set_type';
		$vars['type_eq_form_hidden'] 			= array('eq_id' => $eq_id);
		$vars['type_eq_form_attributes'] 		= array('class' => 'type_eq form');

		$vars['dnu_form_action_url'] 			= $this->mod_uri_base.AMP.'method=set_status';
		$vars['dnu_form_form_hidden'] 			= array('eq_id' => $eq_id);
		$vars['dnu_form_form_attributes'] 		= array('class' => 'dnu_form');

		$vars['activity_value_action_url'] 		= $this->mod_uri_base.AMP.'method=set_activity_value';
		$vars['activity_value_form_hidden'] 	= array('eq_id' => $eq_id);
		$vars['activity_value_form_attributes'] = array();

		$vars['activity_time_action_url'] 		= $this->mod_uri_base.AMP.'method=set_activity_time';
		$vars['activity_time_form_hidden'] 		= array('eq_id' => $eq_id);
		$vars['activity_time_form_attributes'] 	= array();

		$vars['edit_link'] 		  				= BASE.AMP.$this->mod_uri_base.AMP.'method=edit_item'.AMP.'id=';
		$vars['audit_link'] 	  				= BASE.AMP.$this->mod_uri_base.AMP.'method=audit';
		$vars['maintenance_link'] 				= BASE.AMP.$this->mod_uri_base.AMP.'method=maintenance';
		$vars['log_link']		  				= BASE.AMP.$this->mod_uri_base.AMP.'method=show_log'.AMP.'eq_id='.$eq_id;

		$vars['items']		      				= $this->api->eq->details($eq_id);
		$vars['location']         				= $this->api->facility->details();
		$vars['eq_types']          				= $this->api->eq->types();
		$vars['children']         				= $this->api->eq->children($eq_id);
		$vars['parents']          				= $this->api->eq->details(); // the list of possible parent eq #TODO: Filter possible parents by eq. types (sources cannot be parents to cameras, etc.)
		$vars['members']          				= $this->api->member_list();

		return $this->EE->load->view('view_loader', $vars, TRUE);
	}

	/**
	* Adds a piece of equipment to the em_equipment table
	*/
	function new_equipment() {
		// if (! $this->EE->cp->allowed_group('can_access_content')  OR ! $this->EE->cp->allowed_group('can_access_files')) {
		// 	show_error($this->EE->lang->line('unauthorized_access'));
		// }



		$this->EE->cp->set_variable('cp_page_title', $this->EE->lang->line('equipment_manager_add_eq'));
		$this->EE->cp->set_breadcrumb(BASE.AMP.$this->mod_uri_base, $this->EE->lang->line('equipment_manager_module_name'));
		$this->EE->load->helper('form');

		$vars['action_url']		= $this->mod_uri_base.AMP.'method=add_equipment';
		$vars['form_hidden']	= FALSE;
		$vars['attributes'] 	= array( 'id' => 'new_equipment' );

		$vars['eq_types']		= $this->api->type->details();
		$vars['facilities'] 	= $this->api->facility->details();
		$vars['members']		= $this->api->member_list();
		$vars['parents']		= $this->api->eq->details(); // Temporary... Should be showing available parents.

		return $this->EE->load->view('new_equipment', $vars, TRUE);
	}

	function equipment_types(){
		// if (! $this->EE->cp->allowed_group('can_access_content')  OR ! $this->EE->cp->allowed_group('can_access_files')) {
		// 	show_error($this->EE->lang->line('unauthorized_access'));
		// }

		$this->EE->load->library('javascript');
		$this->EE->load->library('table');
		$this->EE->load->helper('form');

		$this->EE->cp->set_variable('cp_page_title', $this->EE->lang->line('equipment_manager_eq_type'));
		$this->EE->cp->set_breadcrumb(BASE.AMP.$this->mod_uri_base, $this->EE->lang->line('equipment_manager_module_name'));

		$vars['action_url'] = $this->mod_uri_base.AMP.'method=add_equipment_type';
		$vars['form_hidden'] = FALSE;
		$vars['form_attributes'] = array(
			'id' 		=> 'add_eq_type',
			'hidden'  	=> 'hidden'
			);
		$vars['options'] = array(
			'edit'		=> lang('edit_selected'),
			'delete'	=> lang('delete_selected')
			);
		$vars['items'] = $this->api->type->details();

		// foreach($this->api->type->details() as $entry) {
		// 	 // if a type is "source" then decay rate should be in days
  //       	if(strstr($entry['description'], 'source')) {
  //           	$entry['maintenance_decay_rate'].= ' days';
  //      		}
		// 	array_push($vars['items'], $entry);
		// }

		return $this->EE->load->view('equipment_types', $vars, TRUE);
	}


	// Shows the entire log database (needs pagination)
	function show_log() {
		// if (! $this->EE->cp->allowed_group('can_access_content')  OR ! $this->EE->cp->allowed_group('can_access_files')) {
		// 	show_error($this->EE->lang->line('unauthorized_access'));
		// }

		if ( is_numeric($this->EE->input->get('eq_id')) ) {
			$eq_id = $this->EE->input->get('eq_id', TRUE);
		} else {
			$eq_id = NULL;
		}

		$this->EE->load->library('javascript');
		$this->EE->load->library('table');
		$this->EE->load->helper('form');

		$this->EE->cp->set_variable('cp_page_title', $this->EE->lang->line('equipment_manager_log_book'));
		$this->EE->cp->set_breadcrumb(BASE.AMP.$this->mod_uri_base, $this->EE->lang->line('equipment_manager_module_name'));

		$vars['action_url'] = $this->mod_uri_base.AMP.'method=index';
		$vars['form_hidden'] = FALSE;
		$vars['items'] = array();
		$vars['options'] = array(
			'edit'		=> lang('edit_selected'),
			'delete'	=> lang('delete_selected')
			);

		foreach ($this->api->log->entries($eq_id) as $entry) {
			$entry['action_timestamp'] = date('m-d-Y',$entry['action_timestamp']);
			array_push($vars['items'], $entry);
		}

		return $this->EE->load->view('show_log', $vars, TRUE);
	}

	/*
	*
	*/
	function facility_list() {
		// if (! $this->EE->cp->allowed_group('can_access_content')  OR ! $this->EE->cp->allowed_group('can_access_files')) {
		// 	show_error($this->EE->lang->line('unauthorized_access'));
		// }
		$this->EE->load->library('javascript');
		$this->EE->load->library('table');
		$this->EE->load->helper('form');

		$this->EE->cp->set_variable('cp_page_title', $this->EE->lang->line('equipment_manager_facility_list'));
		$this->EE->cp->set_breadcrumb($this->mod_uri_base, $this->EE->lang->line('equipment_manager_module_name'));

		$vars['action_url'] = $this->mod_uri_base.AMP.'method=add_facility';
		$vars['form_hidden'] = FALSE;
		$vars['attributes'] = array(
			'id' 	=> 'add_facility',
			'hidden'=> 'hidden'
			);

		$vars['items'] = $this->api->facility->details();
		$vars['options'] = array(
			'edit'		=> lang('edit_selected'),
			'delete'	=> lang('delete_selected')
			);

		return $this->EE->load->view('facility_list', $vars, TRUE);
	}

	/*
	* 
	*/
	function process_audit_form() {
		// if (! $this->EE->cp->allowed_group('can_access_content')  OR ! $this->EE->cp->allowed_group('can_access_files')) {
		// 	show_error($this->EE->lang->line('unauthorized_access'));
		// }

		if( is_int($this->EE->input->get('eq_id')) ) {
			$eq_id = $this->EE->input->GET('eq_id', TRUE);
		} else {
			show_error('invalid id given');
		}

		$json_values = json_encode($_POST);
		$pasing = 0;

		$this->api->log_audit($eq_id, $pasing, $json_values);//option 2 is form passing
		
		return $this->equipment_list();
	}

	/*
	*
	*/
	function audit() {
		// if (! $this->EE->cp->allowed_group('can_access_content')  OR ! $this->EE->cp->allowed_group('can_access_files')) {
		// 	show_error($this->EE->lang->line('unauthorized_access'));
		// }

		$this->EE->load->helper('form');
		$this->EE->load->library('table');
		$this->EE->cp->set_variable('cp_page_title', $this->EE->lang->line('equipment_manager_audit'));
		$this->EE->cp->set_breadcrumb(BASE.AMP.$this->mod_uri_base, $this->EE->lang->line('equipment_manager_module_name'));
		
		//get the default from assigned to the equipment type
		$default_form = $this->EE->input->GET('json_template', TRUE);

		$vars = array();
		$vars['id'] = $this->EE->input->GET('id', TRUE);
		$vars['action_url'] = $this->mod_uri_base.AMP.'method=process_audit_form'.AMP.'eq_id='.$vars['id'].AMP.'json_template='.$default_form;
		$vars['form_hidden'] = FALSE;
		$vars['attributes'] = array(
			'id' => 'audit_form'
			);
		// Gather all json templates from audit_templates table.
		$this->EE->db->select('id, name');
		$this->EE->db->from('em_audit_templates');
		$vars['forms_list'] = $this->EE->db->get();
		$vars['forms_list'] = $vars['forms_list']->result_array();

		// Retrieves the default json_template for a given eq type
		$this->EE->db->select('json_template');
		$this->EE->db->from('em_audit_templates');
		$this->EE->db->where('id',$default_form);
		$json_form_template = $this->EE->db->get();

		// echo '<pre>'; print_r($json_form_template->row('json_template')); die;
		$vars['default_form'] = json_decode($json_form_template->row('json_template'), TRUE);
		// echo '<pre>'; print_r($vars['default_form']); die;

		return $this->EE->load->view('audit', $vars, TRUE);


		return $this->EE->load->view('audit', $vars, TRUE);
	}

	/************************************************************************************
	 *	ACTIONS
	 *	Aliased from api
	 ************************************************************************************/
	function add_facility()			{ return $this->api->facility->add(); }
	function add_equipment_type()   { return $this->api->type->add(); }
	function add_equipment()        { return $this->api->eq->add(); }

	function set_storage_facility() { return $this->api->eq->set_storage_facility(); }
	function set_status()           { return $this->api->eq->set_status(); }
	function set_description()      { return $this->api->eq->set_description(); }
	function set_serialnum()        { return $this->api->eq->set_serialnum(); }
	function set_type()             { return $this->api->eq->set_type(); }
	function set_parent()           { return $this->api->eq->set_parent(); }
	function set_current_member()   { return $this->api->eq->set_current_member(); }
	function set_assigned_member()  { return $this->api->eq->set_assigned_member(); }
	function set_activity_value()  	{ return $this->api->eq->set_activity_value(); }
	function set_activity_time()  	{ return $this->api->eq->set_activity_time(); }



	/*
	* For testing/debugging
	*/
	function test() {}
}// END CLASS

/* End of file mcp.Equipment_manager.php */
/* Location: ./system/expressionengine/third_party/modules/equipment_manager/mcp.equipment_manager.php */