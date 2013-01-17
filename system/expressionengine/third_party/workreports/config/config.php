<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
if (NSM_ENV == 'local' || NSM_ENV == 'production') {
	$config['ax_host'] = 'DC-S-DB-01.applusrtd.net';
	$config['ax_db']   = 'P-AX30SP3-RTD';
	$config['ax_user'] = 'sa.usaAccess';
	$config['ax_pass'] = 'Development12';
}
else { // 'development'
	$config['ax_host'] = 'DC-S-DB-11.applusrtd.net';
	$config['ax_db']   = 'TEST';
	$config['ax_user'] = 'sa.usaAccess';
	$config['ax_pass'] = 'Development12';
}
