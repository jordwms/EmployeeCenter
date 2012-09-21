<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
class Workreports_upd {
	var $version = '1.0';

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

		/*******************************************************************************************
		 *	Create wr_reports table
		 *******************************************************************************************/
		$this->EE->dbforge->add_field(array(
			'id'                              	=> array('type' => 'int',     	'constraint' => '10', 'unsigned' => TRUE, 'auto_increment' => TRUE),
			'submitter_name'					=> array('type' => 'varchar',  	'constraint' => '50'),
			'submitter_id'						=> array('type' => 'varchar',  	'constraint' => '50'),
			'crew_leader'						=> array('type' => 'varchar',  	'constraint' => '50'),
			'status'							=> array('type' => 'tinyint'),
			'execution_date'       				=> array('type' => 'int',     	'constraint' => '10'),
			'submission_date'       			=> array('type' => 'int',     	'constraint' => '10'),
			'company'		                    => array('type' => 'varchar', 	'constraint' => '50'),
			'customer_account'					=> array('type' => 'varchar', 	'constraint' => '50'),
			'customer_name'						=> array('type' => 'varchar', 	'constraint' => '50'),
			'order'								=> array('type' => 'varchar', 	'constraint' => '50'),
			'work_order'						=> array('type' => 'varchar', 	'constraint' => '50'),
			'work_report'						=> array('type' => 'varchar', 	'constraint' => '50'),
			'customer_reference'				=> array('type' => 'varchar', 	'constraint' => '50'),
			'rtd_reference'						=> array('type' => 'varchar', 	'constraint' => '50'),
			'work_location_name'				=> array('type' => 'varchar', 	'constraint' => '50'),
			'contact_person'					=> array('type' => 'varchar', 	'constraint' => '50'),
			'object_description'				=> array('type' => 'varchar', 	'constraint' => '50'),
			'order_description'					=> array('type' => 'varchar', 	'constraint' => '50'),
			'ticket_number'						=> array('type' => 'varchar', 	'constraint' => '50'),
			'remarks'							=> array('type' => 'text')
			)
		);

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