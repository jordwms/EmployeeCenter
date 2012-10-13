<?php
class materials extends axapta {
	protected $id                  = 'INVENTTABLE.ITEMID';
	protected $dimension_id        = 'LTRIM(PRICEDISCTABLE.INVENTDIMID)';
	protected $project_id          = 'SALESTABLE.PROJID';
	protected $item_name           = 'PRICEDISCTABLE.RTDITEMNAME';
	protected $name                = 'CONFIGTABLE.NAME';
	protected $unit                = 'PRICEDISCTABLE.UNITID';
	protected $amount              = 'PRICEDISCTABLE.AMOUNT';
	protected $contract_id         = 'RTDCONTRACT.CONTRACTID';
	protected $contract_valid      = 'RTDCONTRACT.VALID';
	protected $contract_valid_from = 'CONVERT(DATE, RTDCONTRACT.VALIDFROM)';
	protected $contract_valit_to   = 'CONVERT(DATE, RTDCONTRACT.VALIDTO)';
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
		//set some defaults
		if( !is_array($options) ){
			//$options = array('contract_valid' => '1');
			$options = array('film_indicator' => '1');
		} else {
			//$options['contract_valid'] = '1';
			$options['film_indicator'] = '1';
		}

		$query = $this->build_SELECT();

		$query .= 'FROM PROJTABLE'.NL;
		$query .= 'JOIN SALESTABLE      ON PROJTABLE.PROJID = SALESTABLE.projID AND PROJTABLE.DATAAREAID = SALESTABLE.DATAAREAID'.NL;
		$query .= 'JOIN PRICEDISCTABLE  ON LTRIM(SALESTABLE.PriceGroupID) = LTRIM(PRICEDISCTABLE.AccountRelation) AND PROJTABLE.DATAAREAID = PRICEDISCTABLE.DATAAREAID'.NL;
		$query .= 'JOIN RTDCONTRACT     ON RTDCONTRACT.CONTRACTID = LTRIM(RTRIM(SALESTABLE.PriceGroupID)) AND PROJTABLE.DATAAREAID = RTDCONTRACT.DATAAREAID'.NL;
		$query .= 'JOIN INVENTTABLE     ON PRICEDISCTABLE.ITEMRELATION = INVENTTABLE.ITEMID AND PROJTABLE.DATAAREAID = INVENTTABLE.DATAAREAID'.NL;
		$query .= 'JOIN INVENTDIM       ON INVENTDIM.INVENTDIMID = PRICEDISCTABLE.INVENTDIMID AND PROJTABLE.DATAAREAID = INVENTDIM.DATAAREAID'.NL;
		$query .= 'JOIN CONFIGTABLE     ON CONFIGTABLE.CONFIGID = INVENTDIM.CONFIGID AND CONFIGTABLE.ITEMID = INVENTTABLE.ITEMID AND PROJTABLE.DATAAREAID = CONFIGTABLE.DATAAREAID'.NL;
		
		$query .= $this->build_WHERE( $options );

		$query .= NL.'ORDER BY PRICEDISCTABLE.RTDITEMNAME';

		if( isset($_GET['output']) && $_GET['output'] == 'debug' ){
			echo '<pre>'.$query.'</pre>';
			echo '<pre>';
			print_r($options);
			echo '</pre>';
		}

		$materials = $this->conn->prepare($query);

		$this->bind_option_values( $materials, $options );
		$materials->setFetchMode(PDO::FETCH_NAMED);
		$materials->execute();

		return $this->fix_padding( $materials->fetchAll() );
	}
}