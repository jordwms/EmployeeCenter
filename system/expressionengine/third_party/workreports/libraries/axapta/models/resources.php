<?php
class resources extends axapta {
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

		if( isset($_GET['output']) && $_GET['output'] == 'debug' ){
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


		return $this->fix_padding($employees);
	}
}