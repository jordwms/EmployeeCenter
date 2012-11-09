<?php
class research_procedure extends axapta {
	protected $id           = 'RESEARCHPROCEDUREID';
	protected $description  = 'DESCRIPTION';
	protected $customer_id  = 'CUSTACCOUNT';
	protected $active       = 'ACTIVE';
	protected $compnay_id   = 'DATAAREAID';

	function __construct($conn){
		$this->conn =& $conn;
		$this->properties = $this->get_properties();
	}
	
	/*
	 *  Research Procedures
	 */
	function get_remote($options = NULL) {
		$query = $this->build_SELECT();

		$query .= 'FROM RTDRESEARCHPROCEDURE'.NL;

		$query .= $this->build_WHERE( $options );

		if( isset($_GET['output']) && $_GET['output'] == 'debug' ){
			echo '<pre>'.$query.'</pre>';
			echo '<pre>';
			print_r($options);
			echo '</pre>';
		}

		$research_procedure = $this->conn->prepare($query);

		$this->bind_option_values( $research_procedure, $options );

		$research_procedure->setFetchMode(PDO::FETCH_NAMED);
		$research_procedure->execute();

		$return_data = $research_procedure->fetchAll();

		return $this->fix_padding( $return_data );
	}
}