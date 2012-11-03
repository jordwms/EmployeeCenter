<?php
class contract_items extends axapta {
	protected $id                  = 'LTRIM(PRICEDISCTABLE.INVENTDIMID)';
	protected $item_id             = 'LTRIM(INVENTTABLE.ITEMID)';
	protected $name                = 'PRICEDISCTABLE.RTDITEMNAME';
	protected $config_name         = 'CONFIGTABLE.NAME';
	protected $unit                = 'PRICEDISCTABLE.UNITID';
	protected $price_type          = 'PRICEDISCTABLE.RTDPRICETYPE';
	protected $price               = 'PRICEDISCTABLE.AMOUNT';
	protected $currency            = 'PRICEDISCTABLE.CURRENCY';

	protected $contract_id         = 'RTDCONTRACT.CONTRACTID';
	//protected $contract_valid      = 'RTDCONTRACT.VALID';
	//protected $contract_valid_from = 'RTDCONTRACT.VALIDFROM';
	//protected $contract_valit_to   = 'RTDCONTRACT.VALIDTO';

	protected $film_indicator      = 'INVENTTABLE.RTDFILMIND';

	function __construct($conn){
		$this->conn =& $conn;
		$this->properties = $this->get_properties();
	}
	
	/*
	 *  Materials
	 *
	 *	Options: 
	 *	Defaults: 
	 *
	 */
	function get_remote($options = NULL) {

		$query = $this->build_SELECT();

		$query .= 'FROM PRICEDISCTABLE'.NL;
		$query .= 'JOIN RTDCONTRACT     ON RTDCONTRACT.CONTRACTID = LTRIM(PRICEDISCTABLE.ACCOUNTRELATION) AND RTDCONTRACT.DATAAREAID = PRICEDISCTABLE.DATAAREAID'.NL;
		$query .= 'JOIN INVENTTABLE     ON PRICEDISCTABLE.ITEMRELATION = INVENTTABLE.ITEMID AND INVENTTABLE.DATAAREAID = PRICEDISCTABLE.DATAAREAID'.NL;
		$query .= 'JOIN INVENTDIM       ON INVENTDIM.INVENTDIMID = PRICEDISCTABLE.INVENTDIMID AND INVENTDIM.DATAAREAID = PRICEDISCTABLE.DATAAREAID'.NL;
		$query .= 'JOIN CONFIGTABLE     ON CONFIGTABLE.CONFIGID = INVENTDIM.CONFIGID AND CONFIGTABLE.ITEMID = INVENTTABLE.ITEMID AND CONFIGTABLE.DATAAREAID = PRICEDISCTABLE.DATAAREAID'.NL;

		$query .= $this->build_WHERE( $options );

		$query .= NL.'ORDER BY PRICEDISCTABLE.RTDITEMNAME';

		if( isset($_GET['output']) && $_GET['output'] == 'debug' ){
			echo '<pre>'.$query.'</pre>';
			echo '<pre>';
			print_r($options);
			echo '</pre>';
		}

		$contract_items = $this->conn->prepare($query);

		$this->bind_option_values( $contract_items, $options );
		$contract_items->setFetchMode(PDO::FETCH_NAMED);
		$contract_items->execute();

		return $this->fix_padding( $contract_items->fetchAll() );
	}
}