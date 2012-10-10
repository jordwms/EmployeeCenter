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
		$this->explode_datetime($options);

		//select all properties defined at top of class
		$query = $this->build_select();

		//from statement
		$query .= 'FROM EMPLTABLE'.NL;

		//build WHERE statements from passed options
		$query .= $this->build_WHERE($options);

		// if( isset($_GET['output']) && $_GET['output'] == 'debug' ){
		// 	echo '<pre>'.$query.'</pre>';
		// 	echo '<pre>';
		// 	print_r($options);
		// 	echo '</pre>';
		// }

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
			$employee['modified_datetime'] = strtotime($employee['modified_date']) + $employee['modified_time'];
			$employee['created_datetime']  = strtotime($employee['created_date']) + $employee['created_time'];

			
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