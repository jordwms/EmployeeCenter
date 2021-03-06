<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Authenticate
 *
 * @package     Authenticate
 * @author      Justin Kimbrell
 * @copyright   Copyright (c) 2012, Justin Kimbrell
 * @link        http://www.objectivehtml.com/authenticate
 * @version     1.2.1
 * @build       20120627
 */
class Workreports_ext {
    public $name            = 'workreports';
    public $version         = 1.0;
    public $description     = '';
    public $settings_exist  = 'n';
    public $docs_url        = '';
    public $settings        = array();
    public $required_by     = array('module');
    public function __construct() {
        $this->EE =& get_instance();
        $this->settings = array();
    }
    
    /**
     * Member Logout
     *
     * Remove the ugly redirect screen on logout
     *
     * @access  public
     * @return  string
     */
    public function member_member_logout() {
    }

    function member_member_login_start() {
        //$this->EE->session->userdata['test_string'] = 'test string with elephants';
        //echo $this->EE->session->userdata('test_string');
        //die;
    }

    /**
     * Activate Extension
     *
     * This function enters the extension into the exp_extensions table
     *
     * @return void
     */
    function activate_extension() {
        return TRUE;
    }

    /**
     * Update Extension
     *
     * This function performs any necessary db updates when the extension
     * page is visited
     *
     * @return  mixed   void on update / false if none
     */
    function update_extension($current = '') {
        if ($current == '' OR $current == $this->version)
        {
            return FALSE;
        }
        if ($current < '1.0')
        {
            // Update to version 1.0
        }
        $this->EE->db->where('class', __CLASS__);
        $this->EE->db->update('extensions', array('version' => $this->version));
    }

    /**
     * Disable Extension
     *
     * This method removes information from the exp_extensions table
     *
     * @return void
     */
    function disable_extension() {
        $this->EE->db->where('class', __CLASS__);
        $this->EE->db->delete('extensions');
    }
}