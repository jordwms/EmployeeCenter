<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
class Equipment_manager_upd {

	var $version = '1.0';

	function __construct() {
		// Make a local reference to the ExpressionEngine super object
		$this->EE =& get_instance();
	}

	function tabs()
	{
		$tabs['equipment_manager'] = array(
			'equipment_manager_field_ids' => array(
				'visible'     => 'true',
				'collapse'	  => 'false',
				'htmlbuttons' => 'true',
				'width'       => '100%'
			)
		);

		return $tabs;
	}

	/**
	 * Module Installer - Create DB tables
	 * 
	 * @access	public
	 * @return	bool
	 */
	function install() {
		$this->EE->load->dbforge();

		/*******************************************************************************************
		 *	add module to modules table
		 *******************************************************************************************/
		$data = array(
			'module_name'        => 'Equipment_manager', // must be the same name as the mod.*.php class
			'module_version'     => $this->version,
			'has_cp_backend'     => 'y',
			'has_publish_fields' => 'n'
		);

		$this->EE->db->insert('modules', $data); // Insert data array into the 'modules' table


		/********************************************************************************************
		 *	Register Actions
		 *******************************************************************************************/
		$data = array(
			'class'     => 'Equipment_manager_mcp' ,
			'method'    => 'update_home_location'
		);

		//$this->EE->db->insert('actions', $data);


		/*******************************************************************************************
		 *	create em_audit_templates table
		 *******************************************************************************************/
	/*	$this->EE->dbforge->add_field(array(
			'id'                              => array('type' => 'int',     'constraint' => '10', 'unsigned' => TRUE, 'auto_increment' => TRUE),
			'name'                            => array('type' => 'varchar', 'constraint' => '255'),
			'date_created'                    => array('type' => 'datetime'),
			'member_id'                       => array('type' => 'int',     'unsigned' => TRUE),
			'json_template'                   => array('type' => 'text')
			)
		);
		$this->EE->dbforge->add_key('id', TRUE);

		$this->EE->dbforge->create_table('em_form_templates');


		/*******************************************************************************************
		 *	create em_equipment table
		 *******************************************************************************************/
		$this->EE->dbforge->add_field(array(
			'id'                              => array('type' => 'int',     'constraint' => '10',  'unsigned' => TRUE, 'auto_increment' => TRUE),
			'parent_id'                       => array('type' => 'int',     'constraint' => '10',  'unsigned' => TRUE, 'null' => TRUE),
			'type_id'                         => array('type' => 'int',     'constraint' => '10',  'unsigned' => TRUE, 'null' => TRUE),
			'serialnum'                       => array('type' => 'varchar', 'constraint' => '100',                     'null' => TRUE),
			'status_id'                       => array('type' => 'int',     'constraint' => '10',  'unsigned' => TRUE),
			'description'                     => array('type' => 'text',                                               'null' => TRUE),
			'assigned_member_id'              => array('type' => 'int',     'constraint' => '10',  'unsigned' => TRUE, 'null' => TRUE),
			'current_member_id'               => array('type' => 'int',     'constraint' => '10',  'unsigned' => TRUE, 'null' => TRUE),
			'assigned_facility_id'            => array('type' => 'int',     'constraint' => '10',  'unsigned' => TRUE, 'null' => TRUE),
			'current_facility_id'             => array('type' => 'int',     'constraint' => '10',  'unsigned' => TRUE, 'null' => TRUE),
			'next_maintenance_activity_value' => array('type' => 'double'),
			'next_maintenance_time'	          => array('type' => 'int',     'constraint' => '10'),
			'activity_value'                  => array('type' => 'double'),
			'activity_time'                   => array('type' => 'int',     'constraint' => '10'),
			)
		);

		$this->EE->dbforge->add_key('id', TRUE);
		$this->EE->dbforge->add_key('parent_id');
		$this->EE->dbforge->add_key('type_id');
		$this->EE->dbforge->add_key('assigned_member_id');
		$this->EE->dbforge->add_key('current_member_id');
		$this->EE->dbforge->add_key('assigned_facility_id');
		$this->EE->dbforge->add_key('current_facility_id');

		$this->EE->dbforge->create_table('em_equipment');


		/*******************************************************************************************
		 *	create em_equipment_types table
		 *******************************************************************************************/
		$this->EE->dbforge->add_field(array(
			'id'                              => array('type' => 'int',     'constraint' => '10', 'unsigned' => TRUE, 'auto_increment' => TRUE),
			'type'                            => array('type' => 'varchar', 'constraint' => '50'),
			'model'                           => array('type' => 'varchar', 'constraint' => '50'),
			'manufacturer'                    => array('type' => 'varchar', 'constraint' => '50'),
			'description'                     => array('type' => 'text'),
			'calibration_required_interval'   => array('type' => 'int',     'constraint' => '10'),
			'calibration_form_default'        => array('type' => 'int',     'constraint' => '10', 'unsigned' => TRUE),
			'audit_required_interval'         => array('type' => 'int',     'constraint' => '10'),
			'audit_form_default'              => array('type' => 'int',     'constraint' => '10', 'unsigned' => TRUE),
			'maintenance_required_interval'   => array('type' => 'int',     'constraint' => '10'),
			'maintenance_form_default'        => array('type' => 'int',     'constraint' => '10', 'unsigned' => TRUE),
			'activity_rate'                   => array('type' => 'float'),
			'activity_units'                  => array('type' => 'varchar', 'constraint' => '20')
			)
		);

		$this->EE->dbforge->add_key('id', TRUE);
		$this->EE->dbforge->add_key('calibration_form_default');
		$this->EE->dbforge->add_key('audit_form_default');
		$this->EE->dbforge->add_key('maintenance_form_default');

		$this->EE->dbforge->create_table('em_equipment_types');


		/*******************************************************************************************
		 *	create em_facilites table
		 *******************************************************************************************/
		$this->EE->dbforge->add_field(array(
			'id'                              => array('type' => 'int',     'constraint' => '10', 'unsigned' => TRUE, 'auto_increment' => TRUE),
			'facility_name'                   => array('type' => 'varchar', 'constraint' => '50'),
			'location'                        => array('type' => 'text')
			)
		);

		$this->EE->dbforge->add_key('id', TRUE);

		$this->EE->dbforge->create_table('em_facilities');


		/*******************************************************************************************
		 *	create em_log table
		 *******************************************************************************************/
		$this->EE->dbforge->add_field(array(
			'id'                              => array('type' => 'int',     'constraint' => '10', 'unsigned' => TRUE, 'auto_increment' => TRUE),
			'member_id'                       => array('type' => 'int',     'constraint' => '10', 'unsigned' => TRUE),
			'equipment_id'                    => array('type' => 'int',     'constraint' => '10', 'unsigned' => TRUE),
			'action_id'                       => array('type' => 'int',     'constraint' => '10', 'unsigned' => TRUE),
			'action_timestamp'                => array('type' => 'int',     'constraint' => '10', 'unsigned' => TRUE),
			'notes'                           => array('type' => 'text')
			)
		);

		$this->EE->dbforge->add_key('id', TRUE);
		$this->EE->dbforge->add_key('member_id');
		$this->EE->dbforge->add_key('equipment_id');
		$this->EE->dbforge->add_key('action_id');

		$this->EE->dbforge->create_table('em_log');


		/*******************************************************************************************
		 *	create em_log_actions table
		 *******************************************************************************************/
		$fields = array(
			'id'                              => array('type' => 'int',     'constraint' => '10', 'unsigned' => TRUE, 'auto_increment' => TRUE),
			'action'                          => array('type' => 'varchar', 'constraint' => '128')
		);

		$this->EE->dbforge->add_field($fields);
		$this->EE->dbforge->add_key('id', TRUE);

		$this->EE->dbforge->create_table('em_log_actions');

		$this->EE->db->insert('em_log_actions', array('id' => 1, 'action' => 'check_in') );
		$this->EE->db->insert('em_log_actions', array('id' => 2, 'action' => 'check_out') );
		$this->EE->db->insert('em_log_actions', array('id' => 3, 'action' => 'flag_dnu') );
		$this->EE->db->insert('em_log_actions', array('id' => 4, 'action' => 'ordered_new') );
		$this->EE->db->insert('em_log_actions', array('id' => 5, 'action' => 'xfer_vault') );
		$this->EE->db->insert('em_log_actions', array('id' => 6, 'action' => 'recieved') );
		$this->EE->db->insert('em_log_actions', array('id' => 7, 'action' => 'shipped_out') );
		$this->EE->db->insert('em_log_actions', array('id' => 8, 'action' => 'swap_assigned_user') );
		$this->EE->db->insert('em_log_actions', array('id' => 9, 'action' => 'swap_current_user') );
		$this->EE->db->insert('em_log_actions', array('id' => 10, 'action' => 'change_eq_parent') );
		$this->EE->db->insert('em_log_actions', array('id' => 11, 'action' => 'change_eq_type') );
		$this->EE->db->insert('em_log_actions', array('id' => 12, 'action' => 'change_item_descr') );
		$this->EE->db->insert('em_log_actions', array('id' => 13, 'action' => 'change_serial_number') );
		$this->EE->db->insert('em_log_actions', array('id' => 14, 'action' => 'camera_audit') );
		$this->EE->db->insert('em_log_actions', array('id' => 15, 'action' => 'truck_maintenance') );
		$this->EE->db->insert('em_log_actions', array('id' => 16, 'action' => 'gas_monitor_audit') );
		$this->EE->db->insert('em_log_actions', array('id' => 17, 'action' => 'add_eq') );
		$this->EE->db->insert('em_log_actions', array('id' => 18, 'action' => 'item_maintenance') );
		$this->EE->db->insert('em_log_actions', array('id' => 19, 'action' => 'change_action_time') );
		$this->EE->db->insert('em_log_actions', array('id' => 20, 'action' => 'change_action_value') );
		
		/*******************************************************************************************
		 *	create em_status_codes table
		 *******************************************************************************************/
		$fields = array(
			'id'                              => array('type' => 'int',     'constraint' => '10', 'unsigned' => TRUE, 'auto_increment' => TRUE),
			'status'                          => array('type' => 'varchar', 'constraint' => '128')
		);

		$this->EE->dbforge->add_field($fields);
		$this->EE->dbforge->add_key('id', TRUE);

		$this->EE->dbforge->create_table('em_status_codes');

		$this->EE->db->insert('em_status_codes', array('id' => 1, 'status' => 'OK_to_use') );
		$this->EE->db->insert('em_status_codes', array('id' => 2, 'status' => 'user_dnu') );
		$this->EE->db->insert('em_status_codes', array('id' => 3, 'status' => 'rso_dnu') );
		$this->EE->db->insert('em_status_codes', array('id' => 4, 'status' => 'maintenance_dnu') );
		$this->EE->db->insert('em_status_codes', array('id' => 5, 'status' => 'calibration_dnu') );
		$this->EE->db->insert('em_status_codes', array('id' => 6, 'status' => 'in_use') );

		return TRUE;
	}

