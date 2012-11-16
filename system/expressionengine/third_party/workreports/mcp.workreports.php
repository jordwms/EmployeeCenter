<? if (!defined('BASEPATH')) exit('No direct script access allowed');

class Workreports_mcp {

	var $emplID;
	var $mod_uri_base;

	function __construct() {
		$this->EE =& get_instance();
		$this->EE->load->library('javascript');
		$this->EE->load->library('axapta');

		$this->mod_uri_base = 'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=workreports';
		
		// Set page title
		$this->EE->cp->set_variable('cp_page_title', $this->EE->lang->line('workreports_module_name'));

		$this->check_authorization();
	}

	function check_authorization($area = NULL) {
		if( $this->EE->axapta->axapta_connection() ) {
			if( $employee = $this->EE->axapta->employee() ) {
				$authorized_areas = array();
				foreach ($employee['groups'] as $company) {
					if( in_array('WA DISP', $company)  ) {
						//$authorized_areas['open']      = BASE.AMP.$this->mod_uri_base.AMP.'method=open';
						$authorized_areas['submitted'] = BASE.AMP.$this->mod_uri_base.AMP.'method=submitted';
					} 
					if( in_array('WA ADMIN', $company)  ) {
						//$authorized_areas['open']      = BASE.AMP.$this->mod_uri_base.AMP.'method=open';
						$authorized_areas['submitted'] = BASE.AMP.$this->mod_uri_base.AMP.'method=submitted';
						$authorized_areas['pending']   = BASE.AMP.$this->mod_uri_base.AMP.'method=pending';
						$authorized_areas['history']   = BASE.AMP.$this->mod_uri_base.AMP.'method=history';
					}
				}

				if( $area && array_key_exists($area, $authorized_areas) ){
					return;
				} elseif( !$area && count($authorized_areas) > 0 ){
					$this->EE->cp->set_right_nav($authorized_areas);
					return;
				} else {
					show_error( $this->EE->lang->line('unauthorized') );
				}
			} else {
				show_error( $this->EE->lang->line('invalid_employee') );
			}
		} else {
			show_error( $this->EE->lang->line('no_connection') );
		}
	}

	/*
	* For the index page of the webapp. 
	*/
	function index() {
		return $this->submitted();
	}

