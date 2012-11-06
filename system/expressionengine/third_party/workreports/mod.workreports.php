<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Workreports {
	protected $return_data = '';

	function __construct() {
		$this->EE =& get_instance();
		$this->EE->load->library('axapta/axapta');
		$this->EE->load->library('mysql');
	}

	/*
	* Creates a PDF document an either prints it to the screen or emails it to the customer, depending on how it was called.
	* @param $how - How the function was called.
	*/
	function wrPDF($project_id=NULL) {
		$this->EE->load->library('Template', NULL, 'TMPL');
		include_once(__DIR__.'/libraries/WKPDF.php');
		$pdf = new WKPDF();
		
		// Get work report data array, parse corresponding template, and generate HTML from template + data
		$data = $this->wrData($project_id);
		$template = $this->EE->TMPL->fetch_template('workreports', 'print', FALSE, $this->EE->config->item('site_id') ); 
		$template = $this->EE->TMPL->parse_variables($template, $data);

		$this->EE->TMPL->parse($template, FALSE, $this->EE->config->item('site_id'));

		// Create PDF
		$pdf->set_title('WorkReport');
		$pdf->set_html($this->EE->TMPL->final_template);
		$pdf->render();
		$pdf->output(WKPDF::$PDF_SAVEFILE, 'TEST.pdf');

		// Send email with PDF attachment 
		# TODO: $to = customer_contact_email
		# TODO: Check if customer_contact_email is valid before sending 
		// if(! is_null($data[0]['customer_contact_email']) ) {
		// 	$to = $data[0]['customer_contact_email'];
		// }

		$to = 'Robert.McCann@applusrtd.com'; // , Bert.Weber@applusrtd.com';
		$file = FCPATH.'tmp/TEST.pdf';
				
		if( $this->send_mail($to, $file) ) {
			// Message successfully sent! Delete local file from /tmp
			$pdf->delete_file($file);
		} else {
			echo 'Error sending email.';
		}
	}

	/*
	* Emails the customer a work report PDF
	*/
	function send_mail($to, $file) {
		// require_once('Mail.php'); 
		// require_once('Mail/mime.php'); 
		// $mime = new Mail_mime();

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
			$this->EE->config->set_item('compress_output', FALSE);

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
                    $options = array_merge($options, array(
                        'email' => $this->EE->session->userdata('email')
                        //'email' => 'bert.weber@applusrtd.com'
                    ));
				    $return_data = $this->EE->axapta->employee->get_remote( $options );
					break;

				case 'company':
                    $options = array_merge($options, array(
                        'id' => $employee['company_id']
                    ));
					$return_data = $this->EE->axapta->company->get_remote( $options );
					break;

				case 'cost_center':
                    $options = array_merge($options, array(
                        'id' => $employee['cost_center_id'], 
                        'company_id' => $employee['company_id']
                    ));
					$return_data = $this->EE->axapta->cost_center->get_remote( $options );
					break;

				case 'customer':
                    $options = array_merge($options, array(
                        'company_id' => $employee['company_id'],
						'department_id' => $employee['department_id'],
						'cost_center_id' => $employee['cost_center_id'],
						'blocked' => 0
                    ));
					$return_data = $this->EE->axapta->customer->get_remote( $options );
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
						//, 'id' => '107..SYB2001377'
					));
					$return_data = $this->EE->axapta->contact_person->get_remote( $options );
					break;

				case 'work_report':
					$options = array_merge($options, array(
						'project_id' => '07.005541/001/121013'
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
                    $options = array_merge($options, array(
                        //'project_id' => '07.005532/001/120820'
                    ));
					$return_data = $this->EE->axapta->resources->get_remote( $options );
					break;

				case 'resources':
					$options = array_merge($options, array(
						//'company_id' => $employee['company_id'],
						// 'department_id' => $employee['department_id'],
						'status' => 1
					));
					$return_data = $this->EE->axapta->resources->get_remote( $options );
					break;

				case 'materials':
					$options = array(
						//'project_id' => '07.003464/142/120802'
					);
					$return_data = $this->EE->axapta->materials->get_remote( $options );
					break;

				case 'sales_items':
                    $options = array_merge($options, array(
                        //'project_id' => '07.005532/001/120820'
                    ));
					$return_data = $this->EE->axapta->sales_items->get_remote( $options );
					break;

				case 'contract_items':
    				$options = array_merge($options, array(
    					// 'contract_id' => '900.001975',
    					// 'film_indicator' => 0
    				));
					$return_data = $this->EE->axapta->contract_items->get_remote( $options );
					break;

				case 'dispatch_list':
                    $options = array_merge($options, array(
                        'employee_id' => $employee['id']
                    ));
					$return_data = $this->EE->axapta->dispatch_list->get_remote( $options );
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

			// if($return_data) {
				switch ($output) {
					default:
					case 'json':
						header('Cache-Control: no-cache, must-revalidate');
						header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
						header('Content-type: application/json; charset=utf-8');
						echo json_encode($return_data);
					break;
					
					/*
					case 'xml':
						//should return an XML document;
					break;
					*/

					case 'debug':
						header('Content-Type: text/html; charset=utf-8');
						echo '<pre>';
						print_r($return_data);
						echo '</pre>';
					break;
				}
			// } else {
			// 	echo '<h1>error</h1></br>';
			// 	echo '<p>no return data</p>';
			// 	//return 404;
			// }
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

				//$existing_wr = $this->EE->db->result_array();

				//if($this->EE->db->count_all_results() == 0 || $existing_wr['modified_datetime'] < $dispatch_item['modified_datetime']) {
				if($this->EE->db->count_all_results() == 0) {
				
					//get workreport from axapta and add to mysql
					$work_report = $this->EE->axapta->work_report->get_remote( array( 'project_id' => $dispatch_item['project_id'] ) );
					
					// Insert each entry to the MySQL database
					$data = array(
						'project_id' 				=> $work_report[0]['project_id'],
			            'sales_id' 					=> $work_report[0]['sales_id'],
			            //'submitter_id' 			=> $employee_id,

			            'customer_id' 				=> $work_report[0]['customer_id'],
			            //'customer_name' 			=> $work_report[0]['customer_name'],
			            'customer_reference' 		=> $work_report[0]['customer_reference'],

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

			            //'review_norm_id' 			=> $work_report[0]['review_norm_id'],  //missing from mysql :(
			           	'review_procedure_id' 		=> $work_report[0]['review_procedure_id'],
			            'review_spec_id' 			=> $work_report[0]['review_spec_id'],

			            'status' 					=> 1,
			            'export_reason' 	    	=> $work_report[0]['export_reason'],

			            'created_by' 				=> $work_report[0]['created_by'],
			            'modified_by' 				=> $work_report[0]['modified_by'],
			            'modified_datetime' 		=> $work_report[0]['modified_datetime'],
			            'created_datetime' 			=> $work_report[0]['created_datetime'],
			            'execution_datetime' 		=> $work_report[0]['execution_datetime']
						);

					if( $customer = $this->EE->axapta->customer->get_remote(array( 'id' => $work_report[0]['customer_id'] ) )){
						$data = array_merge($data, array(
							'customer_name'             => $customer[0]['name'],
				            'customer_address' 			=> $customer[0]['address'],
				            'customer_phone' 			=> $customer[0]['phone'],
				            //'customer_email' 			=> $customer[0]['email'],
				            'customer_fax' 				=> $customer[0]['fax']
			            ));
					}

					if( $customer_contact = $this->EE->axapta->contact_person->get_remote( array( 'id' => $work_report[0]['customer_contact_person_id'] ) )){
						$data = array_merge($data, array(
				            'customer_contact_name' 	=> $customer_contact[0]['name'],
				            'customer_contact_email' 	=> $customer_contact[0]['email'],
				            'customer_contact_phone' 	=> $customer_contact[0]['phone'],
				            'customer_contact_mobile' 	=> $customer_contact[0]['cell_phone']
			            ));
					}
					
					// if( $team_contact = $this->EE->axapta->contact_person->get_remote( array( 'id' => $work_report[0]['team_contact_person_id'] ) )){
					// 	array_merge($data, array(
					// 		'team_contact_name' 		=> $team_contact[0]['name'],
					// 		'team_contact_email' 		=> $team_contact[0]['email'],
					// 		'team_contact_phone' 		=> $team_contact[0]['phone'],
					// 		'team_contact_mobile' 		=> $team_contact[0]['cell_phone']
					// 	));
					// }

					$this->EE->db->insert('wr_reports', $data);
					$report_id = $this->EE->db->insert_id();

					if( !$this->EE->db->affected_rows() == count($work_report) ){
						// WE HAD A PROBLEM, DELETED EVERYTHING AND SHOW ERROR
					}

					//get resources from axapta and add to mysql
					$resources = $this->EE->axapta->project_resources->get_remote( array( 'project_id' => $dispatch_item['project_id'] ) );

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
					if( array_key_exists('WA TECH', $employee['groups']) ){
						$message .= 'You have '.$this->count($employee, 'WA TECH').' Work Reports assigned to you'.'<br>';
						//$message = $this->EE->lang->line('');
					}
					if( array_key_exists('WA DISP', $employee['groups']) ){
						$message .= 'You have '.$this->count($employee, 'WA DISP').' Work Reports awaiting DISPATCHER approval'.'<br>';
					}
					if( array_key_exists('WA ADMIN', $employee['groups']) ){
						$message .= 'You have '.$this->count($employee, 'WA ADMIN').' Work Reports awaiting ADMIN approval'.'<br>';
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
		//$this->EE->db->select('*');
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
				$this->EE->db->or_where('company_id', $employee['groups']['WA ADMIN']);
				//$this->EE->db->where('department_id', $employee['department_id']);
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

	/*
	* Fetches data for a single work report. 
	* Used in wrDetails() and wrPDF()
	*/
	function wrData($project_id=NULL) {
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

		$data[0]['materials'] = $this->EE->db->get_where('wr_materials', array('report_id' => $data[0]['id']) )->result_array();
		$data[0]['sales_items'] = $this->EE->db->get_where('wr_items', array('report_id' => $data[0]['id']) )->result_array();

		if ( $data[0]['export_reason'] == 'TEMPLATE' ){
			$data[0]['resources'][0]['resource_id'] = $employee['id'];
			$data[0]['resources'][0]['name'] = $employee['name'];
		}else {
			$data[0]['resources'] = $this->EE->db->get_where('wr_resources', array('report_id' => $data[0]['id']) )->result_array();
		}
		return $data;
	}

	function wrDetails() {
		$employee = $this->EE->axapta->employee->get_remote( array('email' => $this->EE->session->userdata('email')) );
		$employee = $employee[0];

		$project_id = str_replace('-', '/', $this->EE->TMPL->fetch_param('projid') );

		$submit_uri = $this->EE->functions->fetch_site_index(0, 0).QUERY_MARKER.'ACT='.$this->EE->functions->fetch_action_id('Workreports', 'submit_for_approval');

		$data = $this->wrData($project_id);
		
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
				if( (array_key_exists('WA DISP', $employee['groups']) && in_array($data[0]['company_id'], $employee['groups']['WA DISP'])) 
					|| (array_key_exists('WA ADMIN', $employee['groups']) && in_array($data[0]['company_id'], $employee['groups']['WA ADMIN'])) ){				// Save button
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
	}

	function contract_items() {
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
	 *  Posts a work report to the MySQL database for supervisor/admin approval
	 */ 
	function submit_for_approval() {
		/* 
		 *  Get the employee object for the currently logged in user.  If invalid object returned, fail out of this process
		 *  @TODO redirect to login screen upon failing to get valid user object
		 */
		if ( $employee = $this->EE->axapta->employee->get_remote(array( 'email' => $this->EE->session->userdata('email') )) ) {
			$employee = $employee[0];
			//$success = array();

			// Get the work report already in the cache (MySQL)
			$this->EE->db->where('project_id', $this->EE->input->post('project_id') );
			$existing_wr = $this->EE->db->get('wr_reports')->row_array();


			/*** Status Decision Tree **************************************************************
			 *  
			 *  Technitions with Dispatcher and/or Admin are able to "short cut" a seperate approval step
			 *  Also, status of approval will be maintained if work report is saved
			 *  while always updating status to inprogress if not yet completed by tech
			 */

			if ($existing_wr['status'] < 2 && $this->EE->input->post('save') ) {
				$status = 2;
			} else {
				$status = $existing_wr['status'];
			}

			if ( $this->EE->input->post('submit') || $this->EE->input->post('approve') ) {
				if(array_key_exists('WA TECH', $employee['groups']) && in_array($this->EE->input->post('company_id'), $employee['groups']['WA TECH'])) {
					$status = 3;
				}
				if(array_key_exists('WA DISP', $employee['groups']) && in_array($this->EE->input->post('company_id'), $employee['groups']['WA DISP'])) {
					$status = 4;
				}
				if(array_key_exists('WA ADMIN', $employee['groups']) && in_array($this->EE->input->post('company_id'), $employee['groups']['WA ADMIN'])) {
					$status = 5;
				}
			} elseif ( $this->EE->input->post('reject') ) { // if "Reject" was clicked
				$status = 0;
			}

			/*** Work Report Form Data ************************************************************************
			 *
			 *  Begin by building the basic data array that will be used regarless if update or new from template
			 *  These are the fields that are always updatable
			 */
			$data = array(
				'submitter_id'			=> $employee['id'],
				'execution_datetime'	=> strtotime($this->EE->input->post('execution_date')),
				'submission_datetime'   => time(),
				'status'				=> $status,

				'rtd_reference'			=> $this->EE->input->post('rtd_reference'),

				'customer_reference' 	=> $this->EE->input->post('customer_reference'),

				'object_description'	=> $this->EE->input->post('object_description'),
				'order_description'     => $this->EE->input->post('order_description'),
				'remarks'               => $this->EE->input->post('remarks')
			);

			/*
			 *  These are the fields that could be updated, but if they are not, we need to preserve the existing data in MySQL
			 */
			// CONTACT PERSON
			if( $this->EE->input->post('customer_contact_id') ){
				$data = array_merge($data, array(
					'customer_contact_id'     => $this->EE->input->post('customer_contact_id'),
					'customer_contact_name'   => $this->EE->input->post('customer_contact_name'),
					'customer_contact_phone'  => $this->EE->input->post('customer_contact_phone'),
					'customer_contact_mobile' => $this->EE->input->post('customer_contact_cell'),
					'customer_contact_email'  => $this->EE->input->post('customer_contact_email')
				));
			}
			// WORK LOCATION
			if( $this->EE->input->post('work_location_id') ){
				$data = array_merge($data, array(
					'work_location_id'      => $this->EE->input->post('work_location_id'),
					'work_location_name'      => $this->EE->input->post('work_location_name'),
					'work_location_address' => $this->EE->input->post('work_location_address')
				));
			}

			/*** Form Actions / Data Processing *****************************************************************
			 *
			 *  Now that we have the data from the form, let's see if we're gonna make a new report based on a template
			 *  OR update the data in an existing report
			 */
			if($existing_wr['export_reason'] == 'TEMPLATE'){
				/*
				 *  NEW FROM TEMPLATE
				 *  Begin by building a new project_id (>= 8 char long)
				 */

				echo '<pre>TEMPLATE:<br>'; print_r($data); echo '</pre>';

				/*
				 *  project_id parts 
				 *  [0] => order / [1] => work_order / [2] => work_report
				 */
				$project_id_parts = explode( '/', $existing_wr['project_id'] );

				/*
				 *  Old work_report_id algorithm
				 *  Builds Last 4 of employee_id + day # of year (DDD) + sequence #
				 *  ex: 02262911
				 */

				/*
				 *  get sequence # -> number of reports since 12 A.M. of current day submitting with same customer/order
				 */
				// $this->EE->db->from( 'wr_reports' );
				// $this->EE->db->where( 'submitter_id', $employee['id'] );
				// $this->EE->db->like( 'project_id', $project_id_parts[0].'/'.$project_id_parts[1], 'after' );
				// $sequence_id = $this->EE->db->count_all_results()+1;

				// $employee_id_parts = explode('.', $employee['id']);// $employee_id[2] will be section 1 of $project_id
				// $day_of_year = str_pad( date('z'), 3, '0', STR_PAD_LEFT );
				// $project_id[2] = $employee_id[2].$date['yday'].$sequence_id;

				/*
				 *  New/better algorithm for unique work_report_id
				 *  Take crc32 hash of time and employee id and
				 *  replace 3rd segment of project_id w/ new work_report_id
				 */
				$project_id_parts[2] = crc32( $employee['id'].time() );
				$project_id = implode('/', $project_id_parts);

				/*
				 *  Ammend $data with some additional stuff we need only if it's a template
				 *  First Merge $exsisting_wr with $data from form
				 *  Then ammend project_id and any other values necessary only for templates
				 */
				$data = array_merge($existing_wr, $data, array(
					'project_id'    => $project_id, //new project id
					'export_reason' => NULL         //make sure this isn't treated as a template in the future
				));

				$this->EE->db->insert('wr_reports', $data);
				$success['wr_reports'] = $this->EE->db->affected_rows();

				$report_id = $this->EE->db->insert_id();
			} else {
				/*
				 *  Update Existing Work Report
				 *	First set $report_id to the existing report
				 */

				//echo '<pre>UPDATE:<br>'; print_r($data); echo '</pre>';

				$report_id = $existing_wr['id'];

				$this->EE->db->where('id', $report_id );
				$this->EE->db->update('wr_reports', $data);
				$success['wr_reports'] = $this->EE->db->affected_rows();
			}

			/*** Resources ***************************************************************
			 *
			 *  Update existing or insert new wr_resources entries
			 *  Because we test if the resource already exists for the $report_id
			 *  there is no need to treat template differently than update
			 */
			foreach($this->EE->input->post('resources') as $resource) {
				/*
				 *  First check if the quantity was actually updated to save some processing
				 */
				if( $resource['qty'] ){

					//echo '<pre>RESOURCE QTY CHANGED:<br>'; print_r($resource); echo '</pre>';

					/*
					 *  Then check if resource already exists for $report_id
					 */
					$this->EE->db->select('resource_id');
					$this->EE->db->from('wr_resources');
					$this->EE->db->where('report_id', $report_id);
					$this->EE->db->where('resource_id', $resource['resource_id']);

					$count = $this->EE->db->count_all_results();

					if( $count == 1 ){
						/*
						 *  The Resource DOES exist already, so just update the qty
						 */
						$data = array(
							'qty' => $resource['qty']
						);
						$this->EE->db->where('report_id', $report_id );
						$this->EE->db->where('resource_id', $resource['resource_id']);
						$this->EE->db->update('wr_resources', $data);
					} elseif( $count == 0 ) {
						/*
						 *  The Resource DOES NOT exist already, so insert new
						 *  This should always be triggered if from TEMPLATE
						 */
						$data = array(
							'report_id'		=> $report_id,
							'name'          => $resource['name'],
							'resource_id' 	=> $resource['resource_id'],
							'qty' 			=> $resource['qty']
						);
						$this->EE->db->insert('wr_resources', $data);
					} else {
						/*
						 *  There must be multiple resources existing with the same resource_id and thats' bad :(
						 *  Lets not do anything to the database just to be safe
						 @TODO Error trap this
						 */
					}
				}
			}

			/*** Sales Items ***************************************************************
			 *
			 *	Basically same process as resources
			 */
			foreach($this->EE->input->post('sales_items') as $item) {
				if( $item['qty'] ) {

					//echo '<pre>SALES ITEM QTY CHANGED:<br>'; print_r($item); echo '</pre>';

					$this->EE->db->select('item_id, dimension_id');
					$this->EE->db->from('wr_items');
					$this->EE->db->where('report_id', $report_id);
					$this->EE->db->where('item_id', $item['item_id']);
					$this->EE->db->where('dimension_id', $item['dimension_id']);

					$count = $this->EE->db->count_all_results();

					if($count == 1) {
						//UPDATE
						$data = array(
							'qty' => $item['qty']
						);
						$this->EE->db->where('report_id', $report_id );
						$this->EE->db->where('item_id', $item['item_id']);
						$this->EE->db->update('wr_items', $data);
					} elseif( $count == 0 ) {
						//INSERT
						$data = array(
							'report_id' 	=> $report_id,
							'dimension_id'  => $item['dimension_id'],
							'item_id' 		=> $item['item_id'],
							'name' 			=> $item['name'],
							'qty'           => $item['qty'],
							'unit'			=> $item['unit']
						);
						$this->EE->db->insert('wr_items', $data);
					} else {
						/*
						 *  There must be multiple resources existing with the same resource_id and thats' bad :(
						 *  Lets not do anything to the database just to be safe
						 @TODO Error trap this
						 */
					}
				}
			}
			
			/*** Materials/Consumables ********************************************************
			 *
			 *	Basically same process as resources except not every work report has materials
			 */
			if( is_array($this->EE->input->post('materials')) ) {
				foreach($this->EE->input->post('materials') as $material) {
					if( $material['qty'] ){

						//echo '<pre>MATERIAL QTY CHANGED:<br>'; print_r($material); echo '</pre>';
						$this->EE->db->select('item_id, dimension_id');
						$this->EE->db->from('wr_materials');
						$this->EE->db->where('report_id', $report_id);
						$this->EE->db->where('item_id', $material['item_id']);
						$this->EE->db->where('dimension_id', $material['dimension_id'] );

						$count = $this->EE->db->count_all_results();
					
						if($count == 1) {
							//UPDATE
							$data = array(
								'qty' => $material['qty']
							);
							$this->EE->db->where('report_id', $report_id);
							$this->EE->db->where('item_id', $material['item_id']);
							$this->EE->db->where('dimension_id', $material['dimension_id'] );
							$this->EE->db->update('wr_materials', $data);
						} elseif( $count == 0 ) {
							//INSERT
							$data = array(
								'report_id' 	=> $report_id,
								'dimension_id'  => $material['dimension_id'],
								'item_id' 		=> $material['item_id'],
								'name' 			=> $material['name'],
								'qty'           => $material['qty'],
								'unit'			=> $material['unit']
							);
							$this->EE->db->insert('wr_items', $data);
						} else {
							/*
							 *  There must be multiple resources existing with the same resource_id and thats' bad :(
							 *  Lets not do anything to the database just to be safe
							 @TODO Error trap this
							 */
						}
					}
				}
			}

			/*
			 *  We're almost done.
			 *  First, check if the work report has admin approval status
			 *	If so, we need to export XML, set ax_status/approval == 1 and send a PDF if the customer contact has an email address.
			 */
			if($status == 5) {
				$this->EE->mysql->create_xml($report_id);
				$this->EE->axapta->work_report->set_approval(array(
					'status' => 1,
					'project_id' => ($project_id) ? $project_id : $existing_wr['project_id'],
				    'employee_id' => $employee['id'],
				    'company_id' => $existing_wr['company_id']
				));
			}

			// If WA DISP approved, send an email 
			if($status >= 2) {	
				$this->wrPDF( $this->EE->input->post('project_id') );
			}

			$this->EE->output->show_message(array(
				'title'   => 'Information Accepted',
				'heading' => 'Thank you',
				'content' => 'Sucessfully submitted work report.',
				'link'    => array($this->EE->functions->form_backtrack('0'), 'Return to Work Reports')
			));

			return TRUE;
		}
	}
}// END CLASS

/* End of file mod.workreports.php */
/* Location: ./system/expressionengine/third_party/modules/workreports/mod.workreports.php */