<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Workreports {
	protected $return_data = '';

	function __construct() {
		$this->EE =& get_instance();
		$this->EE->load->library('axapta/axapta');
		// $this->EE->load->library('WKPDF');
		include_once(__DIR__.'/libraries/WKPDF.php');
		require('Mail.php'); 
		require('Mail/mime.php'); 
		$this->EE->load->library('mysql');
		$this->EE->config->set_item('compress_output', FALSE); 
	}

	function wrPrint() {
		$pdf = new WKPDF();
		
		// define some HTML content with style
		$html = $this->wrDetails();

		// $project_id = str_replace('-', '/', $this->EE->TMPL->fetch_param('projid') );

		// Create PDF
		$pdf->set_title('WorkReport');
		$pdf->set_html($html);
		$pdf->render();
		$pdf->output(WKPDF::$PDF_SAVEFILE, 'TEST.pdf');

		// Send email with PDF attachment
		// $to = 'ProfJord@Gmail.com';
		$to = 'Robert.McCann@applusrtd.com';
		$file = FCPATH.'tmp/TEST.pdf';
				
		if( $this->send_mail($to, $file) ) {
			echo 'Message successfully sent!';
		} else {
			echo 'Error.';
		}
	}

	/*
	* Emails the customer a work report PDF
	*/
	function send_mail($to, $file) {
		$mime = new Mail_mime();

		// email fields: to, from, subject, and so on
		$from = 'Robert.McCann@applusrtd.com'; // 'vxray@localhost';
		$subject = 'Test PDF message';
		$message = 'Attached is a test work report. Please keep this for your records.';
		$headers = "From: $from"; // root@localhost

		// boundary 
		$semi_rand = md5(time()); 
		$mime_boundary = "==Multipart_Boundary_x{$semi_rand}x";
		  	
		// headers for attachment 
		$headers .= "\nMIME-Version: 1.0\n" . "Content-Type: multipart/mixed;\n" . " boundary=\"{$mime_boundary}\"";

		// multipart boundary 
		$message = "--{$mime_boundary}\n" . "Content-Type: text/plain; charset=\"iso-8859-1\"\n" .
		"Content-Transfer-Encoding: 7bit\n\n" . $message . "\n\n";

    	// preparing attachments
        $message .= "--{$mime_boundary}\n";
        $fp = 		@fopen($file,"rb");
		$data =    	@fread($fp,filesize($file));
                	@fclose($fp);
        $data = chunk_split(base64_encode($data));
        $message .= "Content-Type: application/octet-stream; name=\"".basename($file)."\"\n" . 
	        "Content-Description: ".basename($file)."\n" .
	        "Content-Disposition: attachment;\n" . " filename=\"".basename($file)."\"; size=".filesize($file).";\n" . 
	        "Content-Transfer-Encoding: base64\n\n" . $data . "\n\n";
	    $message .= "--{$mime_boundary}--";
	    
	    $returnpath = "-f" . $from; 

		return mail($to, $subject, $message, $headers, $returnpath);
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
			//$employee = $this->EE->axapta->employee->get_remote(array( 'email' => 'jordan.williams@applusrtd.com' ));
			$employee = $employee[0];

			$method  = $this->EE->input->get('method');
			$output  = $this->EE->input->get('output');

			if( !is_array($options = $this->EE->input->post('options'))) {
				$options = array();
			}


			switch ($method) {
				
				case 'employee':
                    // $options = array_merge($options, array(
                        
                    // ));
				    $return_data = $this->EE->axapta->employee->get_remote( array('email' => $this->EE->session->userdata('email')) );
					break;

				case 'company':
                    // $options = array_merge($options, array(
                        
                    // ));
					$return_data = $this->EE->axapta->company->get_remote( array('id' => '107') );
					break;

				case 'cost_center':
                    // $options = array_merge($options, array(
                        
                    // ));
					$return_data = $this->EE->axapta->cost_center->get_remote( array('id' => $employee['cost_center_id'], 'company_id' => $employee['company_id']) );
					break;

				case 'customer':
                    // $options = array_merge($options, array(
                        
                    // ));
					$return_data = $this->EE->axapta->customer->get_remote( array(
						'company_id' => $employee['company_id'],
						//'department_id' => $employee['department_id'],
						'cost_center_id' => $employee['cost_center_id'],
						'blocked' => 0
					));
					break;

				case 'work_location':
					$options = array_merge($options, array(
						'company_id' => $employee['company_id']
					));
					$return_data = $this->EE->axapta->work_location->get_remote( $options );
					break;

				case 'contact_person':
					$options = array_merge($options, array(
						'company_id' => $employee['company_id']
					));
					$return_data = $this->EE->axapta->contact_person->get_remote( $options );
					break;

				case 'work_report':
					$options = array_merge($options, array(
						'project_id' => '07.005532/001/120820'
					));
					$return_data = $this->EE->axapta->work_report->get_remote( $options );
					break;

				case 'template':
					$options = array_merge($options, array(
						//'company_id' => $employee['company_id'],
						'export_reason' => 'TEMPLATE',
						'execution_date' => '2012-01-01'
					));
					$return_data = $this->EE->axapta->work_report->get_remote( $options );
					break;

				case 'project_resources':
                    // $options = array_merge($options, array(
                        
                    // ));
					$return_data = $this->EE->axapta->resources->get_remote( array('project_id' => '07.005532/001/120820') );
					break;

				case 'resources':
					$options = array_merge($options, array(
						'company_id' => $employee['company_id'],
						'department_id' => $employee['department_id']
					));
					$return_data = $this->EE->axapta->employee->get_remote( $options );
					break;

				case 'materials':
					$options = array(
						'project_id' => '07.003464/142/120802'
					);
					$return_data = $this->EE->axapta->materials->get_remote( $options );
					break;

				case 'sales_items':
                    // $options = array_merge($options, array(
                        
                    // ));
					$return_data = $this->EE->axapta->sales_items->get_remote( array('project_id' => '07.005532/001/120820') );
					break;

				case 'contract_items':
    				$options = array_merge($options, array(
    					
    				));
					$return_data = $this->EE->axapta->contract_items->get_remote( $options );
					break;

				case 'dispatch_list':
                    // $options = array_merge($options, array(
                        
                    // ));
					$return_data = $this->EE->axapta->dispatch_list->get_remote(array(
						'employee_id' => $employee['id']
						//'modified_datetime' => array('<', time())
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
						header('Cache-Control: no-cache, must-revalidate');
						header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
						header('Content-type: application/json; charset=utf8');
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
				
				ob_end_flush(  );
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
				'employee_id' => $employee_id
			));

			$templates = $this->EE->axapta->work_report->get_remote( array( 
				'export_reason' => 'TEMPLATE'
				,'execution_date' => '2012-01-01' 
			) );

			$all_reports = array_merge($dispatch_list, $templates);

			//loop over dispatch list and sync the work report to mysql
			foreach ($all_reports as $dispatch_item) {
				$this->EE->db->select('project_id');
				$this->EE->db->from('wr_reports');
				$this->EE->db->where('project_id', $dispatch_item['project_id']);


				if($this->EE->db->count_all_results() == 0) {
				
					//get workreport from axapta and add to mysql
					$work_report = $this->EE->axapta->work_report->get_remote( array( 'project_id' => $dispatch_item['project_id'] ) );
					
					$customer = $this->EE->axapta->customer->get_remote( array( 'id' => $work_report[0]['customer_id'] ) );
					
					// Insert each entry to the MySQL database
					$data = array(
						'project_id' 				=> $work_report[0]['project_id'],
			            'sales_id' 					=> $work_report[0]['sales_id'],
			            //'submitter_id' 			=> $employee_id,

			            'customer_id' 				=> $work_report[0]['customer_id'],
			            'customer_name' 			=> $work_report[0]['customer_name'],
			            'customer_reference' 		=> $work_report[0]['customer_reference'],
			            'customer_address' 			=> $customer[0]['address'],
			            'customer_phone' 			=> $customer[0]['phone'],
			            //'customer_email' 			=> $customer[0]['email'],
			            'customer_fax' 				=> $customer[0]['fax'],

			            'customer_contact_id'	 	=> $work_report[0]['customer_contact_person_id'],

			            'company_id'	 			=> $work_report[0]['company_id'],
			            'department_id' 			=> $work_report[0]['department_id'],
			            'cost_center_id' 			=> $work_report[0]['cost_center_id'],
			            'technique_id' 				=> $work_report[0]['technique_id'],

			            'contract_id' 				=> $work_report[0]['contract_id'],

			            // 'deadline_datetime'  	=> $work_report[0]['deadline_datetime'],
			            'rtd_reference' 			=> $work_report[0]['rtd_reference'],
			            'sales_responsible' 		=> $work_report[0]['sales_responsible'],
			            'crew_leader_id' 			=> $work_report[0]['crew_leader_id'],

			            'team_contact_id' 			=> $work_report[0]['team_contact_person_id'],

			            'work_location_id'   		=> $work_report[0]['work_location_id'],
			           	'work_location_name'    	=> $work_report[0]['work_location_name'],
			            'work_location_address' 	=> $work_report[0]['work_location_address'],

			            'object_description' 		=> $work_report[0]['object_description'],
			            'order_description' 		=> $work_report[0]['order_description'],

			            'research_norm_id' 			=> $work_report[0]['research_norm_id'], 
			            'research_procedure_id' 	=> $work_report[0]['research_procedure_id'],
			            'research_spec_id' 			=> $work_report[0]['research_spec_id'],

			           	'review_procedure_id' 		=> $work_report[0]['review_procedure_id'],
			            //'review_norm_id' 			=> $work_report[0]['review_norm_id'],  //missing from mysql :(
			            'review_spec_id' 			=> $work_report[0]['review_spec_id'],

			            'status' 					=> 1,
			            'export_reason' 	    	=> $work_report[0]['export_reason'],

			            'created_by' 				=> $work_report[0]['created_by'],
			            'modified_by' 				=> $work_report[0]['modified_by'],
			            'modified_datetime' 		=> $work_report[0]['modified_datetime'],
			            'created_datetime' 			=> $work_report[0]['created_datetime'],
			            'execution_datetime' 		=> $work_report[0]['execution_datetime']
						);

					if( $customer_contact = $this->EE->axapta->contact_person->get_remote( array( 'id' => $work_report[0]['customer_contact_person_id'] ) )){
						array_merge($data, array(
				            'customer_contact_name' 	=> $customer_contact[0]['name'],
				            'customer_contact_email' 	=> $customer_contact[0]['email'],
				            'customer_contact_phone' 	=> $customer_contact[0]['phone'],
				            'customer_contact_mobile' 	=> $customer_contact[0]['cell_phone']
			            ));
					}
					
					// if( $team_contact = $this->EE->axapta->contact_person->get_remote( array( 'id' => $work_report['team_contact_id'] ) )){
					// 	array_merge($data, array(
					// 		'team_contact_id'	 		=> $work_report[0]['team_contact_person_id'],
					// 		'team_contact_name' 		=> $team_contact[0]['name'],
					// 		'team_contact_email' 		=> $team_contact[0]['email'],
					// 		'team_contact_phone' 		=> $team_contact[0]['phone'],
					// 		'team_contact_mobile' 		=> $team_contact[0]['cell_phone']
					//	));
					// }

					$this->EE->db->insert('wr_reports', $data);
					$report_id = $this->EE->db->insert_id();

					if( !$this->EE->db->affected_rows() == count($work_report) ){
						// WE HAD A PROBLEM, DELETED EVERYTHING AND SHOW ERROR
					}

					//get resources from axapta and add to mysql
					$resources = $this->EE->axapta->resources->get_remote( array( 'project_id' => $dispatch_item['project_id'] ) );

					foreach($resources as $resource) {
						$data = array(
							'resource_id' 	=> $resource['id'],
							'name'			=> $resource['name'], 
							'report_id' 	=> $report_id
							);
						$this->EE->db->insert('wr_resources', $data);
					}

					if( !$this->EE->db->affected_rows() == count($resources) ){
						// WE HAD A PROBLEM, DELETED EVERYTHING AND SHOW ERROR
					}

					//get sales items from axapta and add to mysql
					$sales_items = $this->EE->axapta->sales_items->get_remote( array( 'project_id' => $dispatch_item['project_id'] ) );
					
					foreach($sales_items as $item) {
						$data = array(
							'item_id' 		=> $item['id'],
							'name'			=> $item['name'],
							'unit'			=> $item['unit'],
							'dimension_id'	=> $item['dimension_id'],   
							'report_id' 	=> $report_id
							);

						$this->EE->db->insert('wr_items', $data);
					}

					if( !$this->EE->db->affected_rows() == count($sales_items) ){
						// WE HAD A PROBLEM, DELETED EVERYTHING AND SHOW ERROR
					}

					//get materials from axapta and add to mysql
					$materials = $this->EE->axapta->materials->get_remote( array( 'project_id' => $dispatch_item['project_id'] ) );
					
					foreach($materials as $mat) {
						$data = array(
							'item_id' 		=> $mat['id'],
							'name'			=> $mat['name'],
							'unit'			=> $mat['unit'],
							// 'qty'			=> (int)$mat['amount'],
							'dimension_id'	=> $mat['dimension_id'],   
							'report_id' 	=> $report_id
							);

						$this->EE->db->insert('wr_materials', $data);
					}

					if( !$this->EE->db->affected_rows() == count($materials) ){
						// WE HAD A PROBLEM, DELETED EVERYTHING AND SHOW ERROR
					}

					//set status in axapta to 1 to know we've already synced this item
					// $this->EE->axapta->work_report->set_ax_status(array(
					// 	'employee_id' => $employee_id,
					// 	'company_id'  => $work_report[0]['company_id'],
					// 	'project_id'  => $work_report[0]['project_id'],
					// 	'status'      => 1
					// ));
				}
			}

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
							$message .= 'You have '.$this->count($employee, 'WA TECH').' Work Reports assigned to you'.'<br>';
							//$message = $this->EE->lang->line('');
						}
						if( in_array('WA DISP', $companies) ){
							$message .= 'You have '.$this->count($employee, 'WA DISP').' Work Reports awaiting DISPATCHER approval'.'<br>';
						}
						if( in_array('WA ADMIN', $companies) ){
							$message .= 'You have '.$this->count($employee, 'WA ADMIN').' Work Reports awaiting ADMIN approval'.'<br>';
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

	function count($employee, $group_id) {
		$this->EE->db->select('*');
		$this->EE->db->from('wr_reports');

		switch ($group_id) {
			case 'WA TECH':
				$this->EE->db->join('wr_resources','wr_resources.report_id = wr_reports.id' );
				$this->EE->db->where('resource_id', $employee['id']);
				$this->EE->db->where('status <', 3);
				break;

			case 'WA DISP':
				$this->EE->db->where('sales_responsible', $employee['id']);
				$this->EE->db->where('status', 4);
				break;

			case 'WA ADMIN':
				$this->EE->db->where('status', 5);

				$this->EE->db->where('company_id', $employee['company_id']);
				$this->EE->db->where('department_id', $employee['department_id']);
				break;
		}
		
		
		return $this->EE->db->count_all_results();
	}

	function templates() {
		$tagdata = $this->EE->TMPL->tagdata;

		$options = array(
			//'company_id' => $employee['company_id'],
			'export_reason' => 'TEMPLATE',
			'execution_date' => '2012-01-01'
		);

		$template_list = $this->EE->axapta->work_report->get_remote( $options );

		foreach ($template_list as &$wr) {
			$wr['project_link'] = str_replace('/', '-', $wr['project_id'] );
			// $wr['project_link'] = $employee_id[2].'-'.$day['yday'].'-'.$sequence_id;
		}

		$this->return_data = $this->EE->TMPL->parse_variables( $tagdata,  $template_list);

		return $this->return_data;
	}

	function wrList() {
		if ( $employee = $this->EE->axapta->employee->get_remote( array('email' => $this->EE->session->userdata('email')) ) ){
			$tagdata = $this->EE->TMPL->tagdata;


			$this->EE->db->select('
						project_id,
						sales_id,
						crew_leader_id,
						sales_responsible,

						execution_datetime,
						wr_status.status,

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
			$this->EE->db->join('wr_resources','wr_resources.report_id = wr_reports.id' );
			$this->EE->db->join('wr_status', 'wr_status.id = wr_reports.status');
			$this->EE->db->where('resource_id', $employee[0]['id']);
			$this->EE->db->where('wr_reports.status <', 4);
			$this->EE->db->order_by('execution_datetime', 'desc');
			// $this->EE->db->where('status', 0);

			$dispatch_list = $this->EE->db->get()->result_array();

			foreach ($dispatch_list as &$wr) {
				$wr['project_link'] = str_replace('/', '-', $wr['project_id'] );
			}

			$this->return_data = $this->EE->TMPL->parse_variables( $tagdata,  $dispatch_list);

			return $this->return_data;
		}
	}

	function wrDetails() {
		// if ( ($employee = $this->EE->axapta->employee()) ) {
			$employee = $this->EE->axapta->employee->get_remote( array('email' => $this->EE->session->userdata('email')) );
			$employee = $employee[0];
			$project_id = str_replace('-', '/', $this->EE->TMPL->fetch_param('projid') );

			$submit_uri = $this->EE->functions->fetch_site_index(0, 0).QUERY_MARKER.'ACT='.$this->EE->functions->fetch_action_id('Workreports', 'submit_for_approval');
			// $employee = $this->EE->axapta->employee->get_remote(array( 'email' => $this->EE->session->userdata('email') ));

			$this->EE->db->select('
						id,
						project_id,
						contract_id,
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
						customer_contact_mobile,

						export_reason,
						status
			');

			$this->EE->db->from('wr_reports');
			$this->EE->db->where('project_id', $project_id);

			// $this->EE->db->where('crew_leader_id', $employee);

			$data[0] = $this->EE->db->get()->row_array();
			// echo "<pre>"; print_r($data[0]); die;


			$data[0]['materials'] = $this->EE->db->get_where('wr_materials', array('report_id' => $data[0]['id']) )->result_array();

			$data[0]['sales_items'] = $this->EE->db->get_where('wr_items', array('report_id' => $data[0]['id']) )->result_array();

			if ( $data[0]['export_reason'] == 'TEMPLATE' ){
				$data[0]['resources'][0]['resource_id'] = $employee['id'];
				$data[0]['resources'][0]['name'] = $employee['name'];
			}else {
				$data[0]['resources'] = $this->EE->db->get_where('wr_resources', array('report_id' => $data[0]['id']) )->result_array();
			}


			$form_open = array(
				'action'		=> $submit_uri,
				'name'          => 'workReport',
				'id'            => $this->EE->TMPL->form_id,
				'class'         => $this->EE->TMPL->form_class,
				'hidden_fields' => array(
										'project_id'			=> $data[0]['project_id'],
										'id' 					=> $data[0]['crew_leader_id'],
										'execution_datetime'	=> $data[0]['execution_datetime'],
										'contract_id'      	    => $data[0]['contract_id'],
										'company_id'			=> $data[0]['company_id'],
										'customer_id'			=> $data[0]['customer_id'],
										'customer_contract_id'	=> $data[0]['customer_id'],
										'rtd_reference'			=> $data[0]['rtd_reference'],
										'work_location_name'	=> $data[0]['work_location_name'],
										'work_location_address' => $data[0]['work_location_address']
									),
				'secure'        => TRUE,
				'onsubmit'      => ''
			);
			$data[0]['form_open'] = $this->EE->functions->form_declaration($form_open);
			$data[0]['form_close'] = '</form>';

			$data[0]['actions'] = ''; // initializing 'actions' so we can have 1 concatonated string

			if ($data[0]['status'] < 4){
				if( in_array('WA DISP',$employee['groups'][$data[0]['company_id']]) || in_array('WA ADMIN',$employee['groups'][$data[0]['company_id']]) ){
					// Save button
					$data[0]['actions'].= '<input type="submit" name="save" class="btn" value="Save">';
					
					if($data[0]['status'] < 3){
						// Submit + Approve button
						$data[0]['actions'].= '<input type="submit" name="submit" class="btn" value="Submit and Approve">';
					}
					
					if($data[0]['status'] > 2 && $data[0]['status'] < 5 ){
						// Approve button, Reject button
						$data[0]['actions'].= '<input type="submit" name="approve" class="btn" value="Approve">';
						$data[0]['actions'].= '<input type="submit" name="reject" class="btn" value="Reject">';
					}

				} else { // NOT DISP/ADMIN
					if($data[0]['status'] < 3){
						// Submit button, Save button
						$data[0]['actions'].= '<input type="submit" name="submit" class="btn" value="Submit">';
						$data[0]['actions'].= '<input type="submit" name="save" class="btn" value="Save">';
					}
					if($data[0]['status'] > 2) {
						// No buttons!
					}

				}
			}

			$tagdata = $this->EE->TMPL->tagdata;
			$this->return_data = $this->EE->TMPL->parse_variables( $tagdata,  $data );
			return $this->return_data;
		// }
	}

	function contract_items(){
		if( $contract_id = $this->EE->TMPL->fetch_param('contract_id') ){
			$options = array('contract_id' => $contract_id);
		} else {
			return FALSE;
		}

		if( $film_indicator = $this->EE->TMPL->fetch_param('film_indicator') ){
			$options['film_indicator'] = $film_indicator;
		}

		$return_data = $this->EE->axapta->contract_items->get_remote( $options );

		$tagdata = $this->EE->TMPL->tagdata;

		return $this->EE->TMPL->parse_variables( $tagdata, $return_data );
	}


	/*
	* Posts a work report to the MySQL database for supervisor/admin approval
	*/ 
	function submit_for_approval() {
		// If the form has valid data process, else rerender the page with error messages.
		if ( $employee = $this->EE->axapta->employee->get_remote(array( 'email' => $this->EE->session->userdata('email') ) ) ) {
			$employee = $employee[0];
			$success = array();

			// IF this is a new entry:
			// 1. Duplicate the entry
			// 2. Fill new entry with form data
			// ELSE:
			// -  Update a current entry

			// Search for this WR wr_reports
			$this->EE->db->select('*');
			$this->EE->db->where('project_id', $this->EE->input->post('project_id') );
			$query = $this->EE->db->get('wr_reports')->row_array();

			if ($query['status'] < 2 && $this->EE->input->post('save') ) {
				$status = 2;
			} else {
				$status = $query['status'];
			}

			// If the user wanted to save OR approve the WR, it will remain open until saved. 
			if ( $this->EE->input->post('submit') || $this->EE->input->post('approve') ) {
				if(in_array('WA DISP',$employee['groups'][$this->EE->input->post('company_id')])) {
					$status = 3;
				}
				if(in_array('WA ADMIN',$employee['groups'][$this->EE->input->post('company_id')])) {
					$status = 4;
				}
			} elseif ( $this->EE->input->post('reject') ) {
				$status = 0;
			}

			// If the MySQL query came back with a result...
			if ( array_key_exists('id', $query) ){
				$report_id = $query['id'];
							
				// echo "<pre>"; print_r($query);
				// echo "-------------";
				// echo "<pre>"; print_r($_POST); die;

				// If this report was built from a template...
				if($query['export_reason'] == 'TEMPLATE'){
					$employee_id = explode('.', $employee['id']);// $employee_id[2] will be section 1 of $project_id
					$day = getdate();
					$project_id = explode('/',$this->EE->input->post('project_id') );

					// finds sequence # -> number or reports since 12 A.M. of current day
					$this->EE->db->like('submitter_id', $employee['id'] );
					$this->EE->db->from('wr_reports');
					$this->EE->db->where('submission_datetime >', time('Y-M-d') );
					
					$sequence_id = $this->EE->db->count_all_results()+1;
					$sequence_id+=1;

					$project_id[2] = crc32( $employee_id[2].time() );
					// $project_id[2] = $employee_id[2].$day['yday'].$sequence_id; // NEW segment 3

					// Insert a new work report using the data found in the template, but creating a unique project ID
					$data = array(
						'submitter_id'			=> $employee['id'],
						'execution_datetime'	=> strtotime($this->EE->input->post('execution_date')),
						'submission_datetime'   => time(),
						'status'				=> $status,
						'company_id'			=> $query['company_id'], #AKA DATAAREAID
						//'cost_center'			=> $this->EE->input->post('cost_center'), #AKA DIMENSION2_
						'customer_name'			=> $query['customer_name'],
						'customer_id'			=> $query['customer_id'],
						'project_id'			=> implode('/', $project_id),
						'customer_reference' 	=> $this->EE->input->post('customer_reference'),
						'rtd_reference'			=> $this->EE->input->post('rtd_reference'),
						'work_location_name' 	=> $this->EE->input->post('work_location_name'),
                        'customer_contact_id'   => $this->EE->input->post('customer_contact_id'),
						'customer_contact_name'	=> $this->EE->input->post('customer_contact_name'),
						'object_description'	=> $this->EE->input->post('object_description'),
						'order_description'     => $this->EE->input->post('order_description'),
						'remarks'               => $this->EE->input->post('remarks')
						);

					$this->EE->db->insert('wr_reports', $data);
					$success['wr_reports'] = $this->EE->db->affected_rows();
					$report_id = $this->EE->db->insert_id();

					// Update wr_resources entries
					$resources = $this->EE->input->post('resources');
					foreach($resources as $resource) {
						$data = array(
							'report_id'		=> $report_id,
							'name'          => $resource['name'],
							'resource_id' 	=> $resource['resource_id'],
							'qty' 			=> $resource['qty']
							);
						$this->EE->db->insert('wr_resources', $data);
					}

					// Make wr_items entries
					$sales_items = $this->EE->input->post('sales_items'); # needs to be 'sales_items'
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
						}
					}
				} else { // the entry is already uniquely in the DB

					$data = array(
						'submitter_id'			=> $employee['id'], #should be employee name
						'execution_datetime'	=> strtotime($this->EE->input->post('execution_date')),
						'submission_datetime'   => time(),
						'status'				=> $status,
						// 'company_id'			=> $this->EE->input->post('company_id'), #AKA DATAAREAID
						// 'cost_center'			=> $this->EE->input->post('cost_center'), #AKA DIMENSION2_
						// 'customer_name'			=> $this->EE->input->post('customer_name'),
						// 'customer_id'			=> $this->EE->input->post('customer_id'),
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

					// Update wr_resources entries
					$resources_form = $this->EE->input->post('resources');

					foreach($resources_form as $resource) {
                        $this->EE->db->select('resource_id');
                        $this->EE->db->from('wr_resources');
                        $this->EE->db->where('report_id', $report_id);
                        $this->EE->db->where('resource_id', $resource['resource_id']);

						if($this->EE->db->count_all_results() > 0){
							$data = array(
								'qty' 			=> $resource['qty']
								);
							$this->EE->db->where('report_id', $report_id );
							$this->EE->db->where('resource_id', $resource['resource_id']);
							$this->EE->db->update('wr_resources', $data);
						} else {
							$data = array(
								'report_id'		=> $report_id,
								'name'          => $resource['name'],
								'resource_id' 	=> $resource['resource_id'],
								'qty' 			=> $resource['qty']
							);
							$this->EE->db->insert('wr_resources', $data);
						}
					}

					// Make wr_items entries
					$sales_items_form = $this->EE->input->post('sales_items');
					foreach($sales_items_form as $item) {
						$this->EE->db->select('item_id, dimension_id');
						$this->EE->db->from('wr_items');
						$this->EE->db->where('report_id', $report_id);
						$this->EE->db->where('item_id', $item['item_id']);
						$this->EE->db->where('dimension_id', $item['dimension_id']);
						$items_db = $this->EE->db->count_all_results();


						if($items_db > 1) {
							$data = array(
								'qty'           => $item['qty']
							);
							$this->EE->db->where('report_id', $report_id );
							$this->EE->db->where('item_id', $item['item_id']);
							$this->EE->db->update('wr_items', $data);
						} else {
							$data = array(
								'report_id' 	=> $report_id,
								'name' 			=> $item['name'],
								'dimension_id'  => $item['dimension_id'],
								'item_id' 		=> $item['item_id'],
								'qty'           => $item['qty'],
								'unit'			=> $item['unit']
							);
							$this->EE->db->insert('wr_items', $data);
						}
					}
					
					// Not every report uses materials
					if(isset($materials) ) {
						// Make wr_materials entries
						$materials_form = $this->EE->input->post('materials');
						foreach($materials_form as $material) {
							$this->EE->db->select('item_id, dimension_id');
							$this->EE->db->from('wr_materials');
							$this->EE->db->where('report_id', $report_id);
							$this->EE->db->where('item_id', $item['item_id']);
							$this->EE->db->where('dimension_id', $item['dimension_id'] );
							$materials_db = $this->EE->db->count_all_results();
						
							if($materials_db > 1) {
								$data = array(
									'qty'				=> $material['qty']
								);
								$this->EE->db->where('report_id', $report_id );
								$this->EE->db->where('item_id', $item['item_id']);
								$this->EE->db->update('wr_materials', $data);
							}
						}
					}
				}
			} 
			if(in_array(0, $success)) {
				show_error('error submitting work report');

				// Delete all records where report_id
			} else {
				// $this->EE->axapta->set_approval($this->EE->input->post('projid'), $this->EE->input->post('DataAreaID'), $employee['id']);
				if($status == 4) {
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