<?php
class dispatch_list extends axapta {
	protected $employee_id                = 'RTDEMPLPERWORKREPORT.EMPLID';
	protected $sales_id                   = 'RTDEMPLPERWORKREPORT.SALESID';
	//protected $sales_name                 = 'SALESTABLE.SALESNAME';  //same as customer name
	protected $project_id                 = 'RTDEMPLPERWORKREPORT.PROJID';

	protected $customer_id                = 'CUSTTABLE.ACCOUNTNUM';
	protected $customer_name              = 'CUSTTABLE.NAME';
	//protected $invoice_account          = 'SALESTABLE.INVOICEACCOUNT';

	protected $rtd_refrence               = 'SALESTABLE.RTDPROJORDERREFERENCE';
	protected $customer_refrence          = 'SALESTABLE.CUSTOMERREF';

	protected $execution_date             = 'CONVERT(DATE, SALESTABLE.DELIVERYDATE)';
	protected $execution_time             = 'SALESTABLE.RTDSTARTTIME';

	protected $work_location_name         = 'SALESTABLE.DELIVERYNAME';
	protected $customer_contact_person_id = 'SALESTABLE.CONTACTPERSONID';


	function __construct($conn){
		$this->conn =& $conn;
		$this->properties = $this->get_properties();
	}
	
	/*
	 *  Sales Items
	 *
	 *	Options: 
	 *	Defaults: 
	 *
	 */
	function get_remote($options = NULL) {
		$query = $this->build_SELECT();

		$query .= 'FROM RTDEMPLPERWORKREPORT'.NL;
		$query .= 'LEFT JOIN SALESTABLE                  ON RTDEMPLPERWORKREPORT.PROJID = SALESTABLE.PROJID             AND SALESTABLE.DATAAREAID        = RTDEMPLPERWORKREPORT.DATAAREAID'.NL;
		$query .= 'LEFT JOIN CUSTTABLE                   ON SALESTABLE.CUSTACCOUNT      = CUSTTABLE.ACCOUNTNUM          AND CUSTTABLE.DATAAREAID         = RTDEMPLPERWORKREPORT.DATAAREAID'.NL;
		$query .= 'LEFT JOIN EMPLTABLE                   ON EMPLTABLE.EMPLID            = RTDEMPLPERWORKREPORT.EMPLID   AND EMPLTABLE.DATAAREAID         = RTDEMPLPERWORKREPORT.DATAAREAID'.NL;
		$query .= 'LEFT JOIN PROJTABLE AS WORKREPORT     ON WORKREPORT.PROJID           = RTDEMPLPERWORKREPORT.PROJID   AND WORKREPORT.DATAAREAID        = RTDEMPLPERWORKREPORT.DATAAREAID'.NL;
		$query .= 'LEFT JOIN PROJTABLE AS WORKORDER      ON WORKORDER.PROJID            = WORKREPORT.PARENTID           AND WORKORDER.DATAAREAID         = RTDEMPLPERWORKREPORT.DATAAREAID'.NL;
		$query .= 'LEFT JOIN RTDPROJORDERTABLE           ON RTDPROJORDERTABLE.PROJID    = WORKORDER.PARENTID            AND RTDPROJORDERTABLE.DATAAREAID = RTDEMPLPERWORKREPORT.DATAAREAID'.NL;

		$query .= $this->build_WHERE($options);

		if( isset($_GET['output']) && $_GET['output'] == 'debug' ){
			echo '<pre>'.$query.'</pre>';
			echo '<pre>';
			print_r($options);
			echo '</pre>';
		}

		$dispatch_list = $this->conn->prepare($query);

		$this->bind_option_values($dispatch_list, $options);

		$dispatch_list->setFetchMode(PDO::FETCH_NAMED);
		$dispatch_list->execute();

		$return_data = $dispatch_list->fetchAll();

		foreach ($return_data as &$data_row) {
			//fix modified and created dates into unix timestamps
			//$data_row['modified_datetime'] = strtotime($data_row['modified_date']) + ($data_row['modified_time']/1000);
			//$data_row['created_datetime']  = strtotime($data_row['created_date']) + ($data_row['created_time']/1000);

			$data_row['execution_datetime']  = strtotime($data_row['execution_date']) + ($data_row['execution_time']/1000);
		}

		return $this->fix_padding( $return_data );
	}
}