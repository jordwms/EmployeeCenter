<?php
class employee extends axapta {
	/* 
	 *	these are the model properties
	 *	they are defined as: $property_name = AXAPTAFIELD with any SQL operators (ie date formating)
	 */
	protected $id                 = 'EMPLID';
	protected $name               = 'NAME';
	protected $title              = 'TITLE';
	protected $axapta_id          = 'USERID';

	protected $status             = 'STATUS';
	protected $internal_external  = 'INTERNALEXTERNAL';
	protected $hrm_active_flag    = 'HRMACTIVEINACTIVE';

	protected $company_id         = 'DATAAREAID';
	protected $department_id      = 'DIMENSION';
	protected $cost_center_id     = 'DIMENSION2_';

	protected $email              = 'EMAIL';
	protected $phone              = 'PHONE';
	protected $cell_phone         = 'CELLULARPHONE';
	protected $personal_phone     = 'RTDPRIVATEPHONE';
	protected $fax                = 'TELEFAX';

	protected $address            = 'ADDRESS';
	protected $street             = 'STREET';
	protected $city               = 'CITY';
	protected $county             = 'COUNTYID';
	protected $state              = 'STATEID';
	protected $zip_code           = 'ZIPCODEID';
	protected $country            = 'COUNTRYID';

	protected $currency           = 'CURRENCY';

	protected $birth_date         = 'CONVERT(DATE,BIRTHDATE)';

	protected $modified_date      = 'CONVERT(DATE,MODIFIEDDATE)';
	protected $modified_time      = 'MODIFIEDTIME';
	protected $modified_by        = 'MODIFIEDBY';

	protected $created_date       = 'CONVERT(DATE,CREATEDDATE)';
	protected $created_time       = 'CREATEDTIME';
	protected $created_by         = 'CREATEDBY';


	function __construct($conn, $test = NULL){
		$this->conn =& $conn;
		$this->properties = $this->get_properties();
	}

	/*
	 *	Employee Details
	 *	
	 *	Defaults: EMAIL = current user email
	 *	Options: ANY COLUMN
	 *
	 */
	function get_remote($options = NULL) {
		/*
		 *  Employee information
		 */
<<<<<<< HEAD

		/* handy dubug defaults */
		//$options = array('company' => '107';
		//$options = array('email' => 'chet.yates@applusrtd.com');
		//$options = array('email' => 'bert.weber@applusrtd.com');
=======
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

		// handy debug defaults;
		// $options = array('DATAAREAID' => '107';
		// $options = array('email' => 'chet.yates@applusrtd.com');
		// $options = array('email' => 'bert.weber@applusrtd.com');
>>>>>>> JW

		//select all properties defined at top of class
		$query = $this->build_select();

		//from statement
		$query .= 'FROM EMPLTABLE'.NL;

		//build WHERE statements from passed options
		$query .= $this->build_WHERE($options);

		if( $_GET['output'] == 'debug' ){
			echo '<pre>'.$query.'</pre>';
			echo '<pre>';
			print_r($options);
			echo '</pre>';
		}

		//create the prepared statement
		$employee_info = $this->conn->prepare($query);

		//bind all option values
		$this->bind_option_values($employee_info, $options);

		//fetch all records
		$employee_info->setFetchMode(PDO::FETCH_NAMED);
		$employee_info->execute();
		$employees = $employee_info->fetchAll();

		foreach ($employees as &$employee) {
			//fix employee name so we have a nice "First Last" setup
			$exploded_name = explode(',', $employee['name']);
			if(count($exploded_name) > 1){
				$employee['name_first_last'] = ltrim($exploded_name[1]).' '.$exploded_name[0];
			} else {
				$employee['name_first_last'] = '';
			}

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
			$employee_groups = $this->conn->prepare(
				"SELECT
					HRMVIRTUALNETWORKHISTORY.DATAAREAID        AS company_id,
					HRMVIRTUALNETWORKHISTORY.HRMPOSITIONID     AS position_id
				FROM HRMVIRTUALNETWORKTABLE
				JOIN HRMVIRTUALNETWORKHISTORY ON HRMVIRTUALNETWORKTABLE.HRMVIRTUALNETWORKID = HRMVIRTUALNETWORKHISTORY.HRMVIRTUALNETWORKID
				WHERE REFERENCE = :id"
			);
			$employee_groups->bindParam(':id', $employee['id'], PDO::PARAM_STR, 12);
			$employee_groups->setFetchMode(PDO::FETCH_NAMED);
			$employee_groups->execute();

			foreach ($employee_groups->fetchALL() as $group) {
				if( !array_key_exists($group['company_id'], $employee['groups'])){
					$employee['groups'][$group['company_id']] = array();
				}
				array_push($employee['groups'][$group['company_id']], $group['position_id'] );
			}
		}
		return $this->fix_padding($employees);
	}
}