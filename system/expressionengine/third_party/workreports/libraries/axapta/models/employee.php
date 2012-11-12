<?php
class employee extends axapta {
	/* 
	 *	these are the model properties
	 *	they are defined as: $property_name = AXAPTAFIELD with any SQL operators (ie date formating)
	 */
	protected $id                 = 'EMPLID';                     //varchar(20)
	protected $name               = 'NAME';                       //varchar(60)
	protected $title              = 'TITLE';                      //varchar(30)
	protected $axapta_id          = 'USERID';                     //varchar(5)

	protected $status             = 'STATUS';                     //int(10)
	protected $internal_external  = 'INTERNALEXTERNAL';           //int(10)
	protected $hrm_active_flag    = 'HRMACTIVEINACTIVE';          //int(10)

	protected $company_id         = 'DATAAREAID';                 //varchar(3)
	protected $department_id      = 'DIMENSION';                  //varchar(10)
	protected $cost_center_id     = 'DIMENSION2_';                //varchar(10)

	protected $email              = 'EMAIL';                      //varchar(80)
	protected $phone              = 'PHONE';                      //varchar(20)
	protected $cell_phone         = 'CELLULARPHONE';              //varchar(20)
	protected $personal_phone     = 'RTDPRIVATEPHONE';            //varchar(20)
	protected $fax                = 'TELEFAX';                    //varchar(20)

	protected $address            = 'ADDRESS';                    //varchar(250)
	protected $street             = 'STREET';                     //varchar(250)
	protected $city               = 'CITY';                       //varchar(60)
	protected $county             = 'COUNTYID';                   //varchar()
	protected $state              = 'STATEID';                    //varchar()
	protected $zip_code           = 'ZIPCODEID';                  //varchar(10)
	protected $country            = 'COUNTRYID';                  //varchar(10)

	protected $currency           = 'CURRENCY';                   //varchar(3)

	protected $birth_date         = 'CONVERT(DATE,BIRTHDATE)';    //datetime()

	protected $modified_date      = 'CONVERT(DATE,MODIFIEDDATE)'; //date()
	protected $modified_time      = 'MODIFIEDTIME';               //int(10)
	protected $modified_by        = 'MODIFIEDBY';                 //varchar(5)

	protected $created_date       = 'CONVERT(DATE,CREATEDDATE)';  //date()
	protected $created_time       = 'CREATEDTIME';                //int(10)
	protected $created_by         = 'CREATEDBY';                  //varchar(5)


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
		     *      [GROUP ID] => Array(
		     *          [0] => 'company_id'
		     *      ),
		     *      [WA TECH] => Array(
		     *          [0] => '102',
		     *          [1] => '002'
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
				if( !array_key_exists($group['position_id'], $employee['groups'])){
					$employee['groups'][$group['position_id']] = array();
				}
				array_push($employee['groups'][$group['position_id']], $group['company_id'] );
			}
		}
		return $this->fix_padding($employees);
	}
}