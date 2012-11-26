<?php
class research_procedure extends axapta {
	protected $id           = 'RTDRESEARCHPROCEDURE.RESEARCHPROCEDUREID';
	protected $description  = 'RTDRESEARCHPROCEDURE.DESCRIPTION';
	protected $customer_id  = 'RTDRESEARCHPROCEDURE.CUSTACCOUNT';
	protected $active       = 'RTDRESEARCHPROCEDURE.ACTIVE';
	protected $pdf          = 'DOCUREF.NAME';
	protected $compnay_id   = 'RTDRESEARCHPROCEDURE.DATAAREAID';

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
		$query .= 'LEFT JOIN DOCUREF ON DOCUREF.REFRECID = RTDRESEARCHPROCEDURE.RECID'.NL;
		
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
			$row['pdf_link'] = 'https://portal.applusrtd.com/Search/Pages/Results.aspx?k='.$row['pdf'].'&s=All%20Sites';
		}

		return $this->fix_padding( $return_data );
	}
}