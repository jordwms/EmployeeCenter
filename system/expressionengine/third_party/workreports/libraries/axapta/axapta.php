<?php
class axapta {
	protected $conn;

	function __construct() {
		$this->EE =& get_instance();

		if( !$this->conn = $this->axapta_connection() ){
			exit();
		} else {
			date_default_timezone_set('UTC');
			$this->init();
		}
	}


	/*
	 *	Load and initalize all the seperate models
	 */
	function init() {
		$models_dir = __DIR__.'/'.'models'.'/';
		$models = array_diff( scandir( $models_dir ), Array( ".", ".." ) );
		foreach ($models as $model) {
			if(file_exists($models_dir.$model)){
				require_once( $models_dir.$model);

				$class = str_replace('.php', '', $model);

				if(class_exists($class)){
					$this->$class = new $class($this->conn);
				}
			}
		}
	}

	function axapta_connection() {
		$this->EE->load->config('config');

		$host   = 'DC-S-DB-11.applusrtd.net';
		$db     = 'TEST';
		$user   = 'sa.usaAccess';
		$pass   = 'Development12';

		try {
			$ax_conn = new PDO("dblib:host=$host;Database=$db;", $user, $pass);

    		//$ax_conn->exec("SET CHARACTER SET utf8");

			return $ax_conn;
		} catch(PDOException $e) {
			//error logging/display
			echo('error connecting to axapta');
			return FALSE;
		}
	}

	/*
	 *	get db server time zone offset
	 */
	function server_tzoffset() {
		$tz = $this->conn->query('select datepart(TZOFFSET, SYSDATETIMEOFFSET() )');
		$tzoffset = $tz->fetch();
		$tz->closeCursor();

		//turn minutes into seconds
		return $tzoffset[0] * 60;
	}


	/*
	 *	QUERY HELPERS
	 */
	// Properties are defiend at the top of the class as: $property_name = 'AXAPTAFIELD';
	function get_properties() {
		$properties = array_diff_key( get_class_vars( get_class($this) ), get_class_vars(__CLASS__) );
		return $properties;
	}

	// builds: SELECT AXAPTAFIELD AS property_name using the properties defined at top of model class
	function build_SELECT(){
		if( is_array($this->properties) && count($this->properties) > 0 ){
			$query_select_part =  'SELECT '.NL;

			$last_key = end( array_keys($this->properties) );
			foreach ( $this->properties as $key => $value) {
				$query_select_part .= TAB.$value.' AS '.$key;
				if(  $key != $last_key ) {
					$query_select_part .= ',';
				}
				$query_select_part .= NL;
			}
			return $query_select_part;
		} else {
			exit('no properties defined');
		}
	}

	// builds: WHERE AXAPTAFIELD = property_name
	// options use the property name, are mapped to appropriate AXAPTAFIELD

	// $options = array('id' => array('=', 'someValue') );


	function build_WHERE($options) {
		if( is_array($options) && count($options) > 0 ){
			$last_key = end( array_keys($options) );

			$query_where_part = 'WHERE ';

			foreach( $options as $key => $value ){
				if( is_array($value) ){
					//option has been declared with an operator for the value
					//first array key should be the operator
					if ($value[0] == 'like'){
						//LIKE operator nees some special lovin
					 	$operator = ' LIKE ';
						$query_where_part .= $this->properties[$key].$operator.':'.$key;
					} elseif($value[0] == 'in') {
						// IN operator needs even more special lovin... also... kinda a security hazard right here :(
						// @TODO escape the $value
						$query_where_part .= $this->properties[$key].' IN ('.$value.')';
					} else {
						//probably a 'normal' operator like =, >, <, <>, <=, >=
					 	$operator = ' '.$value[0].' ';
						$query_where_part .= $this->properties[$key].$operator.':'.$key;
					}
				} else {
					$query_where_part .= $this->properties[$key].' = '.':'.$key;
				}

				if( count($options) > 1 && $key != $last_key){
					$query_where_part .= NL.'AND ';
				}
			}
			return $query_where_part;
		} else {
			return;
		}
	}

	// binds option values to their respective keys
	function bind_option_values(&$prepared_statment, $options) {
		if( is_array($options) && count($options) > 0 ){
			foreach ($options as $key => $value) {
				if( is_array($value) ){
					//option has been declared with an operator for the value
					//first array key should be the operator
					//second array key should be the value
					if( $value[0] == 'like' ){
						$prepared_statment->bindValue($key, '%'.$value[1].'%', PDO::PARAM_STR);
					} elseif( $value[0] == 'in' ){
						//do nothing?
					} else {
						$prepared_statment->bindValue($key, $value[1]);
					}
				} else {
					$prepared_statment->bindValue($key, $value);
				}
			}
		}
		return $prepared_statment;
	}

	// END QUERY HELPERS

	// explodes datetime options into date and time parts for the queries to properly use
	function explode_datetime(&$options){
		foreach ($options as $key => &$value) {
			if( $key_part = strstr($key, '_datetime', TRUE) ){
				if( isset($options[$key]) ){
					if( is_array($options[$key]) ){
						$options[$key_part.'_date'] = array($options[$key][0] , date('Y-m-d', $options[$key][1]) );
						$options[$key_part.'_time'] = array($options[$key][0] , $options[$key][1] - strtotime($options[$key_part.'_date'][1]) );
					} else {
						$options[$key_part.'_date'] = date('Y-m-d', $options[$key]);
						$options[$key_part.'_time'] = $options[$key] - strtotime($options[$key_part.'_date']);
					}
					unset($options[$key]);
					return $options;
				}
			}
		}
	}

	//fix axapta's penchant for padding strings
	//also fixing character set
	function fix_padding(&$data) {
		if( is_array($data) ){
			foreach ($data as $key => &$val) {
				if( is_array($data) ){
					$this->fix_padding($val);
				} elseif( is_string($val) ) {
					$val = ltrim(rtrim($val));
				}
			}
		}
		if( is_string($data) ){
			//$data =  ltrim(rtrim($data));
			$data = iconv('ISO8859-1', 'UTF-8', ltrim(rtrim($data)));
		}
		return $data;
	}

	function authorized_companies(){
		//These are the companies the employee has "WA TECH", we will only list customers of these companies.
		$authorized_companies = '';
		$last_key = end( array_keys($employee['groups']) );
		foreach ($employee['groups'] as $company => $group) {
			if ( in_array('WA TECH', $employee['groups'][$company]) ) {
				$authorized_companies .= "'$company'";
				if( count($employee['groups']) > 1 && $company != $last_key) {
					$authorized_companies .= ', ';
				}
			}
		}
	}
}// END CLASS

/* End of file axapta.php */
/* Location: ./system/expressionengine/third_party/modules/workreports/libraries/axapta.php */