	/*
	* List of all tickets that have not yet been submitted for the day.
	*/
	function open() {
		$this->check_authorization('open');

		$this->EE->cp->set_variable('cp_page_title', $this->EE->lang->line('workreports_open'));
		$this->EE->cp->set_breadcrumb(BASE.AMP.$this->mod_uri_base, $this->EE->lang->line('workreports_module_name'));	

		$this->EE->load->library('table');
		$vars['company_info_header'] = array( 'table_open'  => '<table class="mainTable" name="work_report" border="0" cellspacing="0" cellpadding="0">' );
		$vars['help_message'] = lang('open_help_message');
		$vars['reports'] = array();

		if ( ($employee = $this->EE->axapta->employee_info()) && $ax_conn = $this->EE->axapta->axapta_connection() ) {
			$reports = $ax_conn->prepare(
				"SELECT
					RTDEMPLPERWORKREPORT.PROJID                        AS project_id,
					CUSTTABLE.NAME                                     AS customer_name,
					SALESTABLE.RTDPROJORDERREFERENCE                   AS rtd_reference,
					SALESTABLE.RTDOBJECTDESCRIPTION                    AS object_description,
					DATEDIFF(s, '1970-01-01', SALESTABLE.DELIVERYDATE) AS execution_date,
					SALESTABLE.DELIVERYNAME                            AS work_location_name,
					SALESTABLE.CUSTOMERREF                             AS customer_reference,
					CONTACTPERSON.name                                 AS contact_person
				FROM RTDEMPLPERWORKREPORT
				LEFT JOIN SALESTABLE              ON RTDEMPLPERWORKREPORT.PROJID = SALESTABLE.PROJID             AND SALESTABLE.DATAAREAID        = RTDEMPLPERWORKREPORT.DATAAREAID
				LEFT JOIN CUSTTABLE               ON SALESTABLE.CUSTACCOUNT      = CUSTTABLE.ACCOUNTNUM          AND CUSTTABLE.DATAAREAID         = RTDEMPLPERWORKREPORT.DATAAREAID
				LEFT JOIN EMPLTABLE               ON EMPLTABLE.EMPLID            = RTDEMPLPERWORKREPORT.EMPLID   AND EMPLTABLE.DATAAREAID         = RTDEMPLPERWORKREPORT.DATAAREAID
				LEFT JOIN PROJTABLE AS WORKREPORT ON WORKREPORT.PROJID           = RTDEMPLPERWORKREPORT.PROJID   AND WORKREPORT.DATAAREAID        = RTDEMPLPERWORKREPORT.DATAAREAID
				LEFT JOIN PROJTABLE AS WORKORDER  ON WORKORDER.PROJID            = WORKREPORT.PARENTID           AND WORKORDER.DATAAREAID         = RTDEMPLPERWORKREPORT.DATAAREAID
				LEFT JOIN RTDPROJORDERTABLE       ON RTDPROJORDERTABLE.PROJID    = WORKORDER.PARENTID            AND RTDPROJORDERTABLE.DATAAREAID = RTDEMPLPERWORKREPORT.DATAAREAID
				LEFT JOIN CONTACTPERSON           ON SALESTABLE.CONTACTPERSONID  = CONTACTPERSON.CONTACTPERSONID AND CONTACTPERSON.DATAAREAID     = RTDEMPLPERWORKREPORT.DATAAREAID
				WHERE 
					RTDEMPLPERWORKREPORT.DATAAREAID = :company
					AND SALESTABLE.RTDINVOICED = 0
					AND SALESTABLE.RTDAPPROVED = 0
					AND CUSTTABLE.BLOCKED = 0
					AND ( SALESTABLE.DeliveryDate >= dateadd(week,-1,getdate()) AND SALESTABLE.DeliveryDate <= dateadd(week,1,getdate()) )
					AND (RTDPROJORDERTABLE.PROJORDERSTATUS = '2' OR RTDPROJORDERTABLE.PROJORDERSTATUS = '4')
				ORDER BY SALESTABLE.DELIVERYDATE DESC"
			);
			$reports->bindValue(':company', $employee['company'], PDO::PARAM_STR);
			$reports->setFetchMode(PDO::FETCH_NAMED);
			$reports->execute();
			
			$vars['reports'] = $reports->fetchAll();

			foreach($vars['reports'] as &$report) {
				$report['status'] = 3; // Showing in the view that this ticket is still open. 
			}

		} else {
			$vars['reports'] = NULL;
		}

		return $this->EE->load->view('report', $vars, TRUE);
	}

	/*
	* List of all tickets with a status of 0.
	*/
	function submitted() {
		$this->check_authorization('submitted');

		$this->EE->cp->set_variable('cp_page_title', $this->EE->lang->line('workreports_submitted'));
		$this->EE->cp->set_breadcrumb(BASE.AMP.$this->mod_uri_base, $this->EE->lang->line('workreports_module_name'));	

		// $this->EE->load->library('axapta');
		$this->EE->load->library('table');
		$vars['company_info_header'] = array(
			'table_open'  => '<table class="mainTable" name="work_report" border="0" cellspacing="0" cellpadding="0">'
			);

		$report_link = BASE.AMP.$this->mod_uri_base.AMP.'method=submitted_details'.AMP.'id=';
		// $vars['reports'] = $this->EE->db->get_where('wr_reports', array('status' => 0) )->result_array();
		$vars['reports'] = $this->EE->axapta->get_reports(NULL, 0);

		foreach ($vars['reports'] as &$report) {
			$report['project_id'] = $report['project_order_id'].'/'.$report['project_work_order_id'].'/'.$report['work_report'];
			$report['customer_name'] = '<a href="'.$report_link.$report['id'].'" >'.$report['customer_name'].'</a>';
			$report['execution_date'] = date('m-d-Y', $report['execution_date']);
			$report['submission_date'] = date("m-d-Y h:i A", $report['submission_date']);
		}
		$vars['help_message'] = lang('submitted_help_message');
		return $this->EE->load->view('report', $vars, TRUE);
	}

