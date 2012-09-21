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
	 *	Employee Details
	 *	
	 *	Defaults: EMAIL = current user email
	 *	Options: ANY COLUMN
	 *
	 */
	function employee($options = NULL) {
		/*
		 *  Employee information
		 */
		$query = 'SELECT
			[NAME]                        AS name_last_first,
			[ALIAS]                       AS alias,
			[TITLE]                       AS title,

			[EMPLID]                      AS id,
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
				WHERE REFERENCE = :id"
			);
			$employeeGroups->bindParam(':id', $employee['id'], PDO::PARAM_STR, 12);
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
	 *	Options: id (default: employee['company_id'])
	 *
	 *	DataAreaID = Company ID
	 */
	function company($options = NULL) {
		if ( $employee = $this->employee() ) {
			$query = 'SELECT
					DATAAREAID                  AS id,
					NAME                        AS name,
					ADDRESS                     AS address,
					PHONE                       AS phone,
					STREET                      AS street,
					CITY                        AS city,
					STATE                       AS state,
					ZIPCODE                     AS zipcode,
					COUNTY                      AS county,
					COUNTRY                     AS country,
					ADDRFORMAT                  AS address_format,
					RTDCOMPANYPREFIX            AS company_prefix,

					CONVERT(DATE,MODIFIEDDATE)  AS modified_date,
					MODIFIEDTIME                AS modified_time,
					MODIFIEDBY                  AS modified_by
				FROM COMPANYINFO';
			
			if( !isset($options['id']) || $options['id'] != 'ALL' ) {
				$query .= ' WHERE DATAAREAID = :id';
			}

			$company = $this->conn->prepare($query);

			if( isset($options['id']) && $options['id'] != 'ALL' ){
				$company->bindParam(':id', $options['id'], PDO::PARAM_STR, 3);
			} else {
				if ( $employee = $this->employee() ) {
					$company->bindParam(':id', $employee['company_id'], PDO::PARAM_STR, 3);
				} else {
					return FALSE;
				}
			}

			$company->setFetchMode(PDO::FETCH_NAMED);
			$company->execute();

			$return_data = $company->fetchAll();

			foreach ($return_data as $row => &$values) {
				//fix modified dates into unix timestamps
				$values['modified_datetime'] = date('l jS \of F Y h:i:s A', strtotime($values['modified_date']) + ($values['modified_time']/1000));
			}

			return $this->fix_padding($return_data);
		} else {
			return FALSE;
		}
	}

	/*
	 *  Cost Center
	 *	Option: id         (default: employee['cost_center_id'])
	 *	Option: company_id (default: employee['company_id'])
	 */
	function cost_center($options = NULL) {
		if ( $employee = $this->employee() ) {
			$cost_center = $this->conn->prepare(
				'SELECT
					DIMENSIONS.NUM          AS id,
					DIMENSIONS.DATAAREAID   AS company_id,
					DIMENSIONS.COMPANYGROUP AS company_group,
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
				WHERE DIMENSIONS.DATAAREAID = :company_id
				AND DIMENSIONS.NUM = :id
				AND DIMENSIONS.DIMENSIONCODE = 1'
			);


			if( isset($options['id']) ){
				$cost_center->bindValue(':id', $options['id'], PDO::PARAM_STR);
			} else {
				$cost_center->bindValue(':id', $employee['cost_center_id'], PDO::PARAM_STR);
			}

			if( isset($options['company_id']) ){
				$cost_center->bindValue(':company_id', $options['company_id'], PDO::PARAM_STR);
			} else {
				$cost_center->bindValue(':company_id', $employee['company_id'], PDO::PARAM_STR);
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
	 *	Option: id
	 *	Option: name
	 *
	 *	Will only return customers for an employee's authorized companies if no customer id given
	 *
	 */
	function customer($options = NULL) {
		if ( $employee = $this->employee() ) {

			//$employee['groups'][101] = array('WA TECH');  //debugging test

			$query = 
				'SELECT
					CustTable.AccountNum          AS id,
					CustTable.AccountNum          AS account_num,
					CustTable.Name                AS name,
					CustTable.Address             AS address,
					CustTable.Phone               AS phone,
					CustTable.TELEFAX             AS fax,
					SMMBUSRELTABLE.MAINCONTACT    AS main_contact,
					smmBusRelTable.BusRelAccount  AS business_relation_account
				FROM CustTable
				LEFT JOIN smmBusRelTable ON smmBusRelTable.CustAccount = CustTable.AccountNum
				WHERE 
					CUSTTABLE.BLOCKED = 0';

			if( isset($options['id']) ) {
				$query .= ' AND LTRIM(CustTable.AccountNum) = :id';
			} else {
				//These are the companies the employee has "WA TECH", we will only list customers of these companies.
				$authorized_companies = '';
				$last_key = end( array_keys($employee['groups']) );
				foreach ($employee['groups'] as $company => $group) {
					if ( in_array('WA TECH', $employee['groups'][$company]) ) {
						$authorized_companies .= "'$company'";
						if( count($employee['groups']) > 1 && $company != $last_key) {
							$authorized_companies .= ', ';
						}
					}
				}
				$query .= ' AND CustTable.DATAAREAID IN ('.$authorized_companies.')';
			}

			if( isset($options['name']) ) {
				$query .= ' AND CustTable.NAME LIKE :name';
			}

			$customers = $this->conn->prepare($query);

			if( isset($options['id']) ) {
				$customers->bindValue(':id', $options['id'], PDO::PARAM_STR);
			}
			if( isset($options['name']) ) {
				$customers->bindValue(':name', '%'.$options['name'].'%', PDO::PARAM_STR);
			}

			$customers->setFetchMode(PDO::FETCH_NAMED);
			$customers->execute();

			return $this->fix_padding($customers->fetchAll());
		} else {
			return FALSE;
		}
	}

	/*
	 *  Work Locations
	 *
	 *	Option: id
	 *	Option: name
	 *	Option: customer_name
	 *	Option: customer_id
	 *
	 *	Work Locations = address type 99
	 @TODO: Default Contact Person for Work Location
	 */
	function work_location($options = NULL) {
		if ( $employee = $this->employee() ) {
			$query = 
				'SELECT
					[ADDRESS].RTDLOCATIONID     AS id,
					[ADDRESS].NAME              AS name,
					CUSTTABLE.ACCOUNTNUM        AS customer_id,
					CUSTTABLE.NAME              AS customer_name,
					[ADDRESS].ADDRESS           AS full_address,
					[ADDRESS].STREET            AS street,
					[ADDRESS].CITY              AS county,
					[ADDRESS].STATE             AS state,
					[ADDRESS].ZIPCODE           AS zip_code,
					[ADDRESS].COUNTRY           AS country
				FROM CUSTTABLE
				RIGHT JOIN [ADDRESS] ON [ADDRESS].ADDRRECID = CUSTTABLE.RECID AND [ADDRESS].DATAAREAID = CUSTTABLE.DATAAREAID
				WHERE [ADDRESS].TYPE = 99';

			if( isset($options['id']) ) {
				$query .= ' AND [ADDRESS].RTDLOCATIONID = :id';
			} else {
				//These are the companies the employee has "WA TECH", we will only list customers of these companies.
				$authorized_companies = '';
				$last_key = end( array_keys($employee['groups']) );
				foreach ($employee['groups'] as $company => $group) {
					if ( in_array('WA TECH', $employee['groups'][$company]) ) {
						$authorized_companies .= "'$company'";
						if( count($employee['groups']) > 1 && $company != $last_key) {
							$authorized_companies .= ', ';
						}
					}
				}
				$query .= ' AND CUSTTABLE.DATAAREAID IN ('.$authorized_companies.')';
			}
			if( isset($options['name']) ){
				$query .= ' AND [ADDRESS].NAME LIKE :name';
			}
			if( isset($options['customer_id']) ){
				$query .= ' AND LTRIM(CUSTTABLE.ACCOUNTNUM) = :customer_id';
			}
			if( isset($options['customer_name']) ){
				$query .= ' AND CUSTTABLE.NAME LIKE :customer_name';
			}

			$query .= ' ORDER BY CUSTTABLE.NAME, [ADDRESS].NAME';

			$work_location = $this->conn->prepare($query);

			if( isset($options['id']) ) {
				$work_location->bindValue(':id', $options['id'], PDO::PARAM_STR);
			}
			if( isset($options['name']) ){
				$work_location->bindValue(':name', '%'.$options['name'].'%', PDO::PARAM_STR);
			}
			if( isset($options['customer_id']) ){
				$work_location->bindValue(':customer_id', $options['customer_id'], PDO::PARAM_STR);
			}
			if( isset($options['customer_name']) ){
				$work_location->bindValue(':customer_name', '%'.$options['customer_name'].'%', PDO::PARAM_STR);
			}
			
			$work_location->setFetchMode(PDO::FETCH_NAMED);
			$work_location->execute();
			
			$return_data = $work_location->fetchAll();

			return $this->fix_padding($return_data);
		} else {
			return FALSE;
		}
	}

	/*
	 *  Contact Persons
	 *
	 *	Option: id
	 *	Option: name
	 *	Option: customer_id
	 *	Option: customer_name
	 *
	 */
	function contact_person($options = NULL) {
		if ( $employee = $this->employee() ) {
			$query = 
			    'SELECT 
				    CONTACTPERSON.CONTACTPERSONID    AS id,
				    CONTACTPERSON.NAME               AS name_last_first,
				    CONTACTPERSON.EMAIL              AS email,
				    CONTACTPERSON.PHONE              AS phone,
				    CONTACTPERSON.CELLULARPHONE      AS cell_phone,
				    CUSTTABLE.ACCOUNTNUM             AS customer_id,
				    CUSTTABLE.NAME                   AS customer_name
				FROM CONTACTPERSON
				LEFT JOIN CUSTTABLE ON CUSTTABLE.ACCOUNTNUM = CONTACTPERSON.CUSTACCOUNT AND CUSTTABLE.DATAAREAID = CONTACTPERSON.DATAAREAID
				WHERE
					1=1';
			
			if( isset($options['id']) ) {
				$query .= ' AND CONTACTPERSON.CONTACTPERSONID = :id';
			} else {
				//These are the companies the employee has "WA TECH", we will only list customers of these companies.
				$authorized_companies = '';
				$last_key = end( array_keys($employee['groups']) );
				foreach ($employee['groups'] as $company => $group) {
					if ( in_array('WA TECH', $employee['groups'][$company]) ) {
						$authorized_companies .= "'$company'";
						if( count($employee['groups']) > 1 && $company != $last_key) {
							$authorized_companies .= ', ';
						}
					}
				}
				$query .= ' AND CUSTTABLE.DATAAREAID IN ('.$authorized_companies.')';
			}
			if( isset($options['name']) ){
				$query .= ' AND CONTACTPERSON.NAME LIKE :name';
			}
			if( isset($options['customer_id']) ){
				$query .= ' AND CUSTTABLE.ACCOUNTNUM = :customer_id';
			}
			if( isset($options['customer_name']) ){
				$query .= ' AND CUSTTABLE.NAME LIKE :customer_name';
			}
			//	'  
			//	ORDER BY CUSTTABLE.ACCOUNTNUM';
			

			$contact_person = $this->conn->prepare($query);

			if( isset($options['id']) ){
				$contact_person->bindValue(':id', $options['id'], PDO::PARAM_STR);
			}
			if( isset($options['name']) ){
				$contact_person->bindValue(':name', '%'.$options['name'].'%', PDO::PARAM_STR);
			}
			if( isset($options['customer_id']) ){
				$contact_person->bindValue(':customer_id', $options['customer_id'], PDO::PARAM_STR);
			}
			if( isset($options['customer_name']) ){
				$contact_person->bindValue(':customer_name', '%'.$options['customer_name'].'%', PDO::PARAM_STR);
			}

			$contact_person->setFetchMode(PDO::FETCH_NAMED);
			$contact_person->execute();

			$return_data = $contact_person->fetchAll();

			return $this->fix_padding($return_data);
		} else {
			return FALSE;
		}
	}
 
	/*
	 *  Work Reports
	 *
	 *	Option: 
	 *
	 */
	function work_report($options = NULL) {
		if( $employee = $this->employee() ) {
			$query = 
				'SELECT TOP(10)
					SALESTABLE.PROJID                                  AS project_id,
					SALESTABLE.EXPORTREASON                            AS export_reason,
					SALESTABLE.SALESID                                 AS sales_id,
					SALESTABLE.DATAAREAID                              AS company_id,
					SALESTABLE.DIMENSION                               AS department_id,
					SALESTABLE.DIMENSION2_                             AS cost_center_id,
					SALESTABLE.DIMENSION3_                             AS technique_id,
					RTDPROJORDERTABLE.CONTRACTID                       AS contract_id,
					RTDPROJORDERTABLE.CONTRACTDATE                     AS contract_date,
					SALESTABLE.DEADLINE                                AS deadline_date,
					CONVERT(DATE, SALESTABLE.DELIVERYDATE)             AS execution_date,
					SALESTABLE.RTDPROJORDERREFERENCE                   AS rtd_reference,
					SALESTABLE.SALESRESPONSIBLE                        AS sales_responsible,
					RTDEMPLPERWORKREPORT.EMPLID                        AS crew_leader,
					RTDPROJORDERTABLE.CONTACTPERSONID                  AS contact_person_id,
					SALESTABLE.DELIVERYNAME                            AS delivery_name,
					SALESTABLE.DELIVERYADDRESS                         AS delivery_address,
					SALESTABLE.CUSTACCOUNT                             AS customer_id,
					SALESTABLE.SALESNAME                               AS customer_name,
					SALESTABLE.CUSTOMERREF                             AS customer_refrence,
					SALESTABLE.CONTACTPERSONID                         AS customer_contact_person_id,
					SALESTABLE.RTDOBJECTDESCRIPTION                    AS object_description,
					SALESTABLE.RTDORDERDESCRIPTION                     AS order_description,
					RTDSALESPROCEDURE.RESEARCHNORMID                   AS research_norm_id,
					RTDSALESPROCEDURE.RESEARCHPROCEDUREID              AS research_procedure_id,
					RTDSALESPROCEDURE.RESEARCHSPECID                   AS research_spec_id,
					RTDSALESPROCEDURE.REVIEWNORMID                     AS review_norm_id,
					RTDSALESPROCEDURE.REVIEWPROCEDUREID                AS review_procedure_id,
					RTDSALESPROCEDURE.REVIEWSPECID                     AS review_spec_id,
					SALESTABLE.RTDAPPROVED                             AS status,
					RTDPROJORDERTABLE.PROJORDERSTATUS                  AS project_status,
					SALESTABLE.INVOICEACCOUNT                          AS invoice_account,
					SALESTABLE.RTDINVOICED                             AS invoiced_status,
					CONVERT(DATE, SALESTABLE.CREATEDDATE)              AS created_date,
					SALESTABLE.CREATEDTIME                             AS created_time,
					SALESTABLE.CREATEDBY                               AS created_by,
					CONVERT(DATE, SALESTABLE.MODIFIEDDATE)             AS modified_date,
					SALESTABLE.MODIFIEDTIME                            AS modified_time,
					SALESTABLE.MODIFIEDBY                              AS modified_by
				FROM SALESTABLE
				LEFT JOIN PROJTABLE AS WORKREPORT ON WORKREPORT.PROJID            = SALESTABLE.PROJID    AND WORKREPORT.DATAAREAID           = SALESTABLE.DATAAREAID
				LEFT JOIN PROJTABLE AS WORKORDER  ON WORKORDER.PROJID             = WORKREPORT.PARENTID  AND WORKORDER.DATAAREAID            = SALESTABLE.DATAAREAID
				LEFT JOIN RTDPROJORDERTABLE       ON RTDPROJORDERTABLE.PROJID     = WORKORDER.PARENTID   AND RTDPROJORDERTABLE.DATAAREAID    = SALESTABLE.DATAAREAID AND RTDPROJORDERTABLE.PROJID <> \'\'
				LEFT JOIN RTDEMPLPERWORKREPORT    ON RTDEMPLPERWORKREPORT.PROJID  = SALESTABLE.PROJID    AND RTDEMPLPERWORKREPORT.DATAAREAID = SALESTABLE.DATAAREAID AND RTDEMPLPERWORKREPORT.TASKID = \'Crew Leader\'
				LEFT JOIN RTDSALESPROCEDURE       ON RTDSALESPROCEDURE.SALESID    = SALESTABLE.SALESID   AND RTDSALESPROCEDURE.DATAAREAID    = SALESTABLE.DATAAREAID
				WHERE
					WORKREPORT.RTDPROJORDERLEVEL = 3';
			
				
			$work_report = $ax_conn->prepare($query);

			$work_report->setFetchMode(PDO::FETCH_NAMED);
			$work_report->execute();

			$return_data = $work_report->fetchAll();

			return $this->fix_padding($return_data);
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
	function sales_items($options = NULL) {
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
	 *  Consumables
	 *
	 *	Options: 
	 *	Defaults: 
	 *
	 */
	function consumables($options = NULL) {
		$query = 
			'SELECT 
				INVENTTABLE.ITEMID                      AS id,
				INVENTTABLE.DATAAREAID                  AS company_id,
				LTRIM(PRICEDISCTABLE.INVENTDIMID)       AS dimension_id,
				INVENTTABLE.ITEMNAME                    AS name,
				PRICEDISCTABLE.UNITID                   AS unit,
				PRICEDISCTABLE.RTDPRICETYPE             AS price_type,
				PRICEDISCTABLE.AMOUNT                   AS price
			FROM
				INVENTTABLE     
				JOIN PRICEDISCTABLE  ON PRICEDISCTABLE.ITEMRELATION = INVENTTABLE.ITEMID AND PRICEDISCTABLE.DATAAREAID = INVENTTABLE.DATAAREAID
			WHERE
				INVENTTABLE.RTDFILMIND = 1
				AND MODELGROUPID = \'CONSUMABLE\'
				AND RTDPROBEFUNCTION = \'WR\'
			ORDER BY PRICEDISCTABLE.RTDITEMNAME';
	}

	/*
	 *  Approval
	 *
	 *	Options: 
	 *	Defaults: 
	 *
	 */
	function set_approval($projid=NULL, $dataAreaID=NULL, $emplID=NULL, $approved=TRUE) {
		if( $employee = $this->employee() ) {
			$query = 'UPDATE Salestable SET RtdApproved = :approved WHERE PROJID = :projid AND DATAAREAID = :dataAreaID';

			$approval = $this->conn->prepare($query);

			if($approved) {
				$approval->bindValue('approved', 1, PDO::PARAM_STR);
			} else {
				$approval->bindValue('approved', 0, PDO::PARAM_STR);
			}
			$approval->bindValue('projid', $projid, PDO::PARAM_STR);
			$approval->bindValue('emplID', $projid, PDO::PARAM_STR);
			$approval->bindValue('dataAreaID', $dataAreaID, PDO::PARAM_STR);
			
			$approval->execute();
			//$approval->bindValue('emplID', $dataAreaID, PDO::PARAM_STR, 3);
		}
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

			$this->EE->load->library('typography');
			$this->EE->typography->initialize();

			// Get work report and associated items...
			$mats_query			= $this->get_materials($id);
			$items_query		= $this->get_items($id);
			$report_query		= $this->get_reports($id);
			$resources_query	= $this->get_resources($id);

			$dir = '/ax-public/'.$report_query['company'].'/Project Management/'.$report_query['customer_account'].' '.$report_query['customer_name'].'/';
			$file = $report_query['order'].' '.$report_query['order'].'.'.$report_query['work_order'].'.'.$report_query['work_report'].' [TEST].xml';

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
				$empl_id = $doc->createElement('EmplId', htmlentities( $report_query['submitter_id'], ENT_XML1, ISO-8859-1) );
				$empl_id = $xml_root->appendChild($empl_id);

				// Create and append Crew Leader ID
				$crew_leader = $doc->createElement('CrewLeader', htmlentities( $report_query['crew_leader'], ENT_XML1, ISO-8859-1) );
				$crew_leader = $xml_root->appendChild($crew_leader);

				// Create and append company
				$company = $doc->createElement('company');
				$company = $xml_root->appendChild($company);

				// Fill company with work report elements
				$company->appendChild($doc->createElement('Company', 				htmlentities( $report_query['company'], 			ENT_XML1, ISO-8859-1) ));
				$company->appendChild($doc->createElement('CustomerAccount', 		htmlentities( $report_query['customer_account'], 	ENT_XML1, ISO-8859-1) ));
				$company->appendChild($doc->createElement('Order', 					htmlentities( $report_query['order'], 				ENT_XML1, ISO-8859-1) ));
				$company->appendChild($doc->createElement('WorkOrder', 				htmlentities( $report_query['work_order'], 			ENT_XML1, ISO-8859-1) ));
				$company->appendChild($doc->createElement('WorkReport', 			htmlentities( $report_query['work_report'], 		ENT_XML1, ISO-8859-1) ));
				$company->appendChild($doc->createElement('CustomerReference',		htmlentities( $report_query['customer_reference'],	ENT_XML1, ISO-8859-1) ));
				$company->appendChild($doc->createElement('RTDReference', 			htmlentities( $report_query['rtd_reference'], 		ENT_XML1, ISO-8859-1) ));
				$company->appendChild($doc->createElement('WorkLocationName', 		htmlentities( $report_query['object_description'],	ENT_XML1, ISO-8859-1) ));
				$company->appendChild($doc->createElement('CONTACTPERSON', 			htmlentities( $report_query['work_location_name'],	ENT_XML1, ISO-8859-1) ));
				$company->appendChild($doc->createElement('ObjectDescription',		htmlentities( $report_query['object_description'],	ENT_XML1, ISO-8859-1) )); // $this->EE->typography->parse_type($report_query['object_description'],array('text_format' => 'none'))
				$company->appendChild($doc->createElement('ExecutionDate', 			date( 'Y-m-d', $report_query['execution_date']) ));

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