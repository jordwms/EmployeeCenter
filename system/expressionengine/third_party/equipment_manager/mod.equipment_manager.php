<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

require_once('api.equipment_manager.php');

class Equipment_manager {
	// contains the module’s outputted content and is retrieved by the Template parser
	// after the module is done processing.
	// template parser will use a methods return value, this is only for returning data to the template parser from the constructor
	var $return_data = '';

	var $api;

	function __construct() {
		$this->EE =& get_instance();

		$this->api = new Equipment_manager_api();
	}

	/*
	 *	Returns the number of pieces of equipment assigned to a member
	 */
	function member_eq_count() {
		return $this->api->eq->num_available_to_member();
	}

	/*
	 *	Returns a list of all pieces of equipment assigned to the current user
	 */
	function member_eq_list() {
		$tagdata = $this->EE->TMPL->tagdata;

		$eq_list = $this->api->eq->available_to_member();

		foreach ($eq_list as &$eq) {
			$eq['children'] = $this->api->eq->children($eq['id']);

			// round child_current_activity to 2 decimals
			foreach($eq['children'] as &$child) {
				$child['child_current_activity'] = round($child['child_current_activity'], 2);
			}
		}

		$this->return_data = $this->EE->TMPL->parse_variables( $tagdata, $eq_list );
		return $this->return_data;
	}

	/*
	 *	Returns a list of all pieces of equipment assigned to a specific facility
	 *	Used as a "assignment listing" for use in the vault/shooting room
	 */
	function facility_eq_list() {
		$facility_id = $this->EE->db->escape_str( $this->EE->TMPL->fetch_param('facility_id') );
		$tagdata = $this->EE->TMPL->tagdata;

		$eq_list = $this->EE->db->query(
			"SELECT
				em_equipment.id AS eq_id,
				eq_serialnum,
				em_equipment.description,
				type AS eq_type,
				status,
				status_code,
				assigned_member_id,
				screen_name
			FROM em_equipment 
			INNER JOIN em_facilities ON em_facilities.id=em_equipment.storage_facility_id
			INNER JOIN em_equipment_type ON em_equipment_type.id=em_equipment.eq_type_id
			LEFT JOIN em_status_codes on em_status_codes.id=em_equipment.status_code
			LEFT JOIN exp_members on exp_members.member_id=em_equipment.assigned_member_id
			WHERE storage_facility_id=$facility_id"
		);

		$this->return_data = $this->EE->TMPL->parse_variables( $tagdata, $eq_list->result_array() );
		return $this->return_data;
	}


