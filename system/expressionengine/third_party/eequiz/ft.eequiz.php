<?php 

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Eequiz_ft extends EE_Fieldtype {
    
    var $info = array(
        'name'        => '4-eeQuiz',
        'version'    => '1.0'
    );

    // --------------------------------------------------------------------
    
    /**
     * Display field in publish page
     *
     * @access    public
     * @param    existing data
     * @return    field html
     *
     */
    function display_field($data)
    {
		$options = array("" => "");
		
		$query = $this->EE->db->query("SELECT * FROM exp_eequiz_quizzes ORDER BY title ASC");
		foreach ($query->result_array() as $row) {
			$options[$row["quiz_id"]] = $row["title"];
		}
		
		return form_dropdown('field_id_'.$this->field_id, $options, $data);
    }

    // --------------------------------------------------------------------
        
    /**
     * Replace tag in templates
     *
     * @access    public
     * @param    field data
     * @param    field parameters
     * @param    data between tag pairs
     * @return    replacement text
     *
     */
    function replace_tag($data, $params = array(), $tagdata = FALSE)
    {
        return $data;	
    }
    
    // --------------------------------------------------------------------
    
    /**
     * Prep data for saving
     *
     * @access    public
     * @param    submitted field data
     * @return    string to save
     */
    function save($data)
    {
        return $this->EE->input->post('field_id_'.$this->field_id);
    }
}
