<?php
class mysql {
	public function __construct() {
		$this->EE =& get_instance();
	}

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
			// Get work report and associated items...
			$mats_query			= $this->get_materials($id);
			$items_query		= $this->get_items($id);
			$report_query		= $this->get_reports($id);
			$resources_query	= $this->get_resources($id);

			// echo '<pre>'; print_r($report_id); die;			

			// Explode project_id into 'order', 'work order' and 'work report' sections
			$project_id = explode('/', $report_query['project_id']);

			$dir = '/ax-public/'.$report_query['company_id'].'/Customer Reporting/Tablet Interface/';
			$file = $project_id[0].' '.$project_id[0].'.'.$project_id[1].'.'.$project_id[2].' [TEST].xml';

			// If directory DNE, create
			if (!is_dir($dir)) {
				$dir_result = mkdir($dir);
			}

			if ($dir_result) {
				
				// New DOM document
				$doc = new DOMDocument('1.0','iso-8859-1');
				$doc->formatOutput = TRUE;

				// Create and append root element of xml tree
				$xml_root = $doc->createElement('xml');
				$xml_root = $doc->appendChild($xml_root);

				// Create and append Employee ID
				$empl_id = $doc->createElement('EmplId', htmlentities( $report_query['submitter_id'], NULL, "ISO-8859-1") );
				$empl_id = $xml_root->appendChild($empl_id);

				// Create and append Crew Leader ID
				$crew_leader = $doc->createElement('CrewLeader', htmlentities( $report_query['crew_leader_id'], NULL, "ISO-8859-1") );
				$crew_leader = $xml_root->appendChild($crew_leader);

				// Create and append company
				$company = $doc->createElement('company');
				$company = $xml_root->appendChild($company);

				// Fill company with work report elements
				$company->appendChild($doc->createElement('Company', 				htmlentities( $report_query['company_id'], 				NULL, 'ISO-8859-1') ));
				$company->appendChild($doc->createElement('CustomerAccount', 		htmlentities( $report_query['customer_id'], 			NULL, 'ISO-8859-1') ));
				$company->appendChild($doc->createElement('Order', 					htmlentities( $project_id[0], 							NULL, 'ISO-8859-1') ));
				$company->appendChild($doc->createElement('WorkOrder', 				htmlentities( $project_id[1], 							NULL, 'ISO-8859-1') ));
				$company->appendChild($doc->createElement('WorkReport', 			htmlentities( $project_id[2], 							NULL, 'ISO-8859-1') ));
				$company->appendChild($doc->createElement('CustomerReference',		htmlentities( $report_query['customer_reference'],		NULL, 'ISO-8859-1') ));
				$company->appendChild($doc->createElement('RTDReference', 			htmlentities( $report_query['rtd_reference'], 			NULL, 'ISO-8859-1') ));
				$company->appendChild($doc->createElement('WorkLocationName', 		htmlentities( $report_query['work_location_name'],		NULL, 'ISO-8859-1') ));
				$company->appendChild($doc->createElement('ContactPerson', 			htmlentities( $report_query['customer_contact_name'],	NULL, 'ISO-8859-1') ));
				$company->appendChild($doc->createElement('ObjectDescription',		htmlentities( $report_query['object_description'],		NULL, 'ISO-8859-1') )); // $this->EE->typography->parse_type($report_query['object_description'],array('text_format' => 'none'))
				$company->appendChild($doc->createElement('ExecutionDate', 			date( 'Y-m-d', $report_query['execution_datetime']) ));

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

					$result = $doc->save($dir.$file);

					// $result stores the size of the file if the save is successful or false otherwise...
				if($result) {
					// Notify user and change status in the database. 
					$this->EE->session->set_flashdata('message_success', 'Report approved. file = '.$file);

					$data = array( 'status'	=> 5 );
					$this->EE->db->where('id', $id);
					$this->EE->db->update('wr_reports', $data);
				} else {
					// Send an error
					show_error( lang('xml_error'). " dir = $dir" );
					return FALSE;
				}
			} else {
				// Send an error
				show_error( lang('xml_error').lang('dir_error') );
				return FALSE;
			}
			return TRUE;
		} else {
			show_error('Invalid id given.');
			return FALSE;
		}
	}
}// END CLASS
 
/* End of file axapta.php */
/* Location: ./system/expressionengine/third_party/modules/workreports/libraries/axapta.php */