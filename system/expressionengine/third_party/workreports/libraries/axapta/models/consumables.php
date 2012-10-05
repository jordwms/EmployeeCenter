<?php
class consumables extends Axapta {
	protected $id             = 'INVENTTABLE.ITEMID';
	protected $type           = 'MODELGROUPID';
	protected $company_id     = 'INVENTTABLE.DATAAREAID';
	protected $dimension_id   = 'LTRIM(PRICEDISCTABLE.INVENTDIMID)';
	protected $name           = 'INVENTTABLE.ITEMNAME';
	protected $unit           = 'PRICEDISCTABLE.UNITID';
	protected $price_type     = 'PRICEDISCTABLE.RTDPRICETYPE';
	protected $price          = 'PRICEDISCTABLE.AMOUNT';
	protected $film_indicator = 'INVENTTABLE.RTDFILMIND';

	function __construct($conn){
		$this->conn =& $conn;
		$this->properties = $this->get_properties();
	}
	
	/*
	 *  Consumables
	 *
	 *	Options: 
	 *	Defaults: 
	 *
	 */
	function get($options = NULL) {		
		$query = $this->build_SELECT();
		$query .= 'FROM	INVENTTABLE'.NL;
		$query .= 'JOIN PRICEDISCTABLE  ON PRICEDISCTABLE.ITEMRELATION = INVENTTABLE.ITEMID AND PRICEDISCTABLE.DATAAREAID = INVENTTABLE.DATAAREAID'.NL;

		$query .= $this->build_WHERE($options);	

		if( $_GET['output'] == 'debug' ){
			echo '<pre>'.$query.'</pre>';
			echo '<pre>';
			print_r($options);
			echo '</pre>';
		}

			// WHERE
			// 	INVENTTABLE.RTDFILMIND = 1
			// 	AND MODELGROUPID = \'CONSUMABLE\'
			// 	AND RTDPROBEFUNCTION = \'WR\' 
			// ORDER BY PRICEDISCTABLE.RTDITEMNAME';
	}
}