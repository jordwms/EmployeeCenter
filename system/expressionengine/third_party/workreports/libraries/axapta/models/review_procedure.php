<?php
class review_procedure extends axapta {
	protected $id           = 'REVIEWPROCEDUREID';
	protected $description  = 'DESCRIPTION';
	protected $customer_id  = 'CUSTACCOUNT';
	protected $active       = 'ACTIVE';
	protected $compnay_id   = 'DATAAREAID';

	function __construct($conn){
		$this->conn =& $conn;
		$this->properties = $this->get_properties();
	}
	
	/*
	 *  Review Procedures
	 */
	function get_remote($options = NULL) {
		$query = $this->build_SELECT();

		$query .= 'FROM RTDREVIEWPROCEDURE'.NL;

		$query .= $this->build_WHERE( $options );

		if( isset($_GET['output']) && $_GET['output'] == 'debug' ){
			echo '<pre>'.$query.'</pre>';
			echo '<pre>';
			print_r($options);
			echo '</pre>';
		}

		$review_procedure = $this->conn->prepare($query);

		$this->bind_option_values( $review_procedure, $options );

		$review_procedure->setFetchMode(PDO::FETCH_NAMED);
		$review_procedure->execute();

		$return_data = $review_procedure->fetchAll();

		return $this->fix_padding( $return_data );
	}
}