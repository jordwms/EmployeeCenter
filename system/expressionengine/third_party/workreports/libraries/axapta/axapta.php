<?php
class Axapta {
	private $conn;
	public $server_tzoffset;

	public function __construct() {
		$this->EE =& get_instance();

		if( !$this->conn = $this->axapta_connection() ){
			exit();
		} else {
			$this->server_tzoffset = 7200;
			//$this->server_tzoffset = $this->get_server_tzoffset();
			
			$models = array_diff( scandir( 'models' ), Array( ".", ".." ) );
			foreach ($models as $model) {
				try {
					include_once('models/'.$model);
					$class = str_replace('.php', '', $model);
					new $class;
				} catch (Exception $e) {
					print_r($e);
				}
			}
		}
	}

	function axapta_connection() {
		$this->EE->load->config('config');

		$host   = config_item('ax_host');
		$db     = config_item('ax_db');
		$user   = config_item('ax_user');
		$pass   = config_item('ax_pass');

		try {
			$ax_conn = new PDO("dblib:host=$host;Database=$db;", $user, $pass);
			return $ax_conn;
		} catch(PDOException $e) {
			//error logging/display
			echo('error connecting to axapta');
			return FALSE;
		}
	}

	function server_tzoffset() {
		$tz = $this->conn->query('select datepart(TZOFFSET, SYSDATETIMEOFFSET() )');
		$tzoffset = $tz->fetch();
		$tz->closeCursor();

		//turn minutes into seconds
		return $tzoffset[0] * 60;
	}

	//fix axapta's penchant for padding strings
	function fix_padding(&$data) {
		if( is_array($data) ){
			foreach ($data as $key => &$val) {
				if( is_array($data) ){
					$this->fix_padding($val);
				} elseif( is_string($val) ) {
					$val = ltrim(rtrim($val));
				}
			}
		}
		if( is_string($data) ){
			$data = ltrim(rtrim($data));
		}
		return $data;
	}

	/****
	 *	All Getter's should accecpt an "options" array.
	 *	Particular arguments which will affect the output of the returned data can be defined as key => value pairs.
	 *
	 *	$options = array(
	 *		column_name => limiting_value
	 *	);
	 *
	 ****/


	/*
	* Looks from $_GET to update a work report's status.
	*/
	function set_status() {
		if ( ($employee = $this->EE->axapta->employee()) && ($this->conn = $this->EE->axapta->axapta_connection()) ) {
			if( is_numeric($id = $this->EE->input->GET('id')) ) {
				$status = $this->EE->input->GET('status');

				$data = array('status' 	=> $status);

				$this->EE->db->where('id', $id);
				$this->EE->db->update('wr_reports', $data);
			} else {
				show_error('Invalid ID given.');
			}
		}
	}

	/*
	* Looks from $_POST to update a work report's customer_reference, rtd_reference, work_location_name or contact_person fields.
	*/
	function set_report() {
		if( is_numeric($this->EE->input->POST('id')) ) {
			$id = $this->EE->input->POST('id');
			// $callback = $this->EE->input->POST('callback');
			$customer_reference = $this->EE->input->POST('customer_reference');
			$rtd_reference = $this->EE->input->POST('rtd_reference');
			$work_location_name = $this->EE->input->POST('work_location_name');
			$contact_person = $this->EE->input->POST('contact_person');

			$data = array(
				'customer_reference' 	=> $customer_reference,
				'rtd_reference' 		=> $rtd_reference,
				'work_location_name'	=> $work_location_name,
				'contact_person' 		=> $contact_person
			);

			$this->EE->db->where('id', $id);
			$this->EE->db->update('wr_reports', $data);
		} else {
			show_error('Invalid id given.');
		}
	}