	/*
	* View preloader for reports on 'complete' status (2).
	* Renders a list of all work reports approved by an admin that had XML exported. 
	*/
	function history() {
		// Checking specific account info...
		// $var = $this->EE->axapta->customer(array('id' => '107.CUS000911')); 
		// echo "<pre>";
		// print_r($var);
		$this->check_authorization('history');

		$this->EE->cp->set_variable('cp_page_title', $this->EE->lang->line('workreports_history'));
		$this->EE->cp->set_breadcrumb(BASE.AMP.$this->mod_uri_base, $this->EE->lang->line('workreports_module_name'));	

		$this->EE->load->library('table');
		$vars['company_info_header'] = array(
			'table_open'  => '<table class="mainTable" name="work_report" border="0" cellspacing="0" cellpadding="0">'
			);

		// $vars['reports'] = $this->EE->db->get_where('wr_reports', array('status' => 2))->result_array();
		$vars['reports'] = $this->EE->axapta->get_reports(NULL,2);

		foreach ($vars['reports'] as &$report) {
			$report['project_id'] = $report['project_order_id'].'/'.$report['project_work_order_id'].'/'.$report['project_work_report_id'];
			$report['execution_date'] = date('m-d-Y', $report['execution_date']);
			$report['submission_date'] = date('m-d-Y h:i:s A', $report['submission_date']);
		}

		$vars['help_message'] = lang('history_help_message');

		return $this->EE->load->view('report', $vars, TRUE);
	}

	/*
	* Renders a list of all work reports approved by an admin that are ready for XML export. 
	*/
	function pending() {
		$this->check_authorization('pending');

		$this->EE->cp->set_variable('cp_page_title', $this->EE->lang->line('workreports_pending'));
		$this->EE->cp->set_breadcrumb(BASE.AMP.$this->mod_uri_base, $this->EE->lang->line('workreports_module_name'));	

		$this->EE->load->library('table');
		$vars['company_info_header'] = array(
			'table_open'  => '<table class="mainTable" name="work_report" border="0" cellspacing="0" cellpadding="0">'
			);

		// $vars['reports'] = $this->EE->db->get_where('wr_reports', array('status' => 1))->result_array();
		$vars['reports'] = $this->EE->axapta->get_reports(NULL, 1);

		$report_link = BASE.AMP.$this->mod_uri_base.AMP.'method=pending_details'.AMP.'id=';

		foreach ($vars['reports'] as &$report) {
			$report['customer_name'] = '<a href="'.$report_link.$report['id'].'" >'.$report['customer_name'].'</a>';
			$report['project_id'] = $report['project_order_id'].'/'.$report['project_work_order_id'].'/'.$report['project_work_report_id'];
			$report['execution_date'] = date('m/d/Y', $report['execution_date']);
			$report['submission_date'] = date('m/d/Y h:i:s A', $report['submission_date']);
		}

		$vars['help_message'] = lang('pending_help_message');
		return $this->EE->load->view('report', $vars, TRUE);
	}

	/*
	* View preloader for a report on 'complete' status (0) given a valid id. 
	* Loads details page for a single report with all correlating data for approval.
	*/
	function submitted_details() {
		$this->check_authorization('submitted');

		if( is_numeric($this->EE->input->get('id')) ) {
			$id = $this->EE->input->get('id');
		} else {
			show_error('Invalid id given.');
		}

		$this->EE->load->library('table');

		$this->EE->cp->set_variable('cp_page_title', $this->EE->lang->line('workreports_submitted_details'));
		$this->EE->cp->set_breadcrumb(BASE.AMP.$this->mod_uri_base, $this->EE->lang->line('workreports_module_name'));		

		$vars['report_link'] = BASE.AMP.$this->mod_uri_base.AMP.'method=submitted_details'.AMP.'id=';

		$vars['company_info_header'] = array('table_open'  => '<table class="mainTable" name="work_report" border="0" cellspacing="0" cellpadding="0">');
		$vars['items_header'] = array('table_open'  => '<table class="mainTable" name="work_report_items" border="0" cellspacing="0" cellpadding="0">');

		$vars['action_url']	 	 = $this->mod_uri_base.AMP.'method=set_val';
		$vars['form_hidden']	 = array('id' => $id, 'callback' => 'submitted');
		$vars['form_attributes'] = FALSE;

		$vars['mats']		= $this->EE->axapta->get_materials($id);
		$vars['items'] 		= $this->EE->axapta->get_items($id);
		$vars['report'] 	= $this->EE->axapta->get_reports($id);
		$vars['resources']	= $this->EE->axapta->get_resources($id);

		$delete_link = BASE.AMP.$this->mod_uri_base.AMP.'method=delete_report'.AMP.'id='.$vars['report']['id'].AMP.'callback=submitted';
		$vars['delete_button'] = '<a id="delete" href="'.$delete_link.'" ><input type="button" value="'.lang('delete').'"/></a>';
		$vars['reject_button'] = '<a id="reject" href="'.$delete_link.AMP.'reject=TRUE'.'" ><input type="button" value="'.lang('reject').'"/></a>';

		// if status is 'not approved' for work report, make it an authorize button
		if($vars['report']['status'] == 0) {
			$vars['report']['status'] = '<a href="'.BASE.AMP.$this->mod_uri_base.AMP.'method=set_val'.AMP.'company='.$vars['report']['copmany_id'].AMP.'id='.$id.AMP.'callback=submitted'.AMP.'status=1"><input type="button" value="'.lang('approve').'"></input></a>';
		} else { // mark as Approved
			$vars['report']['status'] = lang('approved');
		}

		return $this->EE->load->view('details', $vars, TRUE);
	}

