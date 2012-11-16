<?php
class cost_center extends axapta {
	protected $id             = 'DIMENSIONS.NUM';           //varchar(10)
	protected $company_id     = 'DIMENSIONS.DATAAREAID';    //varchar(3)
	protected $company_group  = 'DIMENSIONS.COMPANYGROUP';  //varchar(10)
	protected $name           = 'ADDRESS.NAME';             //varchar(60)
	protected $address        = 'ADDRESS.ADDRESS';          //varchar(250)
	protected $phone          = 'ADDRESS.PHONE';            //varchar(20)
	protected $fax            = 'ADDRESS.TELEFAX';          //varchar(20)
	protected $email          = 'ADDRESS.EMAIL';            //varchar(80)
	protected $street         = 'ADDRESS.STREET';           //varchar(250)
	protected $city           = 'ADDRESS.CITY';             //varchar(60)
	protected $state          = 'ADDRESS.STATE';            //varchar(10)
	protected $zipcode        = 'ADDRESS.ZIPCODE';          //varchar(10)
	protected $country        = 'ADDRESS.COUNTRY';          //varchar(60)
	protected $dimension_code = 'DIMENSIONS.DIMENSIONCODE'; //int(10)

	function __construct($conn){
		$this->conn =& $conn;
		$this->properties = $this->get_properties();
	}
	
	/*
	 *  Cost Center
	 *	Option: id         (default: employee['cost_center_id'])
	 *	Option: company_id (default: employee['company_id'])
	 */
	function get_remote($options = NULL) {
		//DIMENSIONCODE = 1 limits dimensions to only "cost centers", we'll make sure it's alway set here:
		if( !is_array($options) ){
			$options = array('dimension_code' => '1');
		} else {
			$options['dimension_code'] = '1';
		}

		$query = $this->build_SELECT();

		$query .= 'FROM DIMENSIONS'.NL;
		$query .= 'LEFT JOIN ADDRESS ON DIMENSIONS.RTDADDRESS = ADDRESS.RECID'.NL;

		$query .= $this->build_WHERE($options);

		if( isset($_GET['output']) && $_GET['output'] == 'debug' ){
			echo '<pre>'.$query.'</pre>';
			echo '<pre>';
			print_r($options);
			echo '</pre>';
		}

		$cost_center = $this->conn->prepare($query);

		$this->bind_option_values($cost_center, $options);

		$cost_center->setFetchMode(PDO::FETCH_NAMED);
		$cost_center->execute();

		$return_data = $cost_center->fetchAll();
		return $this->fix_padding($return_data);
	}
}