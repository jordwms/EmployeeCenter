<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Workreports {
	protected $return_data = '';

	function __construct() {
		$this->EE =& get_instance();
		$this->EE->load->library('axapta/axapta');
		$this->EE->load->library('mysql');
	}
	
	/*
	 *	This is a simple router designed as a REST like API
	 *	Each method should return an named array, which is then passed to ouput,
	 *	which could be json encoded, print_r'd for debugging, or (todo) xml encoded
	 *	
	 *	You must be logged in to use this
	 */
	function rest() {
		if( $this->EE->session->userdata('email') && $this->EE->session->userdata('is_banned') == 0 ) {

			$employee = $this->EE->axapta->employee->get_remote(array( 'email' => $this->EE->session->userdata('email') ));
			$employee = $employee[0];

			$method = $this->EE->input->get('method');
			$output = $this->EE->input->get('output');
			$data   = $this->EE->input->post('data');

			switch ($method) {
				
				case 'employee':
				    $return_data = $this->EE->axapta->employee->get_remote( array('email' => $this->EE->session->userdata('email')) );
					break;

				case 'company':
					$return_data = $this->EE->axapta->company->get_remote( array('id' => '107') );
					break;

				case 'cost_center':
					$return_data = $this->EE->axapta->cost_center->get_remote( array('id' => $employee['cost_center_id'], 'company_id' => $employee['company_id']) );
					break;

				case 'customer':
					$return_data = $this->EE->axapta->customer->get_remote( array(
						'company_id' => $employee['company_id'],
						//'department_id' => $employee['department_id'],
						'cost_center_id' => $employee['cost_center_id'],
						'blocked' => 0
					));
					break;

				case 'work_location':
					$return_data = $this->EE->axapta->work_location->get_remote( array('company_id' => $employee['company_id'], $employee['cost_center_id']) );
					break;

				case 'contact_person':
					$return_data = $this->EE->axapta->contact_person->get_remote( array('id' => '107..SYB2001380') );
					break;

				case 'work_report':
					$options = array(
						'project_id' => '07.005532/001/120820'
					);
					$return_data = $this->EE->axapta->work_report->get_remote( $options );
					break;

				case 'template':
					$options = array(
						//'company_id' => $employee['company_id'],
						'export_reason' => 'TEMPLATE',
						'execution_date' => '2012-01-01'
					);
					$return_data = $this->EE->axapta->work_report->get_remote( $options );
					break;

				case 'resources':
					$return_data = $this->EE->axapta->resources->get_remote( array('project_id' => '07.005532/001/120820') );
					break;

				case 'materials':
					$options = array(
						'project_id' => '07.004845/002/120409te'
					);
					$return_data = $this->EE->axapta->materials->get_remote( $options );
					break;

				case 'sales_items':
					$return_data = $this->EE->axapta->sales_items->get_remote( array('project_id' => '07.005532/001/120820') );
					break;

				case 'contract_items':
					$options = array(
						'contract_id' => '10.000109',
						'film_indicator' => '1'
					);
					$return_data = $this->EE->axapta->contract_items->get_remote( $options );
					break;

				case 'dispatch_list':
					$return_data = $this->EE->axapta->dispatch_list->get_remote(array(
						'employee_id' => 'EM.107.0226', 
						'modified_datetime' => array('<', time())
					));
					break;

				case 'sync':
					echo '<h2>Sync Started</h2>';
					$this->sync($employee['id']);
					break;

				default:
					echo 'no method found';
					$return_data = FALSE;
					break;
			}

			if($return_data) {
				switch ($output) {
					default:
					case 'json':
						echo json_encode($return_data);
					break;
					
					/*
					case 'xml':
						//should return an XML document;
					break;
					*/

					case 'debug':
						echo '<pre>';
						print_r($return_data);
						echo '</pre>';
					break;
				}
				
			} else {
				echo '<h1>error</h1></br>';
				echo '<p>no return data</p>';
				//return 404;
			}
		} else {
			echo lang('unauthorized');
		}
	}

	function sync($employee_id = NULL) {
		if( !is_null($employee_id) ){
			// Get List of dispatched work reports
			// We use axapta's status to know if we've already synced a work report
			$dispatch_list = $this->EE->axapta->dispatch_list->get_remote(array(
				'employee_id' => 'EM.107.0226', 
				'status' => 0
			));


			//loop over dispatch list and sync the work report to mysql
			foreach ($dispatch_list as $dispatch_item) {
				//get workreport from axapta and add to mysql
				$work_report = $this->EE->axapta->work_report->get_remote( array( 'project_id' => $dispatch_item['project_id'] ) );


				//get resources from axapta and add to mysql
				$resources = $this->EE->axapta->resources->get_remote( array( 'project_id' => $dispatch_item['project_id'] ) );


				//get sales items from axapta and add to mysql
				$sales_items = $this->EE->axapta->sales_items->get_remote( array( 'project_id' => $dispatch_item['project_id'] ) );


				//get materials from axapta and add to mysql
				$materials = $this->EE->axapta->materials->get_remote( array( 'project_id' => $dispatch_item['project_id'] ) );


				//set status in axapta to 1 to know we've already synced this item
				$this->EE->axapta->work_report->set_status(array(
					'employee_id' => $employee_id,
					'company_id'  => $dispatch_item['company_id'],
					'project_id'  => $dispatch_item['project_id'],
					'status'      => 1
				));

				// Insert each entry to the MySQL database
				echo "<pre>"; print_r($work_report); die;

			}

			$templates = $this->EE->axapta->work_report->get_remote( array( 'template_indicator' => 1 ) );

		} else {
			echo 'invalid employee';
		}
	}

	function dashboard() {
		$message = '';
		if( $this->EE->axapta->axapta_connection() ) {
			if( $employee = $this->EE->axapta->employee->get_remote(array( 'email' => $this->EE->session->userdata('email') )) ) {
				$employee = $employee[0];
				
				// sync the 2 databases.
				$this->sync($employee['id']);			
				
				if( count($employee['groups']) > 0 ) {
					$message = '';
					foreach ($employee['groups'] as $companies) {
						if( in_array('WA TECH', $companies) ){
							$message .= 'You have '.$this->wrCount().' Work Reports assigned to you'.'<br>';
							//$message = $this->EE->lang->line('');
						}
						if( in_array('WA DISP', $companies) ){
							$message .= 'You have '.$this->wrCount().' Work Reports awaiting DISPATCHER approval'.'<br>';
						}
						if( in_array('WA ADMIN', $companies) ){
							$message .= 'You have '.$this->wrCount().' Work Reports awaiting ADMIN approval'.'<br>';
						}
					}
				} else {
					//$message = 'Please Contact HRM Department for Authorization';
					$message = $this->EE->lang->line('unauthorized');
				}
			} else {
				//$message = 'Invalid Employee Information Returned';
				$message = $this->EE->lang->line('invalid_employee');
				$message = lang('invalid_employee');
			}
		} else {
			//$message = 'No connection to Axapta';
			$message = $this->EE->lang->line('no_connection');
		}

		return $message;
	}

	function wrCount() {
		return $this->EE->db->count_all_results('wr_reports');
	}

	function wrList() {
		//if ( $employee = $this->EE->axapta->employee->get_remote( array('email' => $this->EE->session->userdata('email')) ) ){
			$tagdata = $this->EE->TMPL->tagdata;

			$this->EE->db->select('
						project_id,
						sales_id,
						crew_leader_id,
						sales_responsible,

						execution_datetime,

						company_id,
						rtd_reference,
						object_description,
						order_description,

						work_location_id,
						work_location_name,
						work_location_address,

						customer_id,
						customer_name,

						customer_contact_name,
						customer_contact_email,
						customer_contact_phone,
						customer_contact_mobile,

						customer_reference
			');

			$this->EE->db->from('wr_reports');
			// $this->EE->db->where('status', 0);

			$dispatch_list = $this->EE->db->get()->result_array();

			foreach ($dispatch_list as &$wr) {
				$wr['project_link'] = str_replace('/', '-', $wr['project_id'] );
			}

			$this->return_data = $this->EE->TMPL->parse_variables( $tagdata,  $dispatch_list);

			return $this->return_data;
		//}
	}


	// Remake of wrDetails(), but it comes from MySQL instead of Axapta
	function wrDetails() {
		// if ( ($employee = $this->EE->axapta->employee()) ) {
			$project_id = str_replace('-', '/', $this->EE->TMPL->fetch_param('projid') );

			$submit_uri = $this->EE->functions->fetch_site_index(0, 0).QUERY_MARKER.'ACT='.$this->EE->functions->fetch_action_id('Workreports', 'submit_for_approval');
			// $employee = $this->EE->axapta->employee->get_remote(array( 'email' => $this->EE->session->userdata('email') ));

			$this->EE->db->select('
						id,
						project_id,
						sales_id,
						crew_leader_id,
						sales_responsible,

						execution_datetime,

						company_id,
						rtd_reference,
						object_description,
						order_description,

						work_location_id,
						work_location_name,
						work_location_address,

						team_contact_id,
						team_contact_name,
						team_contact_address,
						team_contact_phone,
						team_contact_fax,
						team_contact_email,

						customer_id,
						customer_name,
						customer_address,
						customer_phone,
						customer_fax,
						customer_email,
						customer_reference,

						customer_contact_id,
						customer_contact_name,
						customer_contact_email,
						customer_contact_phone,
						customer_contact_mobile
			');

			$this->EE->db->from('wr_reports');
			$this->EE->db->where('project_id', $project_id);

			// $this->EE->db->where('crew_leader_id', $employee);

			$data[0] = $this->EE->db->get()->row_array();

			$data[0]['project_id_uri'] = str_replace('/', '-', $data[0]['project_id']);

			$data[0]['materials'] = $this->EE->db->get_where('wr_materials', array('report_id' => $data[0]['id']) )->result_array();

			$data[0]['sales_items'] = $this->EE->db->get_where('wr_items', array('report_id' => $data[0]['id']) )->result_array();

			$data[0]['resources'] = $this->EE->db->get_where('wr_resources', array('report_id' => $data[0]['id']) )->result_array();

			$form_open = array(
				'action'		=> $submit_uri,
				'name'          => 'workReport',
				'id'            => $this->EE->TMPL->form_id,
				'class'         => $this->EE->TMPL->form_class,
				'hidden_fields' => array(
										'project_id'			=> $data[0]['project_id'],
										'id' 					=> $data[0]['crew_leader_id'],
										'execution_datetime'	=> $data[0]['execution_datetime'],
										'company_id'			=> $data[0]['company_id'],
										'customer_id'			=> $data[0]['customer_id'],
										'rtd_reference'			=> $data[0]['rtd_reference'],
										'work_location_name'	=> $data[0]['work_location_name'],
										'work_location_address' => $data[0]['work_location_address']
									),
				'secure'        => TRUE,
				'onsubmit'      => ''
			);
			$data[0]['form_open'] = $this->EE->functions->form_declaration($form_open);
			$data[0]['form_close'] = '</form>';

			$tagdata = $this->EE->TMPL->tagdata;
			$this->return_data = $this->EE->TMPL->parse_variables( $tagdata,  $data );
			return $this->return_data;
		// }
	}


	/*
	* Posts a work report to the MySQL database for supervisor/admin approval
	*/ 
	function submit_for_approval() {
		// echo '<pre>';
		// print_r($_POST['project_id']);
		// die;

		// If the form has valid data process, else rerender the page with error messages.
		if ( $employee = $this->EE->axapta->employee->get_remote(array( 'email' => $this->EE->session->userdata('email') ) ) ) {
			$employee = $employee[0];
			$success = array();

			$status = 2;
			
			if(in_array('WA DISP',$employee['groups'][$this->EE->input->post('company_id')])) {
				$status = 3;
			}
			if(in_array('WA ADMIN',$employee['groups'][$this->EE->input->post('company_id')])) {
				$status = 4;
			}
			// If this is a new entry, insert into table, else update a current entry
			// Find out by searching the wr_reports table

			$this->EE->db->select('id');
			$this->EE->db->where('project_id', $this->EE->input->post('project_id') );
			$query = $this->EE->db->get('wr_reports')->row_array();



			if ( array_key_exists('id', $query) ) {
				$report_id = $query['id'];
				$data = array(
					'submitter_id'			=> $employee['id'], #should be employee name
					'execution_datetime'	=> strtotime($this->EE->input->post('execution_date')),
					'submission_datetime'   => time(),
					'status'				=> $status,
					'company_id'			=> $this->EE->input->post('company_id'), #AKA DATAAREAID
					//'cost_center'			=> $this->EE->input->post('cost_center'), #AKA DIMENSION2_
					'customer_name'			=> $this->EE->input->post('customer_name'),
					'customer_id'			=> $this->EE->input->post('customer_id'),
					'project_id'			=> $this->EE->input->post('project_id'),
					'customer_reference' 	=> $this->EE->input->post('customer_reference'),
					'rtd_reference'			=> $this->EE->input->post('rtd_reference'),
					'work_location_name' 	=> $this->EE->input->post('work_location_name'),
					'customer_contact_name'	=> $this->EE->input->post('customer_contact_name'),
					'object_description'	=> $this->EE->input->post('object_description'),
					'order_description'     => $this->EE->input->post('order_description'),
					'remarks'               => $this->EE->input->post('remarks')
					);

				$this->EE->db->where('project_id', $this->EE->input->post('project_id') );
				$this->EE->db->update('wr_reports', $data);
				$success['wr_reports'] = $this->EE->db->affected_rows();

				
				
				// Make wr_resources entries
				$resources = $this->EE->input->post('resources');
				foreach($resources as $resource) {
					$data = array(
						'report_id'		=> $report_id,
						'name'          => $resource['name'],
						'resource_id' 	=> $resource['resource_id'],
						'qty' 			=> $resource['qty']
						);

					$this->EE->db->update('wr_resources', $data);
					// $success['resources'] = $this->EE->db->affected_rows();
				}

				// Make wr_items entries
				$sales_items = $this->EE->input->post('salesItems');
				foreach($sales_items as $item) {
					$data = array(
						'report_id' 	=> $report_id,
						'name' 			=> $item['name'],
						'dimension_id'  => $item['dimension_id'],
						'item_id' 		=> $item['item_id'],
						'qty'           => $item['qty'],
						'unit'			=> $item['unit']
					);
					$this->EE->db->update('wr_items', $data);
					// $success['sales_items'] = $this->EE->db->affected_rows();
				}
				
				// Not every report uses materials
				if(isset($materials) ) {
					// Make wr_materials entries
					$materials = $this->EE->input->post('materials');
					foreach($materials as $material) {
						$data = array(
							'report_id'			=> $report_id,
							'dimension_id'      => $material['dimension_id'],
							'item_id' 			=> $material['item_id'],
							'name' 			    => $material['name'],
							'unit' 			    => $material['unit'],
							'qty'				=> $material['qty']
						);
						$this->EE->db->update('wr_materials', $data);
						// $success['materials'] = $this->EE->db->affected_rows();
					}
				}
			} else { // Entry DNE, then insert
				$data = array(
					'submitter_id'			=> $employee['id'], #should be employee name
					'execution_datetime'	=> strtotime($this->EE->input->post('execution_date')),
					'submission_datetime'   => time(),
					'status'				=> $status,
					'company_id'			=> $this->EE->input->post('company_id'), #AKA DATAAREAID
					//'cost_center'			=> $this->EE->input->post('cost_center'), #AKA DIMENSION2_
					'customer_name'			=> $this->EE->input->post('customer_name'),
					'customer_id'			=> $this->EE->input->post('customer_id'),
					'project_id'			=> $this->EE->input->post('project_id'),
					'customer_reference' 	=> $this->EE->input->post('customer_reference'),
					'rtd_reference'			=> $this->EE->input->post('rtd_reference'),
					'work_location_name' 	=> $this->EE->input->post('work_location_name'),
					'customer_contact_name'	=> $this->EE->input->post('customer_contact_name'),
					'object_description'	=> $this->EE->input->post('object_description'),
					'order_description'     => $this->EE->input->post('order_description'),
					'remarks'               => $this->EE->input->post('remarks')
					);
				$this->EE->db->insert('wr_reports', $data);
				
				$success['wr_reports'] = $this->EE->db->affected_rows();
				$report_id = $this->EE->db->insert_id();

				
				// Make wr_resources entries
				$resources = $this->EE->input->post('resources');
				foreach($resources as $resource) {
					$data = array(
						'report_id'		=> $report_id,
						'name'          => $resource['name'],
						'resource_id' 	=> $resource['resource_id'],
						'qty' 			=> $resource['qty']
						);

					$this->EE->db->insert('wr_resources', $data);
					// $success['resources'] = $this->EE->db->affected_rows();
				}

				// Make wr_items entries
				$sales_items = $this->EE->input->post('salesItems');
				foreach($sales_items as $item) {
					$data = array(
						'report_id' 	=> $report_id,
						'name' 			=> $item['name'],
						'dimension_id'  => $item['dimension_id'],
						'item_id' 		=> $item['item_id'],
						'qty'           => $item['qty'],
						'unit'			=> $item['unit']
					);
					$this->EE->db->insert('wr_items', $data);
					// $success['sales_items'] = $this->EE->db->affected_rows();
				}
				
				// Not every report uses materials
				if(isset($materials) ) {
					// Make wr_materials entries
					$materials = $this->EE->input->post('materials');
					foreach($materials as $material) {
						$data = array(
							'report_id'			=> $report_id,
							'dimension_id'      => $material['dimension_id'],
							'item_id' 			=> $material['item_id'],
							'name' 			    => $material['name'],
							'unit' 			    => $material['unit'],
							'qty'				=> $material['qty']
						);
						$this->EE->db->update('wr_materials', $data);
						// $success['materials'] = $this->EE->db->affected_rows();
					}
				}
			}
			// echo "<pre>"; print_r($success); die;
			if(in_array(0, $success)) {
				show_error('error submitting work report');

				// Delete all records where report_id
			} else {
				// $this->EE->axapta->set_approval($this->EE->input->post('projid'), $this->EE->input->post('DataAreaID'), $employee['id']);
				if($status == 2) {
					$this->EE->mysql->create_xml($report_id);
				}
				$this->EE->output->show_message(array(
					'title'   => 'Information Accepted',
		            'heading' => 'Thank you',
		            'content' => 'Sucessfully submitted work report.',
		            'link'    => array($this->EE->functions->form_backtrack('-1'), 'Return to Dashboard')
		        ));
			}
			return TRUE;
		} else {
			return FALSE;
		}
	}
}// END CLASS

/* End of file mod.workreports.php */
/* Location: ./system/expressionengine/third_party/modules/workreports/mod.workreports.php */