<?php
class company extends axapta {
	protected $id             = 'DATAAREAID';
	protected $name           = 'NAME';
	protected $address        = '[ADDRESS]';
	protected $phone          = 'PHONE';
	protected $street         = 'STREET';
	protected $city           = 'CITY';
	protected $state          = 'STATE';
	protected $zipcode        = 'ZIPCODE';
	protected $county         = 'COUNTY';
	protected $address_format = 'ADDRFORMAT';
	protected $company_prefix = 'RTDCOMPANYPREFIX';

	protected $modified_date  = 'CONVERT(DATE,MODIFIEDDATE)';
	protected $modified_time  = 'MODIFIEDTIME';
	protected $modified_by    = 'MODIFIEDBY';

	function __construct($conn){
		$this->conn =& $conn;
		$this->properties = $this->get_properties();
	}

	/*
	 *  Company Info
	 *	Options: id (default: employee['company_id'])
	 *
	 *	DataAreaID = Company ID
	 */
	function get_remote($options = NULL) {
		$this->explode_datetime($options);
		
		//select all properties defined at top of class
		$query = $this->build_SELECT();

		//from statement
		$query .= 'FROM COMPANYINFO'.NL;
		
		//build WHERE statements from passed options
		$query .= $this->build_WHERE($options);

		if( isset($_GET['output']) && $_GET['output'] == 'debug' ){
			echo '<pre>'.$query.'</pre>';
			echo '<pre>';
			print_r($options);
			echo '</pre>';
		}

		//create the prepared statement based on above query
		$company = $this->conn->prepare($query);

		//bind all option values
		$this->bind_option_values($company, $options);

		$company->setFetchMode(PDO::FETCH_NAMED);
		$company->execute();

		$return_data = $company->fetchAll();

		foreach ($return_data as $row => &$values) {
			//fix modified dates into unix timestamps
			$values['modified_datetime'] = strtotime($values['modified_date']) + $values['modified_time'];
		}

		return $this->fix_padding($return_data);
	}
}