	/*
	* Returns an array for a single report given its id,
	* OR returns an array of reports given a status code.
	*/
	function get_reports($id=NULL, $status=NULL) {
		if(is_null($id) && is_null($status)) {
			show_error('Invalid id given.');
		}
		if(!is_null($id)) {
			$this->EE->db->where( array('id'=> $id) );

			return $this->EE->db->get('wr_reports')->row_array();
		} else {
			$this->EE->db->where( array('status'=> $status) );
			
			return $this->EE->db->get('wr_reports')->result_array();
		}
	}

	/*
	* Returns a table of all items related to a given report_id
	*/
	function get_items($id=NULL) {
		if(is_null($id)) {
			show_error('Invalid id given.');
		}

		return $this->EE->db->get_where('wr_items',array('report_id' => $id))->result_array();
	}

	/*
	* Returns a table of all materials related to a given report_id
	*/
	function get_materials($id=NULL) {
		if(is_null($id)) {
			show_error('Invalid id given.');
		}

		return $this->EE->db->get_where('wr_materials',array('report_id' => $id))->result_array();
	}

	/*
	* Returns a table of all resources related to a given report_id
	*/
	function get_resources($id=NULL) {
		if(is_null($id)) {
			show_error('Invalid id given.');
		}

		return $this->EE->db->get_where('wr_resources',array('report_id' => $id))->result_array();
	}

