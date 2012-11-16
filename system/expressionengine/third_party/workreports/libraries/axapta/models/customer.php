<?php
class customer extends axapta {
	protected $id                         = 'LTRIM(CustTable.AccountNum)';
	//protected $account_num                = 'CustTable.AccountNum';
	protected $name                       = 'CustTable.Name';
	protected $address                    = 'CustTable.Address';
	protected $phone                      = 'CustTable.Phone';
	protected $fax                        = 'CustTable.TELEFAX';
	protected $main_contact_id            = 'SMMBUSRELTABLE.MAINCONTACT';
	protected $blocked                    = 'CUSTTABLE.BLOCKED';
	protected $business_relation_account  = 'smmBusRelTable.BusRelAccount';
	protected $company_id                 = 'CustTable.DATAAREAID';
	protected $department_id              = 'CustTable.DIMENSION';
	protected $cost_center_id             = 'CustTable.DIMENSION2_';

	function __construct($conn){
		$this->conn =& $conn;
		$this->properties = $this->get_properties();
	}
	
	/*
	 *  Customers
	 *
	 */
	function get_remote($options = NULL) {

		$query = $this->build_SELECT();

		$query .= 'FROM CustTable'.NL;
		$query .= 'LEFT JOIN smmBusRelTable ON smmBusRelTable.CustAccount = CustTable.AccountNum'.NL;

		$query .= $this->build_WHERE($options);

		if( isset($_GET['output']) && $_GET['output'] == 'debug' ){
			echo '<pre>'.$query.'</pre>';
			echo '<pre>';
			print_r($options);
			echo '</pre>';
		}

		$customers = $this->conn->prepare($query);

		$this->bind_option_values($customers, $options);

		$customers->setFetchMode(PDO::FETCH_NAMED);
		$customers->execute();

		return $this->fix_padding($customers->fetchAll());
	}
}