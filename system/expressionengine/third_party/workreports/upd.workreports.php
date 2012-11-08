<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
class Workreports_upd {
	var $version = '1.3.3';

	function __construct() {
		$this->EE =& get_instance();
	}

	function install() {
		$this->EE->load->dbforge();
		/*******************************************************************************************
		 *	Add module to modules table
		 *******************************************************************************************/
		$data = array(
			'module_name'        => 'Workreports', // must be the same name as the mod.*.php class
			'module_version'     => $this->version,
			'has_cp_backend'     => 'y',
			'has_publish_fields' => 'n'
		);

		$this->EE->db->insert('modules', $data); // Insert data array into the 'modules' table

		/********************************************************************************************
		 *	Register Actions
		 *******************************************************************************************/
		$data = array(
			array(
				'class'     => 'Workreports' ,
				'method'    => 'submit_for_approval'
			), array(
				'class'		=> 'Workreports',
				'method'	=> 'get'
			)
		);

		//$this->EE->db->insert('actions', $data);

		/*******************************************************************************************
		 *	Create wr_reports table
		 *******************************************************************************************/
		$this->EE->dbforge->add_field(array(
			'id'                              	=> array('type' => 'int',     	'constraint' => '10', 'unsigned' => TRUE, 'auto_increment' => TRUE),
			'submitter_id'						=> array('type' => 'varchar',  	'constraint' => '50'),
			'submitter_name'					=> array('type' => 'varchar',  	'constraint' => '50'),
			'crew_leader'						=> array('type' => 'varchar',  	'constraint' => '50'),
			'status'							=> array('type' => 'tinyint'),
			'execution_date'       				=> array('type' => 'int',     	'constraint' => '10'),
			'submission_date'       			=> array('type' => 'int',     	'constraint' => '10'),
			'company_id'		                => array('type' => 'varchar', 	'constraint' => '50'),
			'customer_account'					=> array('type' => 'varchar', 	'constraint' => '50'),
			'customer_name'						=> array('type' => 'varchar', 	'constraint' => '50'),
			'project_order_id'					=> array('type' => 'varchar', 	'constraint' => '50'),
			'project_work_order_id'				=> array('type' => 'varchar', 	'constraint' => '50'),
			'project_work_report_id'			=> array('type' => 'varchar', 	'constraint' => '50'),
			'customer_reference'				=> array('type' => 'varchar', 	'constraint' => '50'),
			'rtd_reference'						=> array('type' => 'varchar', 	'constraint' => '50'),
			'work_location_name'				=> array('type' => 'varchar', 	'constraint' => '50'),
			'contact_person'					=> array('type' => 'varchar', 	'constraint' => '50'),
			'object_description'				=> array('type' => 'text'),
			'order_description'					=> array('type' => 'text'),
			'work_location_id'					=> array('type' => 'varchar',   'constraint' => '50'),
			'work_location_address'				=> array('type' => 'varchar',   'constraint' => '50'),
			'project_id' 						=> array('type' => 'varchar',   'constraint' => '50'),
			'sales_id'	 						=> array('type' => 'varchar',   'constraint' => '50'),
			'sales_name' 						=> array('type' => 'varchar',   'constraint' => '50'), // SalesName
			'invoice_account'					=> array('type' => 'varchar',   'constraint' => '50'), // InvoiceAccount
			'delivery_name'						=> array('type' => 'varchar',   'constraint' => '50'), // DeliveryName
			'delivery_address' 					=> array('type' => 'varchar',   'constraint' => '50'), // DeliveryAddress
			'team_contact_name' 				=> array('type' => 'varchar',   'constraint' => '50'), // TeamContactPerson
			'team_contact_address' 				=> array('type' => 'varchar',   'constraint' => '50'), // TeamContactPersonAddress
			'team_contact_phone' 				=> array('type' => 'varchar',   'constraint' => '50'), // TeamContactPersonPhone
			'team_contact_fax' 					=> array('type' => 'varchar',   'constraint' => '50'), // TeamContactPersonFax
			'team_contact_email' 				=> array('type' => 'varchar',   'constraint' => '50'), // TeamContactPersonEmail
			'customer_address'					=> array('type' => 'varchar',   'constraint' => '50'), // CustomerAddress
			'customer_phone'					=> array('type' => 'varchar',   'constraint' => '50'), // CustomerPhone
			'customer_fax' 						=> array('type' => 'varchar',   'constraint' => '50'), // CustomerFax
			'customer_email' 					=> array('type' => 'varchar',   'constraint' => '50'), // CustomerEmail
			'customer_contact_id' 				=> array('type' => 'varchar',   'constraint' => '50'), // CustomerContactPersonID
			'customer_contact_name' 			=> array('type' => 'varchar',   'constraint' => '50'), // CustomerContactPersonName
			'customer_contact_email' 			=> array('type' => 'varchar',   'constraint' => '50'), // CustomerContactPersonEmail
			'customer_contact_phone' 			=> array('type' => 'varchar',   'constraint' => '50'), // CustomerContactPersonPhone
			'customer_contact_mobile' 			=> array('type' => 'varchar',   'constraint' => '50'), // CustomerContactPersonCellPhone
			'remarks'							=> array('type' => 'text')
		));

		$this->EE->dbforge->add_key('id', TRUE);

		$this->EE->dbforge->create_table('wr_reports');

		/*******************************************************************************************
		 *	Create wr_items table
		 *******************************************************************************************/
		$this->EE->dbforge->add_field(array(
			'id'                              	=> array('type' => 'int',     	'constraint' => '10', 'unsigned' => TRUE, 'auto_increment' => TRUE),
			'report_id'						  	=> array('type' => 'int',     	'constraint' => '10', 'unsigned' => TRUE),
			'qty'		                       	=> array('type' => 'int',     	'constraint' => '10', 'unsigned' => TRUE),
			'date'                				=> array('type' => 'int',     	'constraint' => '10', 'unsigned' => TRUE),
			'item_id'			                => array('type' => 'varchar',	'constraint' => '50'),
			'unit'								=> array('type' => 'varchar',	'constraint' => '50'),
			'name'								=> array('type' => 'varchar',	'constraint' => '50'),
			'dimension_id'					    => array('type' => 'text')
			)
		);

		$this->EE->dbforge->add_key('id', TRUE);
		$this->EE->dbforge->add_key('report_id', 'wr_reports');

		$this->EE->dbforge->create_table('wr_items');

		/*******************************************************************************************
		 *	Create wr_materials table
		 *******************************************************************************************/
		$this->EE->dbforge->add_field(array(
			'id'                              	=> array('type' => 'int',     	'constraint' => '10', 'unsigned' => TRUE, 'auto_increment' => TRUE),
			'report_id'							=> array('type' => 'int',     	'constraint' => '10', 'unsigned' => TRUE),
			'qty'       						=> array('type' => 'int',   	'constraint' => '10', 'unsigned' => TRUE),
			'dimension_id'		            	=> array('type' => 'varchar', 	'constraint' => '50'),
			'unit'				            	=> array('type' => 'varchar', 	'constraint' => '50'),
			'name'		            			=> array('type' => 'varchar', 	'constraint' => '50'),
			'item_id'							=> array('type' => 'varchar', 	'constraint' => '50')
			)
		);

		$this->EE->dbforge->add_key('id', TRUE);
		$this->EE->dbforge->add_key('report_id', 'wr_reports');

		$this->EE->dbforge->create_table('wr_materials');
		
		/*******************************************************************************************
		 *	Create wr_resources table
		 *******************************************************************************************/
		$this->EE->dbforge->add_field(array(
			'id'                              	=> array('type' => 'int',     	'constraint' => '10', 'unsigned' => TRUE, 'auto_increment' => TRUE),
			'report_id'							=> array('type' => 'int',     	'constraint' => '10', 'unsigned' => TRUE),
			'qty'       						=> array('type' => 'int',   	'constraint' => '10', 'unsigned' => TRUE),
			'resource_id'       				=> array('type' => 'varchar',   'constraint' => '50'),			
			'name'       						=> array('type' => 'varchar',   'constraint' => '50'),			
			'date'								=> array('type' => 'int',     	'constraint' => '10')
			)
		);

		$this->EE->dbforge->add_key('id', TRUE);
		$this->EE->dbforge->add_key('report_id', 'wr_reports');

		$this->EE->dbforge->create_table('wr_resources');

		return TRUE;
	}

