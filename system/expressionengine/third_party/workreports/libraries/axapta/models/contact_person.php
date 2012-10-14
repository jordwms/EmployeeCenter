<?php
class contact_person extends axapta {
	protected $id            = 'CONTACTPERSON.CONTACTPERSONID';
	protected $name          = 'CONTACTPERSON.NAME';
	protected $email         = 'CONTACTPERSON.EMAIL';
	protected $phone         = 'CONTACTPERSON.PHONE';
	protected $cell_phone    = 'CONTACTPERSON.CELLULARPHONE';

	protected $company_id    = 'CONTACTPERSON.DATAAREAID';
	protected $department_id = 'CUSTTABLE.DIMENSION';
	protected $cost_center   = 'CUSTTABLE.DIMENSION2_';

	protected $customer_id   = 'LTRIM(CUSTTABLE.ACCOUNTNUM)';
	protected $customer_name = 'CUSTTABLE.NAME';


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
		$query .= 'LEFT JOIN CUSTTABLE ON CUSTTABLE.ACCOUNTNUM = CONTACTPERSON.CUSTACCOUNT AND CUSTTABLE.DATAAREAID = CONTACTPERSON.DATAAREAID'.NL;

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