	/*
	* View preloader for reports on 'pending' status (1). 
	* Loads details page for a single report with all correlating data for approval.
	*/
	function pending_details() {
		$this->check_authorization('pending');

		if( is_numeric($this->EE->input->get('id')) ) {
			$id = $this->EE->input->get('id');
		} else {
			show_error('Invalid id given.');
		}

		$this->EE->load->library('table');

		$this->EE->cp->set_variable('cp_page_title', $this->EE->lang->line('workreports_pending_details'));
		$this->EE->cp->set_breadcrumb(BASE.AMP.$this->mod_uri_base, $this->EE->lang->line('workreports_module_name'));		
	
		$vars['report_link'] = BASE.AMP.$this->mod_uri_base.AMP.'method=pending_details'.AMP.'id=';

		$vars['company_info_header'] = array('table_open'  => '<table class="mainTable" name="work_report" border="0" cellspacing="0" cellpadding="0">');
		$vars['items_header'] = array('table_open'  => '<table class="mainTable" name="work_report_items" border="0" cellspacing="0" cellpadding="0">');

		$vars['action_url']	 	 = $this->mod_uri_base.AMP.'method=set_val';
		$vars['form_hidden']	 = array('id' => $id, 'callback' => 'pending');
		$vars['form_attributes'] = FALSE;

		$vars['mats']		= $this->EE->axapta->get_materials($id);
		$vars['items'] 		= $this->EE->axapta->get_items($id);
		$vars['report'] 	= $this->EE->axapta->get_reports($id);
		$vars['resources']	= $this->EE->axapta->get_resources($id);

		$delete_link = BASE.AMP.$this->mod_uri_base.AMP.'method=delete_report'.AMP.'id='.$vars['report']['id'].AMP.'callback=pending';
		
		$vars['delete_button'] = '<a id="delete" href="'.$delete_link.'" ><input type="button" value="'.lang('delete').'"/></a>';
		$vars['reject_button'] = '<a id="reject" href="'.$delete_link.AMP.'reject=TRUE'.'" ><input type="button" value="'.lang('reject').'"/></a>';
		$vars['report']['project_id'] = $vars['report']['project_order_id'].'/'.$vars['report']['project_work_order_id'].'/'.$vars['report']['project_work_report_id'];

		// if status is 'not approved' for work report, make it an authorize button
		if($vars['report']['status'] == 1) {
			$vars['report']['status'] = '<a href="'.BASE.AMP.$this->mod_uri_base.AMP.'method=set_val'.AMP.'company='.$vars['report']['company_id'].AMP.'id='.$id.AMP.'callback=pending'.AMP.'status=2"><input type="button" value="'.lang('approve').'"></input></a>';
		} else { // mark as Approved
			$vars['report']['status'] = lang('approved');
		}

		return $this->EE->load->view('details', $vars, TRUE);
	}

