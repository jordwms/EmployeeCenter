<?php
class contact_person extends axapta {
	protected $id            = 'LTRIM(CONTACTPERSON.CONTACTPERSONID)';  //varchar(20)
	protected $name          = 'CONTACTPERSON.NAME';                    //varchar(60)
	protected $email         = 'CONTACTPERSON.EMAIL';                   //varchar(80)
	protected $phone         = 'CONTACTPERSON.PHONE';                   //varchar(20)
	protected $cell_phone    = 'CONTACTPERSON.CELLULARPHONE';           //varchar(20)

	protected $company_id    = 'CONTACTPERSON.DATAAREAID';              //varchar(3)
	protected $department_id = 'CUSTTABLE.DIMENSION';                   //varchar(10)
	protected $cost_center   = 'CUSTTABLE.DIMENSION2_';                 //varchar(10)

	protected $customer_id   = 'LTRIM(CUSTTABLE.ACCOUNTNUM)';           //varchar(20)
	protected $customer_name = 'CUSTTABLE.NAME';                        //varchar(60)


	function __construct($conn){
		$this->conn =& $conn;
		$this->properties = $this->get_properties();
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
	function get_remote($options = NULL) {
		$query = $this->build_SELECT();

		$query .= 'FROM CONTACTPERSON'.NL;
		$query .= 'LEFT JOIN CUSTTABLE ON LTRIM(CUSTTABLE.ACCOUNTNUM) = LTRIM(CONTACTPERSON.CUSTACCOUNT) AND CUSTTABLE.DATAAREAID = CONTACTPERSON.DATAAREAID'.NL;

		$query .= $this->build_WHERE($options);

		if( isset($_GET['output']) && $_GET['output'] == 'debug' ){
			echo '<pre>'.$query.'</pre>';
			echo '<pre>';
			print_r($options);
			echo '</pre>';
		}
		
		$contact_person = $this->conn->prepare($query);

		$this->bind_option_values($contact_person, $options);

		$contact_person->setFetchMode(PDO::FETCH_NAMED);
		$contact_person->execute();

		$return_data = $contact_person->fetchAll();

		return $this->fix_padding($return_data);
	}
}