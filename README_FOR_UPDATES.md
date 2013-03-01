--- README ----
Updating ExpressionEngine: 

There are several key files/folders which need to be copied from one update of ExpressionEngine to another for ApplusRTD. 

The following files need to be modified using the subsequent lines:

1. EmployeeCenter/system/expressionengine/libraries/Core.php
	In function _initialize_core(), below "// application constants":
define('TAB',			"\t");


2. system/expressionengine/config/config.php
	To the bottom of file... :
require(realpath(dirname(__FILE__) . '/../../config_bootstrap.php')); 

3. system/expressionengine/config/database.php
	To the bottom of file... :
require(realpath(dirname(__FILE__) . '/../../config_bootstrap.php'));

4. system/codeigniter/system/core/Input.php
	In function _clean_input_keys() comment out the following code:
		if ( ! preg_match("/^[a-z0-9:_\/-]+$/i", $str))
		{
			set_status_header(503);
			exit('Disallowed Key Characters.');
		}


The following files/folders need to be preserved during an update:

.htaccess
.gitignore
config_bootstrap.php
README_FOR_UPDATES.md

images/Applus_RTD.png

system/expressionengine/system/third_party/authenticate
system/expressionengine/system/third_party/eequiz
system/expressionengine/system/third_party/equipment_manager
system/expressionengine/system/third_party/nce_ldap
system/expressionengine/system/third_party/responsive_cp
system/expressionengine/system/third_party/workreports

templates/

themes/third_party/eequiz
themes/third_party/equipment_manager
themes/third_party/responsive_cp