	function update($current = '1.0') {
		if(version_compare($current, $version, '=')) {
			return FALSE;
		}

		return FALSE;
	}

	/**
	 * Module Uninstaller
	 *
	 * @access	public
	 * @return	bool
	 */
	function uninstall() {
	    $this->EE->load->dbforge();

	    $this->EE->db->select('module_id');
	    $query = $this->EE->db->get_where('modules', array('module_name' => 'Equipment_manager'));

	    $this->EE->db->where('module_id', $query->row('module_id'));
	    $this->EE->db->delete('module_member_groups');

	    $this->EE->db->where('module_name', 'Equipment_manager');
	    $this->EE->db->delete('modules');

	    $this->EE->db->where('class', 'Equipment_manager');
	    $this->EE->db->delete('actions');


		$this->EE->dbforge->drop_table('em_audit_results');
		$this->EE->dbforge->drop_table('em_audit_templates');

	    $this->EE->dbforge->drop_table('em_equipment');
	    $this->EE->dbforge->drop_table('em_equipment_types');
	    $this->EE->dbforge->drop_table('em_facilities');
	    $this->EE->dbforge->drop_table('em_log');
	    $this->EE->dbforge->drop_table('em_log_actions');
	    $this->EE->dbforge->drop_table('em_status_codes');

	    return TRUE;
	}
}
?>
