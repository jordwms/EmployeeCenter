<?php
class dispatch_list extends axapta {
	protected $employee_id                = 'RTDEMPLPERWORKREPORT.EMPLID';
	protected $project_id                 = 'RTDEMPLPERWORKREPORT.PROJID';

	protected $status                     = 'SALESTABLE.RTDAPPROVED';
	protected $project_status             = 'RTDPROJORDERTABLE.PROJORDERSTATUS';
	protected $invoiced_status            = 'SALESTABLE.RTDINVOICED';

	protected $customer_id                = 'CUSTTABLE.ACCOUNTNUM';
	protected $customer_name              = 'CUSTTABLE.NAME';
	protected $customer_blocked           = 'CUSTTABLE.BLOCKED';

	protected $execution_date             = 'CONVERT(DATE, SALESTABLE.DELIVERYDATE)';
	protected $execution_time             = 'SALESTABLE.RTDSTARTTIME';

	protected $created_date                = 'CONVERT(DATE, SALESTABLE.CREATEDDATE)';
	protected $created_time                = 'SALESTABLE.CREATEDTIME';
	protected $created_by                  = 'SALESTABLE.CREATEDBY';

	protected $modified_date               = 'CONVERT(DATE, SALESTABLE.MODIFIEDDATE)';
	protected $modified_time               = 'SALESTABLE.MODIFIEDTIME';
	protected $modified_by                 = 'SALESTABLE.MODIFIEDBY';


	function __construct($conn){
		$this->conn =& $conn;
		$this->properties = $this->get_properties();
	}
	
	/*
	 *  dispatch list
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