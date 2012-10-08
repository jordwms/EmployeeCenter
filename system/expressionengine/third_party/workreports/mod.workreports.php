<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Workreports {
	protected $return_data = '';

	function __construct() {
		$this->EE =& get_instance();
		$this->EE->load->library('axapta/axapta');
	}

	function dashboard() {
		$message = '';
		if( $this->EE->axapta->axapta_connection() ) {
			if( $employee = $this->EE->axapta->employee->get_remote(array( 'email' => $this->EE->session->userdata('email') )) ) {
				$employee = $employee[0];
				if( count($employee['groups']) > 0 ) {
					foreach ($employee['groups'] as $companies) {
						if( in_array('WA TECH', $companies) ){
							$message = 'You have '.$this->wrCount().' Work Reports assigned to you';
							//$message = $this->EE->lang->line('');
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
					$return_data = $this->EE->axapta->customer->get_remote( array('company_id' => '107') );
					break;

				case 'work_location':
					$return_data = $this->EE->axapta->work_location->get_remote( array('company_id' => '107', 'cost_center_id' => '10') );
					break;

				case 'contact_person':
					$return_data = $this->EE->axapta->contact_person->get_remote( array('id' => '107..SYB2001380') );
					break;

				case 'work_report':
					$return_data = $this->EE->axapta->work_report->get_remote(  );
					break;

				case 'materials':
					$return_data = $this->EE->axapta->materials->get_remote(  );
					break;

				case 'sales_items':
					$return_data = $this->EE->axapta->sales_items->get_remote( array('project_id' => '07.005532/001/120820') );
					break;

				case 'dispatch_list':
					$return_data = $this->EE->axapta->dispatch_list->get_remote( array('employee_id' => 'EM.107.0226') );
					break;

				
				default:
					return FALSE;
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
				echo '<pre>';
				print_r($return_data);
				echo '</pre>';
				//return 404;
			}
		} else {
			echo lang('unauthorized');
		}
	}

	function wrCount() {
		return $this->EE->db->count_all_results('wr_reports');
	}

	function wrList() {
		//if ( $employee = $this->EE->axapta->employee->get_remote( array('email' => $this->EE->session->userdata('email')) ) ){
			$tagdata = $this->EE->TMPL->tagdata;

			$this->EE->db->select('
						crew_leader_id,
						sales_id,
						project_id,
						REPLACE(\'project_id\', \'\/\', \'-\') AS project_id_uri,
						rtd_reference,
						sales_name,
						object_description,
						order_description,
						execution_datetime,
						work_location_name,
						work_location_address,
						customer_reference,
						company_id,
						customer_name,
						customer_contact_name,
						customer_contact_email,
						customer_contact_phone,
						customer_contact_mobile
			');

			$this->EE->db->from('wr_reports');
			// $this->EE->db->where('status', 0);

			$report = $this->EE->db->get()->result_array();

			$this->return_data = $this->EE->TMPL->parse_variables( $tagdata,  $report);
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
						sales_id,
						project_id,
						crew_leader_id,

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

			$data[0]['materials'] = $this->EE->db->get('wr_materials', array('report_id' => $data[0]['id']) )->result_array();

			$data[0]['sales_item'] = $this->EE->db->get('wr_items', array('report_id' => $data[0]['id']) )->result_array();

			$data[0]['resources'] = $this->EE->db->get('wr_resources', array('report_id' => $data[0]['id']) )->result_array();

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


			// echo "<pre>"; print_r($data); die;


			$tagdata = $this->EE->TMPL->tagdata;
			$this->return_data = $this->EE->TMPL->parse_variables( $tagdata,  $data );
			return $this->return_data;
		// }
	}

	/*
	* Validates a form before it can be process in submit_for_approval()
	*/
	function wrValidate() {
		
		$this->EE->load->library('form_validation');

		// 34. When there are no items, do not submit!
		$this->EE->form_validation->set_rules('items', 'Sales Items', 'required');

		return TRUE;
		return $this->EE->form_validation->run();
	}

	/*
	* Posts a work report to the MySQL database for supervisor/admin approval
	*/ 
	function submit_for_approval() {
		// echo '<pre>';
		// print_r($_POST['customer_name']);
		// die;

		// If the form has valid data process, else rerender the page with error messages.
		if ( $employee = $this->EE->axapta->employee->get_remote() ) {
			$success = array();

			$projid = explode( '/', $this->EE->input->post('projid') );

			$status = 0;
			
			if(in_array('WA DISP',$employee['groups'][$this->EE->input->post('company_id')])) {
				$status = 1;
			}
			if(in_array('WA ADMIN',$employee['groups'][$this->EE->input->post('company_id')])) {
				$status = 2;
			}

			/*
			 *	project id (workreport id) get's exploded into the three sections
			 *	[0] => order / [1] => work_order / [2] => work_report
			 */
			$data = array(
				'submitter_id'			=> $this->EE->input->post('id'), #should be employee name
				'submitter_name'		=> $employee['name_last_first'],
				'execution_date'		=> strtotime($this->EE->input->post('execution_date')),
				'submission_date'       => time(),
				'status'				=> $status,
				'company_id'			=> $this->EE->input->post('company_id'), #AKA DATAAREAID
				//'cost_center'			=> $this->EE->input->post('cost_center'), #AKA DIMENSION2_
				'customer_name'			=> $this->EE->input->post('customer_name'),
				'customer_account'		=> $this->EE->input->post('customer_account'),
				'order'					=> $projid[0],
				'work_order' 			=> $projid[1],
				'work_report' 			=> $projid[2],
				'customer_reference' 	=> $this->EE->input->post('customer_reference'),
				'rtd_reference'			=> $this->EE->input->post('rtd_reference'),
				'work_location_name' 	=> $this->EE->input->post('work_location_name'),
				'contact_person'		=> $this->EE->input->post('contact_person'),
				'object_description'	=> $this->EE->input->post('object_description'),
				'order_description'     => $this->EE->input->post('order_description'),
				'remarks'               => $this->EE->input->post('remarks')
				);

			$this->EE->db->insert('wr_reports', $data);
			$report_id = $this->EE->db->insert_id();
			$success['wr_reports'] = $this->EE->db->affected_rows();;
			
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
				$success['resources'] = $this->EE->db->affected_rows();
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
				$success['sales_items'] = $this->EE->db->affected_rows();
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
					$this->EE->db->insert('wr_materials', $data);
					$success['materials'] = $this->EE->db->affected_rows();
				}
			}
			if(in_array(0, $success)) {
				$this->EE->show_error('error submitting work report');

				// Delete all records where report_id
			} else {
				$this->EE->axapta->set_approval($this->EE->input->post('projid'), $this->EE->input->post('DataAreaID'), $employee['id']);
				if($status == 2) {
					$this->EE->axapta->create_xml($report_id);
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