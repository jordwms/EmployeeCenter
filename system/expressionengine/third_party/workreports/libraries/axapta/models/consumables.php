<?php
class consumables extends Axapta {
	protected $id             = 'INVENTTABLE.ITEMID';                 //varchar(10)
	protected $type           = 'INVENTTABLE.MODELGROUPID';           //varchar(10)
	protected $company_id     = 'INVENTTABLE.DATAAREAID';             //varchar(3)
	protected $dimension_id   = 'LTRIM(PRICEDISCTABLE.INVENTDIMID)';  //varchar(20)
	protected $name           = 'INVENTTABLE.ITEMNAME';               //varchar(60)
	protected $unit           = 'PRICEDISCTABLE.UNITID';              //varchar(10)
	protected $price_type     = 'PRICEDISCTABLE.RTDPRICETYPE';        //int(10)
	protected $price          = 'PRICEDISCTABLE.AMOUNT';              //numeric(28,12)
	protected $film_indicator = 'INVENTTABLE.RTDFILMIND';             //int(1)

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

		if( isset($_GET['output']) && $_GET['output'] == 'debug' ){
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