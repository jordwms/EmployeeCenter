<?php
class cost_center extends axapta {
	protected $id             = 'DIMENSIONS.NUM';
	protected $company_id     = 'DIMENSIONS.DATAAREAID';
	protected $company_group  = 'DIMENSIONS.COMPANYGROUP';
	protected $name           = 'ADDRESS.NAME';
	protected $address        = 'ADDRESS.ADDRESS';
	protected $phone          = 'ADDRESS.PHONE';
	protected $fax            = 'ADDRESS.TELEFAX';
	protected $email          = 'ADDRESS.EMAIL';
	protected $street         = 'ADDRESS.STREET';
	protected $city           = 'ADDRESS.CITY';
	protected $state          = 'ADDRESS.STATE';
	protected $zipcode        = 'ADDRESS.ZIPCODE';
	protected $country        = 'ADDRESS.COUNTRY';
	protected $dimension_code = 'DIMENSIONS.DIMENSIONCODE';

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