	/*
	 *	Returns equipment details and forms for check in/out/transfer.
	 */
	function equipment_details($eq_id=NULL, $secure=NULL) {
		if(is_null($eq_id)) {
			$eq_id = $this->EE->db->escape_str( $this->EE->TMPL->fetch_param('equipment_id') );
		}

		$check_in_uri = $this->EE->functions->fetch_site_index(0, 0).QUERY_MARKER.'ACT='.$this->EE->functions->fetch_action_id('Equipment_manager', 'check_in');
		$check_out_uri = $this->EE->functions->fetch_site_index(0, 0).QUERY_MARKER.'ACT='.$this->EE->functions->fetch_action_id('Equipment_manager', 'check_out');
		$xfer_user_uri = $this->EE->functions->fetch_site_index(0, 0).QUERY_MARKER.'ACT='.$this->EE->functions->fetch_action_id('Equipment_manager', 'transfer');

		$active_member_id = $this->EE->session->userdata['member_id'];
		$tagdata = $this->EE->TMPL->tagdata;
		
		$eq_details = $this->api->eq->details($eq_id);

		if (isset($eq_details['error'])) {
			return $eq_details['error'];
		}

		$eq_details['children'] = $this->api->eq->children($eq_id);

		// round child_current_activity to 2 decimals
		foreach($eq_details['children'] as &$child) {
			$child['child_current_activity'] = round($child['child_current_activity'], 2);
		}


		if ($eq_details['current_member_id'] == $active_member_id) {
			//equipment is checked out by the currently signed in user
			
			/* Check In ---------------------------------*/
			$checkin_form_details = array(
				'action'		=> $check_in_uri,
				'name'          => 'check_in',
				'id'            => $this->EE->TMPL->form_id,
				'class'         => $this->EE->TMPL->form_class,
				'hidden_fields' => array('eq_id' => $eq_details['id']),
				'secure'        => TRUE,
				'onsubmit'      => ''
        	);

			$eq_details['actions'] = $this->EE->functions->form_declaration($checkin_form_details);

			//facility drop down
			$eq_details['actions'] .= '<select name="facility">';
			foreach ($this->api->facility->details() as $facility) {
				$eq_details['actions'] .= '<option ';
				if ($facility['id'] == $eq_details['assigned_facility_id']){
					$eq_details['actions'] .= 'selected ';
				}
				$eq_details['actions'] .= 'value="'.$facility['id'].'">'.$facility['facility_name'].'</option>';
			}
			$eq_details['actions'] .= '</select>';

			// Notes box
			$eq_details['actions'] .= '<input type="textarea" name="notes" value="notes"/>';

			// DNU checkbox
			$eq_details['actions'] .= '<label for="status_checkbox">Do Not Use</label>';
			$eq_details['actions'] .= '<input type="checkbox" name="status_id" id="status_checkbox" value="2" />';

			// If checkout is secured, show password box as well
			if($secure) {
				$eq_details['actions'] .= '<input type="textarea" name="password" value="password"/>';
			}

			// Submit and form closing
			$eq_details['actions'] .= '<input type="submit" value="Check In"/>';
			$eq_details['actions'] .= '</form>';

			/* Transfer ---------------------------------*/
			$transfer_form_details = array(
				'action'		=> $xfer_user_uri,
				'name'          => 'xfer_user',
				'id'            => $this->EE->TMPL->form_id,
				'class'         => $this->EE->TMPL->form_class,
				'hidden_fields' => array('eq_id' => $eq_details['id']),
				'secure'        => TRUE,
				'onsubmit'      => ''
        	);

			$member_list = $this->EE->db->select('member_id, screen_name')->get('members')->result_array();

			$eq_details['actions'] .= $this->EE->functions->form_declaration($transfer_form_details);
			
			$eq_details['actions'] .= '<select name="new_member_id">';
			foreach ($member_list as $value) {
				$eq_details['actions'] .= '<option value="'.$value['member_id'].'">'.$value['screen_name'].'</option>';
			}
			$eq_details['actions'] .= '</select>';
						
			$eq_details['actions'] .= '<input type="submit" value="Transfer"/>';
			$eq_details['actions'] .= '</form>';
		
		} elseif ( is_null($eq_details['current_member_id']) ) {
			//equipment is not checked out
			if( $eq_details['status']=='OK_to_use' ){ 
				//status is OK to use
				
				/* Check Out --------------------------------*/
				$checkout_form_details = array(
					'action'		=> $check_out_uri,
					'name'          => 'check_out',
					'id'            => $this->EE->TMPL->form_id,
					'class'         => $this->EE->TMPL->form_class,
					'hidden_fields' => array('eq_id' => $eq_details['id']),
					'secure'        => TRUE,
					'onsubmit'      => ''
	        	);

				$eq_details['actions'] = $this->EE->functions->form_declaration($checkout_form_details);
				
				$eq_details['actions'] .= '<input type="textarea" name="notes" value="notes"/>';
				$eq_details['actions'] .= '<input type="submit" value="Check Out"/>';
				$eq_details['actions'] .= '</form>';
			} else {
				//Equipment status is something other than OK to use
				$eq_details['actions'] = '<p> Status DO NOT USE </p>';
			}
		} elseif (!is_null($eq_details['current_member_id'])) {
			$eq_details['actions'] = '<p> Checked out to another user. </p>';
		} else {
			$eq_details['actions'] = '<p> No actions available.  </p>';
		}

		$this->return_data = $this->EE->TMPL->parse_variables_row( $tagdata, $eq_details );
		return $this->return_data;
	}


	/************************************************************************************
	 *	ACTIONS
	 *	Aliased from api
	 ************************************************************************************/
	function add_facility()         { return $this->api->facility->add(); }
	function add_equipment_type()   { return $this->api->type->add(); }
	function add_equipment()        { return $this->api->eq->add(); }

	function check_out()            { return $this->api->eq->check_out(); }
	function check_in()             { return $this->api->eq->check_in(); }
	function transfer()             { return $this->api->eq->transfer(); }

	function set_storage_facility() { return $this->api->eq->set_storage_facility(); }
	function set_status()           { return $this->api->eq->set_status(); }
	function set_description()      { return $this->api->eq->set_description(); }
	function set_serialnum()        { return $this->api->eq->set_serialnum(); }
	function set_type()             { return $this->api->eq->set_type(); }
	function set_parent()           { return $this->api->eq->set_parent(); }
	function set_current_member()   { return $this->api->eq->set_current_member(); }
	function set_assigned_member()  { return $this->api->eq->set_assigned_member(); }
}