<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

$active_record = TRUE;
$active_group = 'development';

$db['development']['hostname'] = "localhost";
$db['development']['username'] = "employee_center";
$db['development']['password'] = "gi0kQ#vdmw7b";
$db['development']['database'] = "employee_center";
$db['development']['dbdriver'] = "mysqli";
$db['development']['pconnect'] = FALSE;
$db['development']['dbprefix'] = "exp_";
$db['development']['swap_pre'] = "exp_";
$db['development']['db_debug'] = TRUE;
$db['development']['cache_on'] = FALSE;
$db['development']['autoinit'] = FALSE;
$db['development']['char_set'] = "utf8";
$db['development']['dbcollat'] = "utf8_general_ci";
$db['development']['cachedir'] = "./system/expressionengine/cache/db_cache/";

require(realpath(dirname(__FILE__) . '/../../config_bootstrap.php'));

/* End of file database.php */
/* Location: ./system/expressionengine/config/database.php */
