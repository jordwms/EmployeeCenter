<?php
class resources extends axapta {
	protected $id           = 'EMPLTABLE.EMPLID';
	protected $name         = 'EMPLTABLE.NAME';
	protected $project_id   = 'RTDEMPLPERWORKREPORT.PROJID';

	function __construct($conn){
		$this->conn =& $conn;
		$this->properties = $this->get_properties();
	}
	
	/*
	 *  Resources
	 */
	function get_remote($options = NULL) {
		//set some defaults
		if( !is_array($options) ){

		} else {

		}

		$query = $this->build_SELECT();

		$query .= 'FROM RTDEMPLPERWORKREPORT'.NL;
		$query .= 'LEFT JOIN EMPLTABLE ON RTDEMPLPERWORKREPORT.EMPLID = EMPLTABLE.EMPLID AND RTDEMPLPERWORKREPORT.DATAAREAID = EMPLTABLE.DATAAREAID'.NL;
		
		$query .= $this->build_WHERE( $options );

		if( isset($_GET['output']) && $_GET['output'] == 'debug' ){
			echo '<pre>'.$query.'</pre>';
			echo '<pre>';
			print_r($options);
			echo '</pre>';
		}

		$resources = $this->conn->prepare($query);

		$this->bind_option_values( $resources, $options );

		$resources->setFetchMode(PDO::FETCH_NAMED);
		$resources->execute();

		$return_data = $resources->fetchAll();

		return $this->fix_padding( $return_data );
	}
}