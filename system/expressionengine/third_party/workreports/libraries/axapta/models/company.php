<?php
class company extends axapta {
	protected $id             = 'DATAAREAID';                  //varchar(3)
	protected $name           = 'NAME';                        //varchar(60)
	protected $address        = '[ADDRESS]';                   //varchar(250)
	protected $phone          = 'PHONE';                       //varchar(20)
	protected $street         = 'STREET';                      //varchar(250)
	protected $city           = 'CITY';                        //varchar(60)
	protected $state          = 'STATE';                       //varchar(10)
	protected $zipcode        = 'ZIPCODE';                     //varchar(10)
	protected $county         = 'COUNTY';                      //varchar(10)
	protected $address_format = 'ADDRFORMAT';                  //varchar(10)
	protected $company_prefix = 'RTDCOMPANYPREFIX';            //varchar(10)

	protected $modified_date  = 'CONVERT(DATE,MODIFIEDDATE)';  //date()
	protected $modified_time  = 'MODIFIEDTIME';                //int(10)
	protected $modified_by    = 'MODIFIEDBY';                  //varchar(5)

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