	function update($current = '') {
		$this->EE->load->dbforge();

		# The commented code below may not be necessary...
		// if($current == '') {
		// 	$query = $this->EE->db->get_where('modules', array('module_name'=>'Workreports'));
		// 	$row = $query->row();
		// 	$current = $row->module_version;
		// }
		if( $current == $this->version ){
			return FALSE;
		}
		if( $current < '1.2'){
			/* 
			* Update field names for wr_* tables and 
			* change wr_reports.object_description and wr_reports.order_description
			* to 'text' type fields.
			* Note: name changes need column definition, because CI uses ALTER column CHANGE
			*/
			$fields = array(
				'object_description' 	=> array('name' => 'object_description', 	'type' => 'TEXT'),
				'order_description'		=> array('name' => 'order_description', 	'type' => 'TEXT'),
				'crew_leader'			=> array('name'	=> 'crew_leader_id', 		'type' => 'varchar',   'constraint' => '50'),
				'customer_account'		=> array('name'	=> 'customer_id', 			'type' => 'varchar',   'constraint' => '50'),
				'order'					=> array('name'	=> 'project_order_id', 		'type' => 'varchar',   'constraint' => '50'),
				'work_order'			=> array('name'	=> 'project_work_order_id', 'type' => 'varchar',   'constraint' => '50'),
				'work_report'			=> array('name'	=> 'project_work_report_id','type' => 'varchar',   'constraint' => '50'),
				'company'				=> array('name'	=> 'company_id', 			'type' => 'varchar',   'constraint' => '50'),
			);

			$this->EE->dbforge->modify_column('wr_reports', $fields);

			// Adding fields to wr_reports for synching axapta and MySQL
			$fields = array(
				'work_location_id'			=> array('type' => 'varchar',   'constraint' => '50'),
				'work_location_address'		=> array('type' => 'varchar',   'constraint' => '50'),
				'sales_id'	 				=> array('type' => 'varchar',   'constraint' => '50'),
				'sales_name' 				=> array('type' => 'varchar',   'constraint' => '50'), // SalesName
				'invoice_account'			=> array('type' => 'varchar',   'constraint' => '50'), // InvoiceAccount
				'team_contact_name' 		=> array('type' => 'varchar',   'constraint' => '50'), // TeamContactPerson
				'team_contact_address' 		=> array('type' => 'varchar',   'constraint' => '50'), // TeamContactPersonAddress
				'team_contact_phone' 		=> array('type' => 'varchar',   'constraint' => '50'), // TeamContactPersonPhone
				'team_contact_fax' 			=> array('type' => 'varchar',   'constraint' => '50'), // TeamContactPersonFax
				'team_contact_email' 		=> array('type' => 'varchar',   'constraint' => '50'), // TeamContactPersonEmail
				'customer_address'			=> array('type' => 'varchar',   'constraint' => '50'), // CustomerAddress
				'customer_phone'			=> array('type' => 'varchar',   'constraint' => '50'), // CustomerPhone
				'customer_fax' 				=> array('type' => 'varchar',   'constraint' => '50'), // CustomerFax
				'customer_email' 			=> array('type' => 'varchar',   'constraint' => '50'), // CustomerEmail
				'customer_contact_id' 		=> array('type' => 'varchar',   'constraint' => '50'), // CustomerContactPersonID
				'customer_contact_name' 	=> array('type' => 'varchar',   'constraint' => '50'), // CustomerContactPersonName
				'customer_contact_email' 	=> array('type' => 'varchar',   'constraint' => '50'), // CustomerContactPersonEmail
				'customer_contact_phone' 	=> array('type' => 'varchar',   'constraint' => '50'), // CustomerContactPersonPhone
				'customer_contact_mobile' 	=> array('type' => 'varchar',   'constraint' => '50')  // CustomerContactPersonCellPhone
			);
			$this->EE->dbforge->add_column('wr_reports', $fields);

		}

		if ($current < '1.3' ) {
			// Adding fields to wr_reports for synching axapta and MySQL
			$fields = array( 
				'research_norm_id'		=> array('type' => 'varchar',   'constraint' => '50'),
				'research_procedure_id'	=> array('type' => 'varchar',   'constraint' => '50'),
				'research_spec_id'		=> array('type' => 'varchar',   'constraint' => '50'),
				'review_procedure_id'	=> array('type' => 'varchar',   'constraint' => '50'),
				'review_spec_id'		=> array('type' => 'varchar',   'constraint' => '50'),
				'template_indicator'	=> array('type' => 'varchar',   'constraint' => '50'),
				'department_id'			=> array('type' => 'varchar',   'constraint' => '50'),
				'cost_center_id'		=> array('type' => 'varchar',   'constraint' => '50'),
				'technique_id'			=> array('type' => 'varchar',   'constraint' => '50'),
				'contract_id'			=> array('type' => 'varchar',   'constraint' => '50'),
				'contract_date'			=> array('type' => 'varchar',   'constraint' => '50'),
				'deadline_date'			=> array('type' => 'varchar',   'constraint' => '50'),
				'sales_responsible'		=> array('type' => 'varchar',   'constraint' => '50'),
				'team_contact_id' 		=> array('type' => 'varchar',   'constraint' => '50'),
				'created_time'			=> array('type' => 'varchar',   'constraint' => '50'),
				'created_date'			=> array('type' => 'varchar',   'constraint' => '50'),
				'created_by'			=> array('type' => 'varchar',   'constraint' => '50'),
				'modified_date'			=> array('type' => 'varchar',   'constraint' => '50'),
				'modified_time'			=> array('type' => 'varchar',   'constraint' => '50'),
				'project_id'			=> array('type' => 'varchar',   'constraint' => '50'), // already in initial install - don't add again!
				'modified_by'			=> array('type' => 'varchar',   'constraint' => '50')
			);
			$this->EE->dbforge->add_column('wr_reports', $fields);
		}

		if( $current < '1.3.1') {
			// Remove unnecessary fields:
			$this->EE->dbforge->drop_column('wr_reports', 'project_order_id');
			$this->EE->dbforge->drop_column('wr_reports', 'project_work_order_id');
			$this->EE->dbforge->drop_column('wr_reports', 'project_work_report_id');

			$this->EE->dbforge->drop_column('wr_reports', 'submitter_name');

			$this->EE->dbforge->drop_column('wr_reports', 'created_time');
			$this->EE->dbforge->drop_column('wr_reports', 'modified_time');

			$this->EE->dbforge->drop_column('wr_reports', 'contact_person');

			$this->EE->dbforge->drop_column('wr_reports', 'contract_date');

			$this->EE->dbforge->drop_column('wr_reports', 'sales_name');

			$this->EE->dbforge->drop_column('wr_reports', 'invoice_account');

			// Modify existing fields:
			$fields = array( 
				'created_date'    	=> array('name' => 'created_datetime', 'type' => 'int', 'constraint' => '10'),
				'modified_date'    	=> array('name' => 'modified_datetime', 'type' => 'int', 'constraint' => '10'),
				'execution_date'    => array('name' => 'execution_datetime', 'type' => 'int', 'constraint' => '10'),
				'deadline_date'    	=> array('name' => 'deadline_datetime', 'type' => 'int', 'constraint' => '10'),
				'submission_date'   => array('name' => 'submission_datetime', 'type' => 'int', 'constraint' => '10')
			);
			$this->EE->dbforge->modify_column('wr_reports', $fields);
		}

		if( $current < '1.3.2') {
			$fields = array(
				'work_location_address'	=> array('name' => 'work_location_address', 'type' => 'varchar', 'constraint' => '60'),
				'team_contact_address'	=> array('name' => 'team_contact_address', 	'type' => 'varchar', 'constraint' => '60'),
				'customer_address'		=> array('name' => 'customer_address', 		'type' => 'varchar', 'constraint' => '60'),
				'template_indicator'	=> array('name' => 'export_reason', 		'type' => 'varchar', 'constraint' => '60')
				);
			$this->EE->dbforge->modify_column('wr_reports', $fields);
	
			/*******************************************************************************************
			 *	Create wr_status table
			 *******************************************************************************************/
			$this->EE->dbforge->add_field(array(
				'id'                              	=> array('type' => 'int',     	'constraint' => '10', 'unsigned' => TRUE),
				'status'							=> array('type' => 'varchar', 	'constraint' => '50')
			));
			$this->EE->dbforge->add_key('id', TRUE);

			$this->EE->dbforge->create_table('wr_status');

			// Fill with values
			$data = array(
				array(
					'id' 	 => 0,
					'status' => 'rejected'
				), array(
					'id' 	 => 1,
					'status' => 'dispatched'
				), array(
					'id' 	 => 2,
					'status' => 'in_progress'
				), array(
					'id' 	 => 3,
					'status' => 'completed'
				), array(
					'id' 	 => 4,
					'status' => 'supervisor_approved'
				), array(
					'id' 	 => 5,
					'status' => 'admin_approved'
				), array(
					'id' 	 => 6,
					'status' => 'xml_approved'
				)
			);

			$this->EE->db->insert_batch('wr_status', $data);
		}

		if($current < '1.3.3') {
			$fields = array(
				'work_location_address'	=> array('name' => 'work_location_address', 'type' => 'varchar', 'constraint' => '255'),
				'team_contact_address'	=> array('name' => 'team_contact_address', 	'type' => 'varchar', 'constraint' => '255'),
				'customer_address'		=> array('name' => 'customer_address', 		'type' => 'varchar', 'constraint' => '255')
				);
			$this->EE->dbforge->modify_column('wr_reports', $fields);
		}

		if($current < '1.3.4') {
			$fields = array( 
				'review_norm_id'		=> array('type' => 'varchar',   'constraint' => '50')
				);
			$this->EE->dbforge->add_column('wr_reports', $fields);
		}

		return TRUE;
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
	    $query = $this->EE->db->get_where('modules', array('module_name' => 'workreports'));

	    $this->EE->db->where('module_id', $query->row('module_id'));
	    $this->EE->db->delete('module_member_groups');

	    $this->EE->db->where('module_name', 'workreports');
	    $this->EE->db->delete('modules');

	    $this->EE->db->where('class', 'workreports');
	    $this->EE->db->delete('actions');

	    $this->EE->dbforge->drop_table('wr_items');
	    $this->EE->dbforge->drop_table('wr_reports');
	    $this->EE->dbforge->drop_table('wr_materials');
	    $this->EE->dbforge->drop_table('wr_resources');

	    return TRUE;
	}
}// END CLASS

/* End of file upd.workreports.php */
/* Location: ./system/expressionengine/third_party/modules/workreports/upd.workreports.php */

?>