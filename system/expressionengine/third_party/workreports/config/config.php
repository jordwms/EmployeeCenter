<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
if (NSM_ENV == 'local' || NSM_ENV == 'production') {
	$config['ax_host'] = 'ax_host_goes_here';
	$config['ax_db']   = 'AX_DB';
	$config['ax_user'] = 'username';
	$config['ax_pass'] = 'password';
}
else { // 'development'
	$config['ax_host'] = 'ax_host_goes_here';
	$config['ax_db']   = 'TEST';
	$config['ax_user'] = 'username';
	$config['ax_pass'] = 'password';
}