	/*
	* Makes a call to various library functions from URL requests based on GET and POST arrays 
	# How can this be cleaned up? So many if-else stmts....
	*/
	function set_val() {
		if ( ($employee = $this->EE->axapta->employee_info()) && ($conn = $this->EE->axapta->axapta_connection()) ) {
			if (array_key_exists('status',$_GET) ) {
				$this->EE->axapta->set_status();
				$company = $this->EE->input->get('company');

				// If the submit was from the 'pending' page, or the user is both a tech and an admin
				if($this->EE->input->GET('callback') == 'pending' || in_array('WA ADMIN',$employee['groups'][$company]) ) {
					$result = $this->EE->axapta->create_xml();
					if($result) {
						$this->EE->session->set_flashdata('message_success', 'Report approved.');
					} else {
						$this->EE->session->set_flashdata('message_failure', "An error occured. Please notify your system admin.");
					}
					if ($this->EE->input->GET('callback') == 'pending') {
						return $this->EE->functions->redirect(BASE.AMP.$this->mod_uri_base.AMP.'method=pending'.AMP.'id='.$id);
					} else {
						return $this->EE->functions->redirect(BASE.AMP.$this->mod_uri_base.AMP.'method=submitted');
					}
				} else {
					$this->EE->session->set_flashdata('message_success', 'Report updated successfully.');
					return $this->EE->functions->redirect(BASE.AMP.$this->mod_uri_base.AMP.'method=submitted');
				}
			} else {
				$this->EE->axapta->set_report();

				$id = $this->EE->input->POST('id');
				$callback = $this->EE->input->POST('callback');
				
				$this->EE->session->set_flashdata('message_success', 'Report updated successfully.');

				if($callback == 'submitted') {
					return $this->EE->functions->redirect(BASE.AMP.$this->mod_uri_base.AMP.'method=submitted_details'.AMP.'id='.$id);
				} else {
					return $this->EE->functions->redirect(BASE.AMP.$this->mod_uri_base.AMP.'method=pending_details'.AMP.'id='.$id);
				}
			}
		}
	}

	/*
	* Looks from $_GET to delete a report from wr_reports and all corresponding data in other 'wr_' prefix tables.
	*/
	function delete_report($reject=FALSE) {
		if( is_numeric($id = $this->EE->input->GET('id')) ) {			
			$data = array('id' 	=> $id);

			$reject = $this->EE->input->GET('reject');			
			if($reject){
				$report = $this->EE->db->get_where('wr_reports', $data)->row();

				// check this for errors...
				$project_id = $report->order.'/'.$report->work_order.'/'.$report->work_report;

				$this->EE->axapta->set_approval($project_id, $report->company, $report->submitter_id, FALSE);
				
				return;
			}

			$this->EE->db->delete('wr_reports', $data);

			$data =array('report_id' => $id);
			$this->EE->db->delete('wr_items', $data);
			$this->EE->db->delete('wr_resources', $data);
			$this->EE->db->delete('wr_materials', $data);


			$this->EE->session->set_flashdata('message_success', 'Report deleted successfully.');
			if($this->EE->input->GET('callback') == 'submitted') {
				$this->EE->functions->redirect(BASE.AMP.$this->mod_uri_base.AMP.'method=submitted');
			} else {
				$this->EE->functions->redirect(BASE.AMP.$this->mod_uri_base.AMP.'method=pending');	
			}
		} else {
			show_error('Invalid id given.');
		}
	}

	/*
	* Brings up 2 XML reports with the same filename so the user can decide whether to overwrite the older file or make a unique file. 
	* @ 
	*/
	function compare($dir, $xml_doc, $file) {
		$this->EE->load->library('table');

		$this->EE->cp->set_variable('cp_page_title', $this->EE->lang->line('workreports_compare_reports'));
		$this->EE->cp->set_breadcrumb(BASE.AMP.$this->mod_uri_base, $this->EE->lang->line('workreports_module_name'));		

		$data['file'] = simplexml_load_file($dir.$file);
		$data['new_xml'] = simplexml_import_dom($xml_doc);
		
		return $this->EE->load->view('compare', $data, TRUE);

	}
}// END CLASS
 
/* End of file mcp.workreports.php */
/* Location: ./system/expressionengine/third_party/modules/workreports/mcp.workreports.php */