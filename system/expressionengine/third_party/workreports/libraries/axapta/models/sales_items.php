<?php
class sales_items extends axapta {
	protected $id             = 'SALESLINE.ITEMID';
	protected $name           = 'SALESLINE.RTDITEMNAME';
	protected $unit           = 'SALESLINE.SALESUNIT';
	protected $project_id     = 'SALESTABLE.PROJID';
	protected $work_center_id = 'SALESLINE.RTDWrkCtrID';
	protected $dimension_id   = 'LTRIM(SALESLINE.INVENTDIMID)';

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

		$query .= 'FROM SALESTABLE'.NL;
		$query .= 'LEFT JOIN SALESLINE ON SALESTABLE.SALESID = SALESLINE.SALESID AND SALESTABLE.DATAAREAID = SALESLINE.DATAAREAID'.NL;

		$query .= $this->build_WHERE($options);

		$query .= NL.'ORDER BY SALESLINE.RTDITEMNAME, SALESLINE.SALESUNIT';

		if( $_GET['output'] == 'debug' ){
			echo '<pre>QUERY:'.NL.$query.'</pre>';
			echo '<pre>OPTIONS:';
			print_r($options);
			echo '</pre>';
		}

		$sales_items = $this->conn->prepare($query);

		$this->bind_option_values($sales_items, $options);

		$sales_items->setFetchMode(PDO::FETCH_NAMED);
		$sales_items->execute();

		return $sales_items->fetchAll();
	}
}