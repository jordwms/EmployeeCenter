<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Workreports {
	var $return_data = '';

	function __construct() {
		$this->EE =& get_instance();
		$this->EE->load->library('axapta');
	}

	function dashboard() {
		$message = '';
		if( $this->EE->axapta->axapta_connection() ) {
			if( $employee = $this->EE->axapta->employee() ) {
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
	function get() {
		if( $this->EE->session->userdata('email') && $this->EE->session->userdata('is_banned') == 0 ) {

			$method = $this->EE->input->get('method');
			$output = $this->EE->input->get('output');
			$data   = $this->EE->input->post('data');

			switch ($method) {
				case 'employee': //done
				    $return_data = $this->EE->axapta->employee(  );
					break;
				case 'company': //done
					$return_data = $this->EE->axapta->company(  );
					break;
				case 'cost_center': //done?
					$return_data = $this->EE->axapta->cost_center(  );
					break;
				case 'customer': //done
					$return_data = $this->EE->axapta->customer(  );
					break;
				case 'work_location': //done
					$return_data = $this->EE->axapta->work_location(  );
					break;
				case 'contact_person': //done
					$return_data = $this->EE->axapta->contact_person(array('id' => '107..SYB2001380'));
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

	// function wrCount() {
	// 	if ( ($employee = $this->EE->axapta->employee()) && ($conn = $this->EE->axapta->axapta_connection()) ) {
	// 		/*
	// 		 *  Work Reports Available to Employee
	// 		 */
	// 		$count = $conn->prepare(
	// 			"SELECT
	// 				COUNT(*) AS count
	// 			FROM RTDEMPLPERWORKREPORT
	// 			LEFT JOIN EMPLTABLE               ON EMPLTABLE.EMPLID             = RTDEMPLPERWORKREPORT.EMPLID  AND EMPLTABLE.DATAAREAID            = RTDEMPLPERWORKREPORT.DATAAREAID
	// 			LEFT JOIN SALESTABLE              ON RTDEMPLPERWORKREPORT.PROJID  = SALESTABLE.PROJID            AND SALESTABLE.DATAAREAID           = RTDEMPLPERWORKREPORT.DATAAREAID
	// 			LEFT JOIN CUSTTABLE               ON SALESTABLE.CUSTACCOUNT       = CUSTTABLE.ACCOUNTNUM         AND CUSTTABLE.DATAAREAID            = RTDEMPLPERWORKREPORT.DATAAREAID
	// 			LEFT JOIN PROJTABLE AS WORKREPORT ON WORKREPORT.PROJID            = RTDEMPLPERWORKREPORT.PROJID  AND WORKREPORT.DATAAREAID           = RTDEMPLPERWORKREPORT.DATAAREAID
	// 			LEFT JOIN PROJTABLE AS WORKORDER  ON WORKORDER.PROJID             = WORKREPORT.PARENTID          AND WORKORDER.DATAAREAID            = RTDEMPLPERWORKREPORT.DATAAREAID
	// 			LEFT JOIN RTDPROJORDERTABLE       ON RTDPROJORDERTABLE.PROJID     = WORKORDER.PARENTID           AND RTDPROJORDERTABLE.DATAAREAID    = RTDEMPLPERWORKREPORT.DATAAREAID

	// 			WHERE
	// 				RTDEMPLPERWORKREPORT.EMPLID = :emplid
	// 				AND EMPLTABLE.STATUS = 1
	// 				AND SALESTABLE.RTDINVOICED = 0
	// 				AND SALESTABLE.RTDAPPROVED = 0
	// 				AND CUSTTABLE.BLOCKED = 0
	// 				AND ( SALESTABLE.DeliveryDate >= dateadd(month,-1,getdate()) AND SALESTABLE.DeliveryDate <= dateadd(week,1,getdate()) )
	// 				AND (RTDPROJORDERTABLE.PROJORDERSTATUS = '2' OR RTDPROJORDERTABLE.PROJORDERSTATUS = '4')"
	// 		);
			
	// 		$count->bindParam(':emplid', $employee['id'], PDO::PARAM_STR, 12);
	// 		$count->setFetchMode(PDO::FETCH_NAMED);
	// 		$count->execute();

	// 		$return_val = $count->fetch();

	// 		return $return_val['count'];
	// 	} else {
	// 		return '0';
	// 	}
	// }

	/*
	 *  Work Reports Available to Employee
	 */
	// function wrList() {
	// 	if ( ($employee = $this->EE->axapta->employee()) && $ax_conn = $this->EE->axapta->axapta_connection() ) {
	// 		$tagdata = $this->EE->TMPL->tagdata;

	// 		$workReports = $ax_conn->prepare(
	// 			"SELECT
	// 				RTDEMPLPERWORKREPORT.EMPLID                        AS EmployeeID,
	// 				RTDEMPLPERWORKREPORT.SALESID                       AS SalesID,
	// 				RTDEMPLPERWORKREPORT.PROJID                        AS ProjectID,
	// 				REPLACE(RTDEMPLPERWORKREPORT.PROJID, '/', '-')     AS ProjectLink,
	// 				CUSTTABLE.NAME                                     AS CustomerName,
	// 				SALESTABLE.RTDPROJORDERREFERENCE                   AS RTDRef,
	// 				SALESTABLE.SALESNAME                               AS SalesName,
	// 				SALESTABLE.CUSTACCOUNT                             AS CustomerAccount,
	// 				SALESTABLE.INVOICEACCOUNT                          AS InvoiceAccount,

	// 				DATEDIFF(s, '1970-01-01', SALESTABLE.DELIVERYDATE) AS ExecDate,
	// 				SALESTABLE.DELIVERYDATE                            AS execution_date,
	// 				SALESTABLE.RTDSTARTTIME                            AS start_time,

	// 				SALESTABLE.DELIVERYNAME                            AS DeliveryName,
	// 				SALESTABLE.CUSTOMERREF                             AS CustomerRef,
	// 				CONTACTPERSON.name                                 AS CustomerContactPersonName,
	// 				CONTACTPERSON.email                                AS CustomerContactPersonEmail,
	// 				CONTACTPERSON.PHONE                                AS CustomerContactPersonPhone,
	// 				CONTACTPERSON.CELLULARPHONE                        AS CustomerContactPersonCellPhone,
	// 				RTDSTARTDATE                                       AS StartDate,
	// 				RTDSTARTTIME                                       AS StartTime,
	// 				RTDENDDATE                                         AS EndDate,
	// 				RTDENDTIME                                         AS EndTime
	// 			FROM RTDEMPLPERWORKREPORT
	// 			LEFT JOIN SALESTABLE                  ON RTDEMPLPERWORKREPORT.PROJID = SALESTABLE.PROJID             AND SALESTABLE.DATAAREAID        = RTDEMPLPERWORKREPORT.DATAAREAID
	// 			LEFT JOIN CUSTTABLE                   ON SALESTABLE.CUSTACCOUNT      = CUSTTABLE.ACCOUNTNUM          AND CUSTTABLE.DATAAREAID         = RTDEMPLPERWORKREPORT.DATAAREAID
	// 			LEFT JOIN EMPLTABLE                   ON EMPLTABLE.EMPLID            = RTDEMPLPERWORKREPORT.EMPLID   AND EMPLTABLE.DATAAREAID         = RTDEMPLPERWORKREPORT.DATAAREAID
	// 			LEFT JOIN CONTACTPERSON               ON SALESTABLE.CONTACTPERSONID  = CONTACTPERSON.CONTACTPERSONID AND CONTACTPERSON.DATAAREAID     = RTDEMPLPERWORKREPORT.DATAAREAID
	// 			LEFT JOIN PROJTABLE AS WORKREPORT     ON WORKREPORT.PROJID           = RTDEMPLPERWORKREPORT.PROJID   AND WORKREPORT.DATAAREAID        = RTDEMPLPERWORKREPORT.DATAAREAID
	// 			LEFT JOIN PROJTABLE AS WORKORDER      ON WORKORDER.PROJID            = WORKREPORT.PARENTID           AND WORKORDER.DATAAREAID         = RTDEMPLPERWORKREPORT.DATAAREAID
	// 			LEFT JOIN RTDPROJORDERTABLE           ON RTDPROJORDERTABLE.PROJID    = WORKORDER.PARENTID            AND RTDPROJORDERTABLE.DATAAREAID = RTDEMPLPERWORKREPORT.DATAAREAID

	// 			WHERE 
	// 				RTDEMPLPERWORKREPORT.EMPLID = :emplid
	// 				AND EMPLTABLE.STATUS = 1
	// 				AND SALESTABLE.RTDINVOICED = 0
	// 				AND SALESTABLE.RTDAPPROVED = 0
	// 				AND CUSTTABLE.BLOCKED = 0
	// 				AND ( SALESTABLE.DeliveryDate >= dateadd(month,-1,getdate()) AND SALESTABLE.DeliveryDate <= dateadd(week,1,getdate()) )
	// 				AND (RTDPROJORDERTABLE.PROJORDERSTATUS = '2' OR RTDPROJORDERTABLE.PROJORDERSTATUS = '4')
	// 			ORDER BY SALESTABLE.DELIVERYDATE DESC"
	// 		);
			
	// 		$workReports->bindParam(':emplid', $employee['id'], PDO::PARAM_STR, 12);
	// 		$workReports->setFetchMode(PDO::FETCH_NAMED);
	// 		$workReports->execute();

	// 		$return_data = $workReports->fetchAll();

	// 		//fix + add start time to scheduled execution date
	// 		foreach ($return_data as &$wr) {
	// 			$wr['start_datetime'] = strtotime($wr['execution_date']) + ($wr['start_time']/1000) + $this->EE->axapta->server_tzoffset;
	// 		}

	// 		$this->return_data = $this->EE->TMPL->parse_variables( $tagdata, $return_data);
			
	// 		return $this->return_data;
	// 	} else {
	// 		return;
	// 	}
	// }

	function wrList($projid = NULL) {
		// if ( ($employee = $this->EE->axapta->employee()) && $ax_conn = $this->EE->axapta->axapta_connection() ) {
		// 	if(is_null($projid)) {
		// 		$projid = explode( '/', $this->EE->input->post('projid') );
		// 	}
			// $employee = $this->EE->axapta->employee->get_remote( array('email' => $this->EE->session->userdata('email')) );
			$tagdata = $this->EE->TMPL->tagdata;

			$this->EE->db->select('
						crew_leader_id,
						sales_id,
						project_order_id,
						project_work_order_id,
						project_work_report_id,
						rtd_reference,
						sales_name,
						invoice_account,
						object_description,
						order_description,
						execution_date,
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
						// RTDEMPLPERWORKREPORT.EMPLID                        AS EmployeeID,
						// RTDEMPLPERWORKREPORT.SALESID                       AS SalesID,
						// RTDEMPLPERWORKREPORT.PROJID                        AS ProjectID,
						// CUSTTABLE.NAME                                     AS CustomerName,
						// SALESTABLE.RTDPROJORDERREFERENCE                   AS RTDRef,
						// SALESTABLE.SALESNAME                               AS SalesName,
						# SALESTABLE.CUSTACCOUNT                             AS CustomerAccount,
						// SALESTABLE.INVOICEACCOUNT                          AS InvoiceAccount,

						// SALESTABLE.DELIVERYDATE                            AS execution_date,
						# SALESTABLE.RTDSTARTTIME                            AS start_time,

						// SALESTABLE.DELIVERYNAME                            AS DeliveryName,
						// SALESTABLE.CUSTOMERREF                             AS CustomerRef,
						// CONTACTPERSON.name                                 AS CustomerContactPersonName,
						// CONTACTPERSON.email                                AS CustomerContactPersonEmail,
						// CONTACTPERSON.PHONE                                AS CustomerContactPersonPhone,
						// CONTACTPERSON.CELLULARPHONE                        AS CustomerContactPersonCellPhone,
						# RTDSTARTDATE                                       AS StartDate,
						# RTDSTARTTIME                                       AS StartTime,
						# RTDENDDATE                                         AS EndDate,
						# RTDENDTIME                                         AS EndTime

			$this->EE->db->from('wr_reports');
			// $this->EE->db->where('status', 0);
			// this->EE->db->where('crew_leader_id', $employee[0]);

			$report = $this->EE->db->get()->result_array();

			// $data['materials'] = $this->EE->axapta->materials->get();
			// $data['sales_item'] = $this->EE->axapta->sales_item->get();
			// $data['resources'] = $this->EE->axapta->resources->get();

			$this->return_data = $this->EE->TMPL->parse_variables( $tagdata,  $report);
			return $this->return_data;
		// }
	}
	
/*	function details_router($projid=NULL) {

		// Coming from a fresh report (not submitted for validation)
		if(empty($_POST)){
			if(is_null($projid)) {
				$projid = $this->EE->db->escape_str( $this->EE->TMPL->fetch_param('projid') );
			}
			$data = $this->wrDetails($projid);
			// print_r($data);
			$tagdata = $this->EE->TMPL->tagdata;
			// print_r($tagdata);
			$this->return_data = $this->EE->TMPL->parse_variables( $tagdata,  $data);
			return $this->return_data;
		} else { // if valid
			if( $this->wrValidate($_POST) ) {
				// Success!
				return $this->submit_for_approval();
			} else {
				// Re-render form with errors
				if(is_null($projid)) {
					$projid = $this->EE->db->escape_str( $this->EE->TMPL->fetch_param('projid') );
				}
				$data = $this->wrDetails($projid);
				$tagdata = $this->EE->TMPL->tagdata;
				$this->return_data = $this->EE->TMPL->parse_variables( $tagdata,  $data);
				return $this->return_data;
			}
		}
	}*/

	/*
	 *  Work Reports Details
	 */
	// function wrDetails($projid = NULL) {
	// 	if ( ($employee = $this->EE->axapta->employee()) && $ax_conn = $this->EE->axapta->axapta_connection() ) {
	// 		if(is_null($projid)) {
	// 			$projid = $this->EE->db->escape_str( $this->EE->TMPL->fetch_param('projid') );
	// 		}

	// 		$submit_uri = $this->EE->functions->fetch_site_index(0, 0).QUERY_MARKER.'ACT='.$this->EE->functions->fetch_action_id('Workreports', 'submit_for_approval');
	// 		// $submit_uri = $this->EE->functions->fetch_site_index(0,0).'workreports/details/'.$projid;
	// 		$data = array();

	// 		$workReport = $ax_conn->prepare(
	// 			"SELECT
	// 				RTDEMPLPERWORKREPORT.EMPLID                        AS EmployeeID,
	// 				LTRIM(RTDEMPLPERWORKREPORT.SALESID)                AS SalesID,
	// 				RTDEMPLPERWORKREPORT.PROJID                        AS ProjectID,
	// 				RTDEMPLPERWORKREPORT.DATAAREAID                    AS CompanyID,
	// 				SALESTABLE.RTDPROJORDERREFERENCE                   AS RTDRef,
	// 				SALESTABLE.SALESNAME                               AS SalesName,
	// 				SALESTABLE.INVOICEACCOUNT                          AS InvoiceAccount,
	// 				SALESTABLE.RTDOBJECTDESCRIPTION                    AS ObjectDescription,
	// 				SALESTABLE.RTDORDERDESCRIPTION                     AS OrderDescription,
	// 				DATEDIFF(s, '1970-01-01', SALESTABLE.DELIVERYDATE) AS ExecDate,
	// 				SALESTABLE.DELIVERYNAME                            AS DeliveryName,
	// 				SALESTABLE.DELIVERYADDRESS                         AS DeliveryAddress,
	// 				SALESTABLE.CUSTOMERREF                             AS CustomerRef,
	// 				RTDEMPLPERWORKREPORT.DATAAREAID                    AS DataAreaID,
	// 				EMPLTABLE.NAME                                     AS TeamContactPerson,
	// 				EMPLTABLE.ADDRESS                                  AS TeamContactPersonAddress,
	// 				EMPLTABLE.PHONE                                    AS TeamContactPersonPhone,
	// 				EMPLTABLE.TELEFAX                                  AS TeamContactPersonFax,
	// 				EMPLTABLE.EMAIL                                    AS TeamContactPersonEmail,
	// 				CUSTTABLE.NAME                                     AS CustomerName,
	// 				CUSTTABLE.ADDRESS                                  AS CustomerAddress,
	// 				CUSTTABLE.PHONE                                    AS CustomerPhone,
	// 				CUSTTABLE.TELEFAX                                  AS CustomerFax,
	// 				CUSTTABLE.EMAIL                                    AS CustomerEmail,
	// 				LTRIM(SALESTABLE.CUSTACCOUNT)                      AS CustomerAccount,
	// 				SALESTABLE.CONTACTPERSONID                         AS CustomerContactPersonID,
	// 				CONTACTPERSON.name                                 AS CustomerContactPersonName,
	// 				CONTACTPERSON.email                                AS CustomerContactPersonEmail,
	// 				CONTACTPERSON.PHONE                                AS CustomerContactPersonPhone,
	// 				CONTACTPERSON.CELLULARPHONE                        AS CustomerContactPersonCellPhone
	// 			FROM RTDEMPLPERWORKREPORT
	// 			LEFT JOIN SALESTABLE      ON RTDEMPLPERWORKREPORT.PROJID   = SALESTABLE.PROJID              AND SALESTABLE.DATAAREAID     = RTDEMPLPERWORKREPORT.DATAAREAID
	// 			LEFT JOIN CUSTTABLE       ON SALESTABLE.CUSTACCOUNT        = CUSTTABLE.ACCOUNTNUM           AND CUSTTABLE.DATAAREAID      = RTDEMPLPERWORKREPORT.DATAAREAID
	// 			LEFT JOIN CONTACTPERSON   ON SALESTABLE.CONTACTPERSONID    = CONTACTPERSON.CONTACTPERSONID  AND CONTACTPERSON.DATAAREAID  = RTDEMPLPERWORKREPORT.DATAAREAID
	// 			LEFT JOIN EMPLTABLE       ON EMPLTABLE.EMPLID              = SALESTABLE.SALESRESPONSIBLE    AND EMPLTABLE.DATAAREAID      = RTDEMPLPERWORKREPORT.DATAAREAID
	// 			WHERE 
	// 				RTDEMPLPERWORKREPORT.PROJID = REPLACE(:projid, '-', '/')
	// 				AND RTDEMPLPERWORKREPORT.EMPLID = :emplid"
	// 		);
			
	// 		$workReport->bindParam(':projid', $projid, PDO::PARAM_STR, 20);
	// 		$workReport->bindParam(':emplid', $employee['id'], PDO::PARAM_STR, 12);
	// 		$workReport->setFetchMode(PDO::FETCH_NAMED);
	// 		$workReport->execute();

	// 		$data[0] = $workReport->fetch();
	// 		$workReport->closeCursor();

	// 		//$data[0]['cost_center'] = $this->EE->axapta->cost_center();

	// 		//$data[0]['customer'] = $this->EE->axapta->customer();

	// 		$resources = $ax_conn->query(
	// 			"SELECT
	// 				EMPLTABLE.EMPLID                        AS resource_id, 
	// 				EMPLTABLE.NAME                          AS name
	// 			FROM RTDEMPLPERWORKREPORT
	// 			LEFT JOIN EMPLTABLE ON RTDEMPLPERWORKREPORT.EMPLID = EMPLTABLE.EMPLID AND RTDEMPLPERWORKREPORT.DATAAREAID = EMPLTABLE.DATAAREAID
	// 			WHERE 
	// 				RTDEMPLPERWORKREPORT.PROJID = REPLACE('$projid', '-', '/')
	// 			ORDER BY EMPLTABLE.NAME"
	// 		);
	// 		$data[0]['resources'] = $resources->fetchAll();

	// 		$salesItems = $ax_conn->prepare(
	// 			"SELECT
	// 				LTRIM(SALESLINE.INVENTDIMID)            AS dimension_id,
	// 				SALESLINE.ITEMID                        AS item_id,
	// 				SALESLINE.RTDITEMNAME                   AS name,
	// 				SALESLINE.SALESUNIT                     AS unit
	// 			FROM SALESTABLE
	// 			LEFT JOIN SALESLINE ON SALESTABLE.SALESID = SALESLINE.SALESID AND SALESTABLE.DATAAREAID = SALESLINE.DATAAREAID
	// 			WHERE 
	// 			SALESTABLE.PROJID = REPLACE(:projid, '-', '/')
	// 			AND SALESLINE.RTDWrkCtrID <> :emplid
	// 			ORDER BY SALESLINE.RTDITEMNAME, SALESLINE.SALESUNIT"
	// 		);
			
	// 		$salesItems->bindParam(':projid', $projid, PDO::PARAM_STR, 20);
	// 		$salesItems->bindParam(':emplid', $employee['id'], PDO::PARAM_STR, 12);
	// 		$salesItems->setFetchMode(PDO::FETCH_NAMED);
	// 		$salesItems->execute();

	// 		$data[0]['salesItems'] = $salesItems->fetchAll();

	// 		$materials = $ax_conn->prepare(
	// 			"SELECT 
	// 				INVENTTABLE.ITEMID                      AS item_id,
	// 				LTRIM(PRICEDISCTABLE.INVENTDIMID)       AS dimension_id,
	// 				PRICEDISCTABLE.RTDITEMNAME              AS itemName,
	// 				CONFIGTABLE.NAME                        AS name,
	// 				PRICEDISCTABLE.UNITID                   AS unit,
	// 				PRICEDISCTABLE.RTDPRICETYPE             AS priceType,
	// 				PRICEDISCTABLE.AMOUNT                   AS ammount,
	// 				RTDCONTRACT.VALIDFROM                   AS validFrom,
	// 				RTDCONTRACT.VALIDTO                     AS valitTo
	// 			FROM PROJTABLE
	// 				JOIN SALESTABLE      ON PROJTABLE.PROJID = SALESTABLE.projID AND PROJTABLE.DATAAREAID = SALESTABLE.DATAAREAID
	// 				JOIN PRICEDISCTABLE  ON LTRIM(RTRIM(SALESTABLE.PriceGroupID)) = LTRIM(RTRIM(PRICEDISCTABLE.AccountRelation)) AND PROJTABLE.DATAAREAID = PRICEDISCTABLE.DATAAREAID
	// 				JOIN RTDCONTRACT     ON RTDCONTRACT.CONTRACTID = LTRIM(RTRIM(SALESTABLE.PriceGroupID)) AND PROJTABLE.DATAAREAID = RTDCONTRACT.DATAAREAID
	// 				JOIN INVENTTABLE     ON PRICEDISCTABLE.ITEMRELATION = INVENTTABLE.ITEMID AND PROJTABLE.DATAAREAID = INVENTTABLE.DATAAREAID
	// 				JOIN INVENTDIM       ON INVENTDIM.INVENTDIMID = PRICEDISCTABLE.INVENTDIMID AND PROJTABLE.DATAAREAID = INVENTDIM.DATAAREAID
	// 				JOIN CONFIGTABLE     ON CONFIGTABLE.CONFIGID = INVENTDIM.CONFIGID AND CONFIGTABLE.ITEMID = INVENTTABLE.ITEMID AND PROJTABLE.DATAAREAID = CONFIGTABLE.DATAAREAID
	// 			WHERE
	// 				RTDCONTRACT.VALID = 1
	// 				AND SALESTABLE.PROJID = REPLACE(:projid, '-', '/')
	// 				AND INVENTTABLE.RTDFILMIND = '1'
	// 			ORDER BY PRICEDISCTABLE.RTDITEMNAME"
	// 		);
			
	// 		$salesItems->bindParam(':projid', $projid, PDO::PARAM_STR, 20);
	// 		$salesItems->setFetchMode(PDO::FETCH_NAMED);
	// 		$salesItems->execute();
			
	// 		$data[0]['materials'] = $materials->fetchAll();

	// 		$form_open = array(
	// 			'action'		=> $submit_uri,
	// 			'name'          => 'workReport',
	// 			'id'            => $this->EE->TMPL->form_id,
	// 			'class'         => $this->EE->TMPL->form_class,
	// 			'hidden_fields' => array(
	// 									'projid' 				=> str_replace('-', '/', $projid),
	// 									'DataAreaID'            => $data[0]['DataAreaID'],
	// 									'id' 					=> $data[0]['EmployeeID'],
	// 									'execution_date'		=> $data[0]['ExecDate'],
	// 									'company_id'			=> $data[0]['CompanyID'],
	// 									//'customer_name'		=> $data[0]['customer_name'],
	// 									'customer_account'		=> $data[0]['CustomerAccount'],
	// 									//'customer_reference'	=> $data[0]['CustomerRef'],
	// 									'rtd_reference'			=> $data[0]['RTDRef'],
	// 									'work_location_name'	=> $data[0]['DeliveryName'],
	// 									'work_location_address' => $data[0]['DeliveryAddress'],
	// 									'cost_center'			=> $employee['cost_center_id'],
	// 									'contact_person'		=> $data[0]['CustomerContactPersonName'],
	// 									'contact_email'         => $data[0]['CustomerContactPersonEmail'],
	// 									'contact_phone'         => $data[0]['CustomerContactPersonPhone'],
	// 									'contact_cell'          => $data[0]['CustomerContactPersonCellPhone'],
	// 									'object_description'	=> $data[0]['ObjectDescription']
	// 								),
	// 			'secure'        => TRUE,
	// 			'onsubmit'      => ''
	// 		);
	// 		$data[0]['form_open'] = $this->EE->functions->form_declaration($form_open);
	// 		$data[0]['form_close'] = '</form>';

	// 		$tagdata = $this->EE->TMPL->tagdata;
	// 		$this->return_data = $this->EE->TMPL->parse_variables( $tagdata,  $data);
	// 		return $this->return_data;
	// 	} else {
	// 		return;
	// 	}
	// }

	// Remake of wrDetails(), but it comes from MySQL instead of Axapta
	function wrDetails($projid = NULL) {
		// if ( ($employee = $this->EE->axapta->employee()) && $ax_conn = $this->EE->axapta->axapta_connection() ) {
			if(is_null($projid)) {
				$projid = explode( '-', $this->EE->db->escape_str( $this->EE->TMPL->fetch_param('projid') ) );
			}
			$submit_uri = $this->EE->functions->fetch_site_index(0, 0).QUERY_MARKER.'ACT='.$this->EE->functions->fetch_action_id('Workreports', 'submit_for_approval');
			// $employee = $this->EE->axapta->employee->get_remote(array( 'email' => $this->EE->session->userdata('email') ));

			$this->EE->db->select('
						id,
						crew_leader_id,
						sales_id,
						project_id,
						project_order_id,
						project_work_order_id,
						project_work_report_id,
						company_id,
						rtd_reference,
						sales_name,
						invoice_account,
						object_description,
						order_description,
						execution_date,
						work_location_name,
						work_location_address,
						customer_reference,
						company_id,
						team_contact_name,
						team_contact_address,
						team_contact_phone,
						team_contact_fax,
						team_contact_email,
						customer_name,
						customer_address,
						customer_phone,
						customer_fax,
						customer_email,
						customer_account,
						customer_contact_id,
						customer_contact_name,
						customer_contact_email,
						customer_contact_phone,
						customer_contact_mobile
			');

			$this->EE->db->from('wr_reports');
			$this->EE->db->where('project_order_id', $projid[0]);
			$this->EE->db->where('project_work_order_id', $projid[1]);
			$this->EE->db->where('project_work_report_id', $projid[2]);
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
										'projid' 				=> $data[0]['project_id'],
										'id' 					=> $data[0]['crew_leader_id'],
										'execution_date'		=> $data[0]['execution_date'],
										'company_id'			=> $data[0]['company_id'],
										//'customer_name'		=> $data[0]['customer_name'],
										'customer_account'		=> $data[0]['customer_account'],
										//'customer_reference'	=> $data[0]['customer_reference'],
										'rtd_reference'			=> $data[0]['rtd_reference'],
										'work_location_name'	=> $data[0]['work_location_name'],
										'work_location_address' => $data[0]['work_location_address'],
										// 'cost_center'			=> $employee['cost_center_id'],
										'contact_person'		=> $data[0]['customer_contact_name'],
										'contact_email'         => $data[0]['customer_contact_email'],
										'contact_phone'         => $data[0]['customer_contact_phone'],
										'contact_cell'          => $data[0]['customer_contact_mobile'],
										'object_description'	=> $data[0]['object_description']
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
		if ( ($employee = $this->EE->axapta->employee()) ) {
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