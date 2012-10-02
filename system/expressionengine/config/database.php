<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

$active_record = TRUE;
$active_group = 'expressionengine';

$db['expressionengine']['hostname'] = "localhost";
$db['expressionengine']['username'] = "employee_center";
$db['expressionengine']['password'] = "gi0kQ#vdmw7b";
$db['expressionengine']['database'] = "employee_center";
$db['expressionengine']['dbdriver'] = "mysqli";
$db['expressionengine']['pconnect'] = FALSE;
$db['expressionengine']['dbprefix'] = "exp_";
$db['expressionengine']['swap_pre'] = "exp_";
$db['expressionengine']['db_debug'] = TRUE;
$db['expressionengine']['cache_on'] = FALSE;
$db['expressionengine']['autoinit'] = FALSE;
$db['expressionengine']['char_set'] = "utf8";
$db['expressionengine']['dbcollat'] = "utf8_general_ci";
$db['expressionengine']['cachedir'] = "./system/expressionengine/cache/db_cache/";

require(realpath(dirname(__FILE__) . '/../../../config_bootstrap.php'));

/* End of file database.php */
/* Location: ./system/expressionengine/config/database.php */
