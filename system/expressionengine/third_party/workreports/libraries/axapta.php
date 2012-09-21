<?php
class Axapta {
	private $conn;

	public function __construct() {
		$this->EE =& get_instance();

		if(!$this->conn = $this->axapta_connection()){
			exit();
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
	 *	Employee Details
	 *	
	 *	Defaults: EMAIL = current user email
	 *	Options: ANY COLUMN
	 *
	 */
	function employee_info($options = NULL) {
		/*
		 *  Employee information
		 */
		$query = 'SELECT
			[NAME]                        AS name_last_first,
			[ALIAS]                       AS alias,
			[TITLE]                       AS title,

			[EMPLID]                      AS employee_id,
			[USERID]                      AS axapta_id,
			[DATAAREAID]                  AS data_area_id,
			[INTERNALEXTERNAL]            AS internal_external,
			[HRMACTIVEINACTIVE]           AS hrm_active_flag,
			[STATUS]                      AS status,

			[DATAAREAID]                  AS company_id,
			[DIMENSION]                   AS department_id,
			[DIMENSION2_]                 AS cost_center_id,
			[DIMENSION3_]                 AS technique_id,
			[DIMENSION4_]                 AS business_line_id,
			
			[EMAIL]                       AS email,
			[PHONE]                       AS phone,
			[CELLULARPHONE]               AS cell_phone,
			[RTDPRIVATEPHONE]             AS personal_phone,
			[TELEFAX]                     AS fax,
			
			[ADDRESS]                     AS full_address,
			[STREET]                      AS street,
			[CITY]                        AS city,
			[COUNTYID]                    AS county,
			[STATEID]                     AS state,
			[ZIPCODEID]                   AS zip_code,
			[COUNTRYID]                   AS country,
			
			[CURRENCY]                    AS currency,
			
			CONVERT(DATE,[BIRTHDATE])     AS birth_date,
			CONVERT(DATE,[MODIFIEDDATE])  AS modified_date,
			[MODIFIEDTIME]                AS modified_time,
			[MODIFIEDBY]                  AS modified_by,
			CONVERT(DATE,[CREATEDDATE])   AS created_date,
			[CREATEDTIME]                 AS created_time,
			[CREATEDBY]                   AS created_by
		FROM [TEST].[dbo].[EMPLTABLE]';

		//extend query with options
		$query .= ' WHERE ';

		//handy dubug defaults;
		//$options = array('DATAAREAID' => '107';
		//$options = array('email' => 'chet.yates@applusrtd.com');
		//$options = array('email' => 'bert.weber@applusrtd.com');

		if( is_array($options) && count($options) > 0 ){
			foreach ($options as $key => $value) {
				$query .= $key.' = :'.$key;

				if( count($options) > 1 || !isset($data['email']) ) { 
					$query .= ' AND '; 
				}
			}
		}

		//set defualt values
		if( !isset($data['email']) ) {
			$query .= 'EMAIL = :email';
		}

		$employee_info = $this->conn->prepare($query);

		//bind options
		if( is_array($options) && count($options) > 0 ){
			foreach ($options as $key => $value) {
				$employee_info->bindValue(':'.$key, $value);
			}
		}

		//bind defaults
		if ( !isset($options['email']) ) {
			$employee_info->bindValue(':email', $this->EE->session->userdata['email'], PDO::PARAM_STR);
		} else {
			$employee_info->bindValue(':email', $options['email'], PDO::PARAM_STR);
		}
		
		$employee_info->setFetchMode(PDO::FETCH_NAMED);
		$employee_info->execute();
		$employee = $employee_info->fetchAll();

		//Sanity Check if there are more than one employee's returned for 1 email given
		if( count($employee) == 1 ) {
			//no need for nested arrays
			$employee = $employee[0];

			//fix employee name so we have a nice "First Last" setup
			$employee_name = explode(',', $employee['name_last_first']);
			$employee['name'] = ltrim($employee_name[1]).' '.$employee_name[0];

			//fix modified and created dates into unix timestamps
			$employee['modified_datetime'] = strtotime($employee['modified_date']) + ($employee['modified_time']/1000);
			$employee['created_datetime']  = strtotime($employee['created_date']) + ($employee['created_time']/1000);

			
			/*
			 *	each company has it's own groups
			 *	each employee can belong to multiple groups in differnt companies
			 *	!an employee should only be granted a group's privlidges for the correct company
			 *
			 *	[groups] => Array(
		     *      [company_id] => Array(
		     *          [0] => 'GROUP ID'
		     *      ),
		     *      [107] => Array(
		     *          [0] => 'WA TECH',
		     *          [1] => 'WA DISP',
		     *			[2] => 'WA ADMIN'
		     *      )
			 *  )
			 *
			 */
			$employee['groups'] = array();
			$employeeGroups = $this->conn->prepare(
				"SELECT
					HRMVIRTUALNETWORKHISTORY.DATAAREAID        AS company_id,
					HRMVIRTUALNETWORKHISTORY.HRMPOSITIONID     AS position_id
				FROM HRMVIRTUALNETWORKTABLE
				JOIN HRMVIRTUALNETWORKHISTORY ON HRMVIRTUALNETWORKTABLE.HRMVIRTUALNETWORKID = HRMVIRTUALNETWORKHISTORY.HRMVIRTUALNETWORKID
				WHERE REFERENCE = :employee_id"
			);
			$employeeGroups->bindParam(':employee_id', $employee['employee_id'], PDO::PARAM_STR, 12);
			$employeeGroups->setFetchMode(PDO::FETCH_NAMED);
			$employeeGroups->execute();

			foreach ($employeeGroups->fetchALL() as $group) {
				if( !array_key_exists($group['company_id'], $employee['groups'])){
					$employee['groups'][$group['company_id']] = array();
				}
				array_push($employee['groups'][$group['company_id']], $group['position_id'] );
			}

			return $this->fix_padding($employee);
		} else {
			return FALSE;
		}
	}

	/*
	 *  Company Info
	 *	company_id = DataAreaID
	 */
	function company($company_id = NULL) {
		$company = $this->conn->prepare('SELECT
				DATAAREAID        AS id,
				NAME              AS name,
				ADDRESS           AS address,
				PHONE             AS phone,
				STREET            AS street,
				CITY              AS city,
				STATE             AS state,
				ZIPCODE           AS zipcode,
				COUNTY            AS county,
				COUNTRY           AS country,
				ADDRFORMAT        AS address_format,
				RTDCOMPANYPREFIX  AS company_prefix
			FROM COMPANYINFO
			WHERE DATAAREAID = :company_id');

		if($company_id){
			$company->bindParam(':company_id', $company_id, PDO::PARAM_STR, 3);
		} else {
			if ( $employee = $this->employee_info() ) {
				$company->bindParam(':company_id', $employee['company_id'], PDO::PARAM_STR, 3);
			} else {
				return FALSE;
			}
		}
		$company->setFetchMode(PDO::FETCH_NAMED);
		$company->execute();

		$return_data = $company->fetchAll();
		$company->closeCursor();

		return $this->fix_padding($return_data);
	}

	/*
	 *  Cost Center
	 */
	function cost_center($cost_center_id = NULL, $company_id = NULL) {
		if ( ($employee = $this->employee_info()) && ($this->conn = $this->axapta_connection()) ) {
			$cost_center = $this->conn->prepare(
				"SELECT
					DIMENSIONS.COMPANYGROUP AS CompanyGroup,
					ADDRESS.NAME            AS name,
					ADDRESS.ADDRESS         AS address,
					ADDRESS.PHONE           AS phone,
					ADDRESS.TELEFAX         AS fax,
					ADDRESS.EMAIL           AS email,
					ADDRESS.STREET          AS street,
					ADDRESS.CITY            AS city,
					ADDRESS.STATE           AS state,
					ADDRESS.ZIPCODE         AS zipcode,
					ADDRESS.COUNTRY         AS country
				FROM DIMENSIONS
				LEFT JOIN ADDRESS ON DIMENSIONS.RTDADDRESS = ADDRESS.RECID
				WHERE DIMENSIONS.DATAAREAID = :dataAreaID
				AND DIMENSIONS.NUM = :dim2
				AND DIMENSIONS.DIMENSIONCODE = 1"
			);

			if(is_null($cost_center_id)){
				$cost_center->bindValue(':dim2', $employee['department_id'], PDO::PARAM_STR);
			} else {
				$cost_center->bindValue(':dim2', $cost_centerID, PDO::PARAM_STR);
			}

			if(is_null($company_id)){
				$cost_center->bindValue(':dataAreaID', $employee['data_area_id'], PDO::PARAM_STR);
			} else {
				$cost_center->bindValue(':dataAreaID', $companyID, PDO::PARAM_STR);
			}
			
			$cost_center->setFetchMode(PDO::FETCH_NAMED);
			$cost_center->execute();

			return $cost_center->fetchAll();
		} else {
			return FALSE;
		}
	}

	/*
	 *  Customers
	 *
	 *	Options: customer_id, customer_name
	 *	Defaults: DATAAREAID = current user's compnay_id
	 *
	 */
	function customers($options = NULL) {
		if ( $employee = $this->employee_info() ) {
			//These are the companies the employee has "WA TECH", we will only list customers of these companies.
			$authorized_companies = '';
			//$employee['groups'][101] = array('WA TECH');  //debugging test
			$last_key = end( array_keys($employee['groups']) );
			foreach ($employee['groups'] as $company => $group) {
				if ( in_array('WA TECH', $employee['groups'][$company]) ) {
					$authorized_companies .= "'$company'";
					if( count($employee['groups']) > 1 && $company != $last_key) {
						$authorized_companies .= ', ';
					}
				}
			}

			$query = 
				'SELECT
					CustTable.AccountNum          AS account_num,
					CustTable.Name                AS name,
					CustTable.Address             AS address,
					CustTable.Phone               AS phone,
					CustTable.TELEFAX             AS fax,
					SMMBUSRELTABLE.MAINCONTACT    AS main_contact,
					smmBusRelTable.BusRelAccount  AS business_relation_account
				FROM CustTable
				LEFT JOIN smmBusRelTable ON smmBusRelTable.CustAccount = CustTable.AccountNum
				WHERE CustTable.DATAAREAID IN ('.$authorized_companies.')';

			if( isset($options['customer_id']) ) {
				$query .= ' AND CustTable.AccountNum = :customer_id';
			}
			if( isset($options['customer_name']) ) {
				$query .= ' AND CustTable.NAME LIKE :customer_name';
			}

			$customers = $this->conn->prepare($query);

			if( isset($options['customer_id']) ) {
				$customers->bindValue(':customer_id', $options['customer_id'], PDO::PARAM_STR);
			}
			if( isset($options['customer_name']) ) {
				$customers->bindValue(':customer_name', '%'.$options['customer_name'].'%', PDO::PARAM_STR);
			}

			$customers->setFetchMode(PDO::FETCH_NAMED);
			$customers->execute();

			return $this->fix_padding($customers->fetchAll());
		} else {
			return FALSE;
		}
	}

	/*
	 *  Contact Persons
	 *
	 *	Options: 
	 *	Defaults: 
	 *
	 */
	function contact_persons($options) {
		if ( $this->conn = $this->axapta_connection() ) {

		} else {
			return FALSE;
		}
	}

	/*
	 *  Work Locations
	 *
	 *	Options:
	 *	Defaults:
	 *
	 */
	function work_locations() {
		if ( $this->conn = $this->axapta_connection() ) {

		} else {
			return FALSE;
		}
	}

	/*
	 *  Sales Items
	 *
	 *	Options: 
	 *	Defaults: 
	 *
	 */
	function sales_items($customer_id = NULL) {
		if ( $this->conn = $this->axapta_connection() ) {
			$salesItems = $this->conn->query(
				"SELECT
					LTRIM(SALESLINE.INVENTDIMID)            AS dimension_id,
					SALESLINE.ITEMID                        AS item_id,
					SALESLINE.RTDITEMNAME                   AS name,
					SALESLINE.SALESUNIT                     AS unit
				FROM SALESTABLE
				LEFT JOIN SALESLINE ON SALESTABLE.SALESID = SALESLINE.SALESID AND SALESTABLE.DATAAREAID = SALESLINE.DATAAREAID
				WHERE 
				SALESTABLE.PROJID = REPLACE('$projid', '-', '/')
				AND SALESLINE.RTDWrkCtrID <> '$this->emplID'
				ORDER BY SALESLINE.RTDITEMNAME, SALESLINE.SALESUNIT"
			);

			return $salesItems->fetchAll();
		} else {
			return FALSE;
		}
	}

	/*
	 *  Materials
	 *
	 *	Options: 
	 *	Defaults: 
	 *
	 */
	function materials() {
		if ( $this->conn = $this->axapta_connection() ) {
			$materials = $ax_conn->query(
				"SELECT 
					INVENTTABLE.ITEMID                      AS item_id,
					LTRIM(PRICEDISCTABLE.INVENTDIMID)       AS dimension_id,
					PRICEDISCTABLE.RTDITEMNAME              AS itemName,
					CONFIGTABLE.NAME                        AS name,
					PRICEDISCTABLE.UNITID                   AS unit,
					PRICEDISCTABLE.RTDPRICETYPE             AS priceType,
					PRICEDISCTABLE.AMOUNT                   AS ammount,
					RTDCONTRACT.VALIDFROM                   AS validFrom,
					RTDCONTRACT.VALIDTO                     AS valitTo
				FROM PROJTABLE
					JOIN SALESTABLE      ON PROJTABLE.PROJID = SALESTABLE.projID AND PROJTABLE.DATAAREAID = SALESTABLE.DATAAREAID
					JOIN PRICEDISCTABLE  ON LTRIM(RTRIM(SALESTABLE.PriceGroupID)) = LTRIM(RTRIM(PRICEDISCTABLE.AccountRelation)) AND PROJTABLE.DATAAREAID = PRICEDISCTABLE.DATAAREAID
					JOIN RTDCONTRACT     ON RTDCONTRACT.CONTRACTID = LTRIM(RTRIM(SALESTABLE.PriceGroupID)) AND PROJTABLE.DATAAREAID = RTDCONTRACT.DATAAREAID
					JOIN INVENTTABLE     ON PRICEDISCTABLE.ITEMRELATION = INVENTTABLE.ITEMID AND PROJTABLE.DATAAREAID = INVENTTABLE.DATAAREAID
					JOIN INVENTDIM       ON INVENTDIM.INVENTDIMID = PRICEDISCTABLE.INVENTDIMID AND PROJTABLE.DATAAREAID = INVENTDIM.DATAAREAID
					JOIN CONFIGTABLE     ON CONFIGTABLE.CONFIGID = INVENTDIM.CONFIGID AND CONFIGTABLE.ITEMID = INVENTTABLE.ITEMID AND PROJTABLE.DATAAREAID = CONFIGTABLE.DATAAREAID
				WHERE
					RTDCONTRACT.VALID = 1
					AND SALESTABLE.PROJID = REPLACE('$projid', '-', '/')
					AND INVENTTABLE.RTDFILMIND = '1'
				ORDER BY PRICEDISCTABLE.RTDITEMNAME"
			);
			
			return $materials->fetchAll();
		} else {
			return FALSE;
		}
	}

	/*
	 *  Work Reports
	 *
	 *	Options: 
	 *	Defaults: 
	 *
	 */
	function dispatched_workreport($project_id) {
		if( ($employee = $this->employee_info()) && ($this->conn = $this->axapta_connection()) ) {$workReport = $ax_conn->query(
				"SELECT
					RTDEMPLPERWORKREPORT.EMPLID                        AS EmployeeID,
					LTRIM(RTDEMPLPERWORKREPORT.SALESID)                AS SalesID,
					RTDEMPLPERWORKREPORT.PROJID                        AS ProjectID,
					RTDEMPLPERWORKREPORT.DATAAREAID                    AS CompanyID,
					SALESTABLE.RTDPROJORDERREFERENCE                   AS RTDRef,
					SALESTABLE.SALESNAME                               AS SalesName,
					SALESTABLE.INVOICEACCOUNT                          AS InvoiceAccount,
					SALESTABLE.RTDOBJECTDESCRIPTION                    AS ObjectDescription,
					SALESTABLE.RTDORDERDESCRIPTION                     AS OrderDescription,
					DATEDIFF(s, '1970-01-01', SALESTABLE.DELIVERYDATE) AS ExecDate,
					SALESTABLE.DELIVERYNAME                            AS DeliveryName,
					SALESTABLE.DELIVERYADDRESS                         AS DeliveryAddress,
					SALESTABLE.CUSTOMERREF                             AS CustomerRef,
					RTDEMPLPERWORKREPORT.DATAAREAID                    AS DataAreaID,
					EMPLTABLE.NAME                                     AS TeamContactPerson,
					EMPLTABLE.ADDRESS                                  AS TeamContactPersonAddress,
					EMPLTABLE.PHONE                                    AS TeamContactPersonPhone,
					EMPLTABLE.TELEFAX                                  AS TeamContactPersonFax,
					EMPLTABLE.EMAIL                                    AS TeamContactPersonEmail,
					CUSTTABLE.NAME                                     AS CustomerName,
					CUSTTABLE.ADDRESS                                  AS CustomerAddress,
					CUSTTABLE.PHONE                                    AS CustomerPhone,
					CUSTTABLE.TELEFAX                                  AS CustomerFax,
					CUSTTABLE.EMAIL                                    AS CustomerEmail,
					LTRIM(SALESTABLE.CUSTACCOUNT)                      AS CustomerAccount,
					SALESTABLE.CONTACTPERSONID                         AS CustomerContactPersonID,
					CONTACTPERSON.name                                 AS CustomerContactPersonName,
					CONTACTPERSON.email                                AS CustomerContactPersonEmail,
					CONTACTPERSON.PHONE                                AS CustomerContactPersonPhone,
					CONTACTPERSON.CELLULARPHONE                        AS CustomerContactPersonCellPhone
				FROM RTDEMPLPERWORKREPORT
				LEFT JOIN SALESTABLE      ON RTDEMPLPERWORKREPORT.PROJID   = SALESTABLE.PROJID              AND SALESTABLE.DATAAREAID     = RTDEMPLPERWORKREPORT.DATAAREAID
				LEFT JOIN CUSTTABLE       ON SALESTABLE.CUSTACCOUNT        = CUSTTABLE.ACCOUNTNUM           AND CUSTTABLE.DATAAREAID      = RTDEMPLPERWORKREPORT.DATAAREAID
				LEFT JOIN CONTACTPERSON   ON SALESTABLE.CONTACTPERSONID    = CONTACTPERSON.CONTACTPERSONID  AND CONTACTPERSON.DATAAREAID  = RTDEMPLPERWORKREPORT.DATAAREAID
				LEFT JOIN EMPLTABLE       ON EMPLTABLE.EMPLID              = SALESTABLE.SALESRESPONSIBLE    AND EMPLTABLE.DATAAREAID      = RTDEMPLPERWORKREPORT.DATAAREAID
				WHERE 
					RTDEMPLPERWORKREPORT.PROJID = REPLACE('$projid', '-', '/')
					AND RTDEMPLPERWORKREPORT.EMPLID = '$this->emplID'"
			);
			
			$workReport->setFetchMode(PDO::FETCH_NAMED);
			$data = $workReport->fetch();
			$workReport->closeCursor();

			return $data;
		} else {
			return FALSE;
		}
	}

	function set_approval($projid=NULL, $dataAreaID=NULL, $emplID=NULL, $approved=TRUE) {
		if( ($employee = $this->employee_info()) && ($this->conn = $this->axapta_connection()) ) {
			$query = 'UPDATE Salestable SET RtdApproved = :approved WHERE PROJID = :projid AND DATAAREAID = :dataAreaID';

			$approval = $this->conn->prepare($query);

			if($approved) {
				$approval->bindValue('approved', 1, PDO::PARAM_STR);
			} else {
				$approval->bindValue('approved', 0, PDO::PARAM_STR);
			}
			$approval->bindValue('projid', $projid, PDO::PARAM_STR);
			$approval->bindValue('dataAreaID', $dataAreaID, PDO::PARAM_STR);
			
			$approval->execute();
			//$approval->bindValue('emplID', $dataAreaID, PDO::PARAM_STR, 3);
		}
	}

	/*
	* Looks from $_GET to update a work report's status.
	*/
	function set_status() {
		if ( ($employee = $this->EE->axapta->employee_info()) && ($this->conn = $this->EE->axapta->axapta_connection()) ) {
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

			$dir = '/ax-public/'.$report_query['company'].'/Customer Reporting/Tablet Interface/';
			$file = $report_query['order'].' '.$report_query['order'].'.'.$report_query['work_order'].'.'.$report_query['work_report'].' [TEST].xml';

			// New DOM document
			$doc = new DOMDocument('1.0','iso-8859-1');
			$doc->formatOutput = TRUE;

			// Create and append root element of xml tree
			$xml_root = $doc->createElement('xml');
			$xml_root = $doc->appendChild($xml_root);

			// Create and append Employee ID
			$empl_id = $doc->createElement('EmplId', $report_query['submitter_id'] );
			$empl_id = $xml_root->appendChild($empl_id);

			// Create and append Crew Leader ID
			$crew_leader = $doc->createElement('CrewLeader', $report_query['crew_leader'] );
			$crew_leader = $xml_root->appendChild($crew_leader);

			// Create and append company
			$company = $doc->createElement('company');
			$company = $xml_root->appendChild($company);

			// Fill company with work report elements
			$company->appendChild($doc->createElement('Company', 				$report_query['company'] ));
			$company->appendChild($doc->createElement('CustomerAccount', 		$report_query['customer_account'] ));
			$company->appendChild($doc->createElement('Order', 				$report_query['order'] ));
			$company->appendChild($doc->createElement('WorkOrder', 			$report_query['work_order'] ));
			$company->appendChild($doc->createElement('WorkReport', 			$report_query['work_report'] ));
			$company->appendChild($doc->createElement('CustomerReference',		$report_query['customer_reference'] ));
			$company->appendChild($doc->createElement('RTDReference', 			$report_query['rtd_reference'] ));
			$company->appendChild($doc->createElement('WorkLocationName', 		$report_query['work_location_name'] ));
			$company->appendChild($doc->createElement('ContactPerson', 		$report_query['contact_person'] ));
			$company->appendChild($doc->createElement('ObjectDescription',		$this->EE->typography->parse_type($report_query['object_description'],array('text_format' => 'none')) ));
			$company->appendChild($doc->createElement('ExecutionDate', 		date('Y-m-d',$report_query['execution_date']) ));

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
					$this->EE->session->set_flashdata('message_failure', "An error occured, please notify your system ad");
				}
				return TRUE;
			// }

		} else {
			show_error('Invalid id given.');
		}
	}
}