	/*
	* Takes a single entry from the wr_reports table and all related entries in wr_items, formats them in an 
	* an XML document and saves in a directory.
	*/
	function create_xml($report_id = NULL) {
		$dir_result = TRUE;

		if( is_null($report_id) ) {
			$id = $this->EE->input->GET('id');
		} else {
			$id = $report_id;
		}
			
		if( is_numeric($id) ) {

			$this->EE->load->library('typography');
			$this->EE->typography->initialize();

			// Get work report and associated items...
			$mats_query			= $this->get_materials($id);
			$items_query		= $this->get_items($id);
			$report_query		= $this->get_reports($id);
			$resources_query	= $this->get_resources($id);

			// echo $this->encode( $report_query['work_location_name'] );
			// die;

			$dir = '/ax-public/'.$report_query['company'].'/Project Management/'.$report_query['customer_account'].' '.$report_query['customer_name'].'/';
			$file = $report_query['order'].' '.$report_query['order'].'.'.$report_query['work_order'].'.'.$report_query['work_report'].' [TEST].xml';

			// If directory DNE, create
			if (!is_dir($dir)) {
			$dir_result = mkdir($dir);
			}

			if ($dir_result) {
				// New DOM document
				$doc = new DOMDocument('1.0','ISO-8859-1');
				$doc->formatOutput = TRUE;

				// Create and append root element of xml tree
				$xml_root = $doc->createElement('xml');
				$xml_root = $doc->appendChild($xml_root);

				// Create and append Employee ID
				$empl_id = $doc->createElement('EmplId', $this->encode( $report_query['submitter_id'] ) );
				$empl_id = $xml_root->appendChild($empl_id);

				// Create and append Crew Leader ID
				$crew_leader = $doc->createElement('CrewLeader', $this->encode( $report_query['crew_leader'] ) );
				$crew_leader = $xml_root->appendChild($crew_leader);

				// Create and append company
				$company = $doc->createElement('company');
				$company = $xml_root->appendChild($company);

				// Fill company with work report elements
				$company->appendChild($doc->createElement('Company', 				$this->encode( $report_query['company'] ) ));
				$company->appendChild($doc->createElement('CustomerAccount', 		$this->encode( $report_query['customer_account'] ) ));
				$company->appendChild($doc->createElement('Order', 					$this->encode( $report_query['order'] ) ));
				$company->appendChild($doc->createElement('WorkOrder', 				$this->encode( $report_query['work_order'] ) ));
				$company->appendChild($doc->createElement('WorkReport', 			$this->encode( $report_query['work_report'] ) ));
				$company->appendChild($doc->createElement('CustomerReference',		$this->encode( $report_query['customer_reference'] ) ));
				$company->appendChild($doc->createElement('RTDReference', 			$this->encode( $report_query['rtd_reference'] ) ));
				$company->appendChild($doc->createElement('WorkLocationName', 		$this->encode( $report_query['object_description'] ) ));
				$company->appendChild($doc->createElement('CONTACTPERSON', 			$this->encode( $report_query['work_location_name'] ) ));
				$company->appendChild($doc->createElement('ObjectDescription',		$this->encode( $report_query['object_description'] ) )); // $this->EE->typography->parse_type($report_query['object_description'],array('text_format' => 'none'))
				$company->appendChild($doc->createElement('ExecutionDate', 			date( 'Y-m-d', $report_query['execution_date'] ) ));

				// Create and append Resources
				$resources = $doc->createElement('Resources');
				$resources = $xml_root->appendChild($resources);

				foreach($resources_query as $a){
					// Create Resource and append to Resources
					$item = $doc->createElement('Resource');
					$item = $resources->appendChild($item);
					
					// Fill Resource with elements
					$item->appendChild($doc->createElement('ResourceID', 	$a['resource_id']));
					$item->appendChild($doc->createElement('Qty', 			$a['qty']));
					// $item->appendChild($doc->createElement('Date', 			date('Y-m-d',$a['date']) ));
				}

				// Create and append Items
				$items = $doc->createElement('Items');
				$items = $xml_root->appendChild($items);

				foreach($items_query as $a){
					// Create Item and append to Items
					$item = $doc->createElement('Item');
					$item = $items->appendChild($item);
					
					// Fill Item with elements
					$item->appendChild($doc->createElement('ItemId', 		$a['item_id']));
					$item->appendChild($doc->createElement('InventDimId',	$a['dimension_id']));
					$item->appendChild($doc->createElement('Unit', 			$a['unit']));
					$item->appendChild($doc->createElement('Qty', 			$a['qty']));
					// $item->appendChild($doc->createElement('Date', 			date('Y-m-d',$a['date']) ));
				}

				// Create and append Materials
				$mats = $doc->createElement('Materials');
				$mats = $xml_root->appendChild($mats);

				foreach($mats_query as $a){
					// Create Material and append to Materials
					$item = $doc->createElement('Material');
					$item = $mats->appendChild($item);
					
					// Fill Material with elements
					$item->appendChild($doc->createElement('ItemId', 		$a['item_id']));
					$item->appendChild($doc->createElement('InventDimId',	$a['dimension_id']));
					$item->appendChild($doc->createElement('Unit', 			$a['unit']));
					$item->appendChild($doc->createElement('Qty', 			$a['qty']));
				}

				/*
				* Overwrite protection - Searches for the file. If found, the user chooses whether to overwrite or create a unique XML file.
				* A view is rendered showing differences between the two XML files, along with options whether to overwrite, make unique or cancel.
				* The view shows fields side-by-side for simple comparison. 
				*/
				// if( file_exists($dir.$file) ) {		
				// 	return $this->compare($dir,$doc,$file);
				// } else {

					$result = $doc->save($dir.$file);

					// $result stores the size of the file if the save is successful or false otherwise...
					if($result) {
						// Notify user and change status in the database. 
						$this->EE->session->set_flashdata('message_success', 'Report approved. file = '.$file);

						$data = array( 'status'	=> 2 );
						$this->EE->db->where('id', $id);
						$this->EE->db->update('wr_reports', $data);
					} else {
						$this->EE->session->set_flashdata('message_failure', lang('xml_error'). " dir = $dir" );
					}
				// }
				} else {
					$this->EE->session->set_flashdata('message_failure', lang('xml_error').lang('dir_error') );
				}
			return TRUE;
		} else {
			show_error('Invalid id given.');
		}
	}
}// END CLASS
 
/* End of file axapta.php */
/* Location: ./system/expressionengine/third_party/modules/workreports/libraries/axapta.php */