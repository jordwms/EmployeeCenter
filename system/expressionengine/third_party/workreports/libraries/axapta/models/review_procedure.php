<?php
class review_procedure extends axapta {
	protected $id           = 'REVIEWPROCEDUREID';
	protected $description  = 'DESCRIPTION';
	protected $customer_id  = 'CUSTACCOUNT';
	protected $active       = 'ACTIVE';
	protected $pdf          = 'DOCUREF.NAME';
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
		$query .= 'LEFT JOIN DOCUREF ON DOCUREF.REFRECID = RTDREVIEWPROCEDURE.RECID'.NL;

		$query .= $this->build_WHERE( $options );

		if( isset($_GET['output']) && $_GET['output'] == 'debug' ){
			echo '<pre>'.$query.'</pre>';
			echo '<pre>';
			print_r($options);
			echo '</pre>';
		}

		$stmt = $this->conn->prepare($query);

		$this->bind_option_values( $stmt, $options );

		$stmt->setFetchMode(PDO::FETCH_NAMED);
		$stmt->execute();

		$return_data = $stmt->fetchAll();

		foreach ($return_data as &$row) {
			$row['pdf_link'] = 'https://portal.applusrtd.com/Knowledge/PolProc/Verification%20Procedures/'.$row['pdf'];
		}

		return $this->fix_padding( $return_data );
	}
}