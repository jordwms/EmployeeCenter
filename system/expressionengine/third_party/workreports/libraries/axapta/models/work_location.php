<?php
class work_location extends axapta {
	protected $id             = '[ADDRESS].RTDLOCATIONID';
	protected $type           = '[ADDRESS].TYPE';
	protected $company_id     = 'CUSTTABLE.DATAAREAID';
	protected $department_id  = 'CUSTTABLE.DIMENSION';
	protected $cost_center_id = 'CUSTTABLE.DIMENSION2_';
	protected $name           = '[ADDRESS].NAME';
	protected $customer_id    = 'LTRIM(CUSTTABLE.ACCOUNTNUM)';
	protected $customer_name  = 'CUSTTABLE.NAME';
	protected $address        = '[ADDRESS].ADDRESS';
	protected $street         = '[ADDRESS].STREET';
	protected $city           = '[ADDRESS].CITY';
	protected $state          = '[ADDRESS].STATE';
	protected $zip_code       = '[ADDRESS].ZIPCODE';
	protected $country        = '[ADDRESS].COUNTRY';

	function __construct($conn){
		$this->conn =& $conn;
		$this->properties = $this->get_properties();
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
	function get_remote($options = NULL) {
		//set some defaults
		if( !is_array($options) ){
			$options = array('type' => '99');
		} else {
			$options['type'] = '99';
		}

		$query = $this->build_SELECT();

		$query .= 'FROM CUSTTABLE'.NL;
		$query .= 'JOIN [ADDRESS] ON [ADDRESS].ADDRRECID = CUSTTABLE.RECID AND [ADDRESS].DATAAREAID = CUSTTABLE.DATAAREAID'.NL;

		$query .= $this->build_WHERE($options);

		$query .= NL.'ORDER BY CUSTTABLE.NAME, [ADDRESS].NAME';

		if( isset($_GET['output']) && $_GET['output'] == 'debug' ){
			echo '<pre>'.$query.'</pre>';
			echo '<pre>';
			print_r($options);
			echo '</pre>';
		}

		$work_location = $this->conn->prepare($query);

		$this->bind_option_values($work_location, $options);

		$work_location->setFetchMode(PDO::FETCH_NAMED);
		$work_location->execute();
		
		$return_data = $work_location->fetchAll();

		return $this->fix_padding($return_data);
	}
}