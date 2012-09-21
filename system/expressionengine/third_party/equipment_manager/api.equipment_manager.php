<? if (!defined('BASEPATH')) exit('No direct script access allowed');

/*************************************************************************************
 *	API/Actions
 *	Essentially the "model", but some functions will be accessible via GET/POST
 *************************************************************************************/

class Equipment_manager_api {
	function __construct() {
		$this->EE =& get_instance();
		
		//these initialize the "sub classes"
		$this->facility = new Facility;
		$this->eq       = new Equipment;
		$this->log      = new Log;
		$this->audit    = new Audit;
		$this->type    	= new Equipment_types;
	}

	/*
	 * Retrieves a list of all members.
	 *	There might be something in the EE members class that does this...
	 */
	//function get_members() {
	function member_list($member_id=NULL) {
		$this->EE->db->select('
			member_id,
			group_id,
			screen_name');
		$this->EE->db->from('members');

		if(is_numeric($member_id)) {
			$this->EE->db->WHERE('member_id', $member_id);
		}

		$query = $this->EE->db->get();

		return $query->result_array();
	}
}

/*************************************************************************************
 *	Facilities
 *************************************************************************************/
class Facility extends Equipment_manager_api {
	function __construct() {
		$this->EE =& get_instance();
	}


	function details($facility_id=NULL) {
		if( !is_null($facility_id) ) {
			$this->EE->db->where(array('facility_id' => $facility_id));
		}
		return $this->EE->db->get('em_facilities')->result_array();
	}


	function add($facility_name=NULL, $location=NULL) {
		if( is_null($facility_name) && is_null($location) ) {
			$data = array (
				'facility_name' => $this->EE->input->POST('facility_name', TRUE),
				'location' => $this->EE->input->POST('location', TRUE)
			);
		} else {
			$data = array (
				'facility_name' => $facility_name,
				'location' => $location
			);
		}

		$this->EE->db->insert('em_facilities', $data);
	}
}

/*************************************************************************************
 *	Equipment
 *************************************************************************************/
class Equipment extends Equipment_manager_api {
	var $log;

	function __construct() {
		$this->EE =& get_instance();

		$this->log = new Log;

	}

/* Getters **********************************************/

	/*
	 *	Retrieves the full (joined) details for a piece of equipment
	 *	Joined values include users screen name, and facility names instead of id's
	 *  Half-life is calculated using the formula: N(t) = N(0)*2^(-t/half-life) where 't' is time in DAYS. 
	 */
	function details($eq_id=NULL) {
		$this->EE->db->select('
			em_equipment.id                    	AS id,
			em_equipment.serialnum          	AS serialnum,
			em_equipment.description        	AS description,
			em_equipment.parent_id          	AS parent_id,
			em_equipment.type_id            	AS type_id,
			em_equipment_types.type         	AS type,
			em_status_codes.id              	AS status_id,
			em_status_codes.status          	AS status,
			assigned_members.member_id      	AS assigned_member_id,
			assigned_members.screen_name    	AS assigned_member_screen_name,
			current_members.member_id       	AS current_member_id,
			current_members.screen_name     	AS current_member_screen_name,
			assigned_facility.id            	AS assigned_facility_id,
			assigned_facility.facility_name 	AS assigned_facility_name,
			current_facility.id             	AS current_facility_id,
			current_facility.facility_name  	AS current_facility_name,
			em_equipment_types.activity_rate	AS activity_rate,
			em_equipment_types.activity_units	AS activity_units,
			em_equipment.activity_value         AS activity_value,
			em_equipment.activity_time          AS activity_time,
			activity_value / pow(2,(DATEDIFF(NOW(),FROM_UNIXTIME(activity_time)) / activity_rate)) 
												AS current_activity'
		);
		$this->EE->db->from('em_equipment');
		$this->EE->db->join('em_equipment_types', 'em_equipment_types.id = em_equipment.type_id', 'left');
		$this->EE->db->join('members AS assigned_members', 'em_equipment.assigned_member_id = assigned_members.member_id', 'left');
		$this->EE->db->join('members AS current_members', 'em_equipment.current_member_id = current_members.member_id', 'left');
		$this->EE->db->join('em_facilities AS assigned_facility', 'assigned_facility.id = em_equipment.assigned_facility_id', 'left');
		$this->EE->db->join('em_facilities AS current_facility', 'current_facility.id = em_equipment.current_facility_id', 'left');
		$this->EE->db->join('em_status_codes', 'em_status_codes.id = em_equipment.status_id', 'left');

		if( !is_null($eq_id) ) {
			$this->EE->db->where('em_equipment.id', $eq_id);
		}

		//$this->EE->db->order_by('em_equipment.id', 'ASC');

		$query = $this->EE->db->get();

		if($query->num_rows() == 0) {
			return array('error' => 'No equipment found.');
		} elseif ( $query->num_rows() == 1 && !is_null($eq_id)) {
			return (array)$query->row();
		} else {
			return $query->result_array();
		} 
	}

	/*
	 *	returns an array with all equipment types
	 */
	function types() {
		return $this->EE->db->get('em_equipment_types')->result_array();
	}

	/*
	 *	Returns parent equipment for a given $eq_id
	 *
	 @TODO: Make recursive (return parents of parents)
	 */
	function parents($id) {
		$this->EE->db->select('id, serialnum, description');
		$this->EE->db->from('em_equipment');
		$this->EE->db->where('parent_id', $id);

		return $this->EE->db->get()->result_array();
	}

	/*
	 *	Returns children equipment for a given $eq_id
	 */
	function children($eq_id) {
		$this->EE->db->select(
			'em_equipment.id               			AS child_id, 
			em_equipment.serialnum         		   	AS child_serialnum, 
			em_equipment.description      		   	AS child_description,
			em_equipment_types.type        		  	AS child_type,
			em_equipment_types.activity_units	   	AS child_activity_units,
			`activity_value` / pow(2,(DATEDIFF(NOW(),FROM_UNIXTIME(`activity_time`)) / `activity_rate`)) AS child_current_activity'

		);
		$this->EE->db->from('em_equipment');

		$this->EE->db->join('em_equipment_types', 'em_equipment_types.id = em_equipment.type_id', 'left');

		$this->EE->db->where('em_equipment.parent_id', $eq_id);

		return $this->EE->db->get()->result_array();
	}

	/*
	 *	Retrieves a count of equipment assigned to, or checked out by member
	 */
	function num_available_to_member($active_member_id=NULL) {
		if(is_null($active_member_id)){ $active_member_id = $this->EE->session->userdata['member_id']; }
		
		$this->EE->db->from('em_equipment');
		$this->EE->db->where('parent_id', 0);
		$this->EE->db->where("(assigned_member_id = $active_member_id OR assigned_member_id = 0 OR current_member_id = $active_member_id)");

		return $this->EE->db->count_all_results();
	}

	/*
	 *	Get all equipment assigned or checked out to a member
	 */
	function available_to_member($active_member_id=NULL) {
		//if no member_id given, set to currently logged in member
		if(is_null($active_member_id)){ $active_member_id = $this->EE->session->userdata['member_id']; }

		$this->EE->db->select('
			em_equipment.id                 AS id,
			em_equipment.serialnum          AS serialnum,
			em_equipment.description        AS description,
			em_equipment.parent_id          AS parent_id,
			em_equipment_types.id           AS type_id,
			em_equipment_types.type         AS type,
			assigned_member.member_id       AS assigned_member_id,
			assigned_member.screen_name     AS assigned_member_screen_name,
			current_member.member_id        AS current_member_id,
			current_member.screen_name      AS current_member_screen_name,
			assigned_facility.id            AS assigned_facility_id,
			assigned_facility.facility_name AS assigned_facility_name,
			current_facility.id             AS current_facility_id,
			current_facility.facility_name  AS current_facility_name,
			em_status_codes.id              AS status_id,
			em_status_codes.status          AS status'
		);

		$this->EE->db->from('em_equipment');

		$this->EE->db->join('em_equipment_types', 'em_equipment_types.id = em_equipment.type_id', 'left');
		$this->EE->db->join('members AS assigned_member', 'em_equipment.assigned_member_id = assigned_member.member_id', 'left');
		$this->EE->db->join('members AS current_member', 'em_equipment.current_member_id = current_member.member_id', 'left');
		$this->EE->db->join('em_facilities AS assigned_facility', 'assigned_facility.id = em_equipment.assigned_facility_id', 'left');
		$this->EE->db->join('em_facilities AS current_facility', 'current_facility.id = em_equipment.current_facility_id', 'left');
		$this->EE->db->join('em_status_codes', 'em_status_codes.id = em_equipment.status_id', 'left');

		$this->EE->db->where('parent_id', 0);
		$this->EE->db->where("(assigned_member_id = $active_member_id OR assigned_member_id = 0 OR current_member_id = $active_member_id)");

		$query = $this->EE->db->get();

		return $query->result_array();
	}
	
/* Setters **********************************************/

	/*
	 *	Add a new piece of equipment
	 */
	function add($data=NULL) {
		if ( is_null($data) ) {
			$data = array (
				'serialnum'            => $this->EE->input->POST('serialnum', TRUE),
				'status_id'            => $this->EE->input->POST('status_id', TRUE),
				'assigned_member_id'   => $this->EE->input->POST('assigned_member_id', TRUE),
				'assigned_facility_id' => $this->EE->input->POST('assigned_facility_id', TRUE),
				'description'          => $this->EE->input->POST('description', TRUE),
				'type_id'              => $this->EE->input->POST('type_id', TRUE),
				'parent_id'            => $this->EE->input->POST('parent_id', TRUE),
				'activity_value'       => $this->EE->input->POST('activity_value', TRUE),
				'activity_time'        => $this->EE->input->POST('activity_time', TRUE)
			);
		}

		$this->EE->db->insert('em_equipment', $data);

		return true;
	}
	
	/*
	 *	Changes the storage facility to $facility_id for a given $eq_id
	 */
	function set_storage_facility($eq_id=NULL, $facility_id=NULL) {
		if (is_null($eq_id) && is_null($facility_id)) {
			$eq_id = $this->EE->input->post('eq_id');
			$facility_id = $this->EE->input->post('facility_id');
		}

		if (is_numeric($eq_id) && is_numeric($facility_id)) {
			$data = array(
				'assigned_facility_id' => $facility_id
			);

			$this->EE->db->where('id', $eq_id);
			$this->EE->db->update('em_equipment', $data);		

			/* Log Action *******************************************/
			$this->log->add(5, $eq_id);
		} else {
			show_error('invalid_input');
		}
	}

	/*
	 *	Changes the status to $status for a given $eq_id
	 */
	function set_status($eq_id=NULL, $status=NULL) {
		if (is_null($eq_id) && is_null($status)) {
			$eq_id = $this->EE->input->post('eq_id');
			$status = $this->EE->input->post('status');
		}

		if (is_numeric($eq_id) && is_numeric($status)) {
			$data = array(
				'status_id' => $status
			);

			$this->EE->db->where('id', $eq_id);
			$this->EE->db->update('em_equipment', $data);

			/* Log Action *******************************************/
			$this->log->add(3, $eq_id);
		} else {
			show_error('invalid_input');
		}
	}

	/*
	 *	changes the description to $description for a given $eq_id
	 */
	function set_description($eq_id=NULL, $description=NULL) {
		if (is_null($eq_id) && is_null($description)) {
			$eq_id = (int)$this->EE->input->post('eq_id');
			$description = $this->EE->input->post('description');
		}

		if (is_numeric($eq_id)) {
			$data = array(
				'description' => $description
			);

			$this->EE->db->where('id', $eq_id);
			$this->EE->db->update('em_equipment', $data);

			/* Log Action *******************************************/
			$this->log->add(3, $eq_id);
		} else {
			show_error('invalid_input');
		}
	}

	/*
	 *	changes the description to $description for a given $eq_id
	 */
	function set_serialnum($eq_id=NULL, $serialnum=NULL) {
		if (is_null($eq_id) && is_null($serialnum)) {
			$eq_id = (int)$this->EE->input->post('eq_id');
			$serialnum = $this->EE->input->post('serialnum');
		}

		if (is_numeric($eq_id)) {
			$data = array(
				'serialnum' => $serialnum
			);

			$this->EE->db->where('id', $eq_id);
			$this->EE->db->update('em_equipment', $data);

			/* Log Action *******************************************/
			$this->log->add(13, $eq_id);
		} else {
			show_error('invalid_input');
		}
	}

	/*
	 *	changes the description to $description for a given $eq_id
	 */
	function set_type($eq_id=NULL, $type_id=NULL) {
		if (is_null($eq_id) && is_null($type_id)) {
			$eq_id = $this->EE->input->post('eq_id', true);
			$type_id = $this->EE->input->post('type_id', true);
		}

		if (is_numeric($eq_id) && is_numeric($type_id)) {
			$data = array(
				'type_id' => $type_id
			);

			$this->EE->db->where('id', $eq_id);
			$this->EE->db->update('em_equipment', $data);

			/* Log Action *******************************************/
			$this->log->add(11, $eq_id);
		} else {
			show_error('invalid_input');
		}
	}

	/*
	 *	changes the description to $description for a given $eq_id
	 */
	function set_parent($eq_id=NULL, $parent_id=NULL) {
		if (is_null($eq_id) && is_null($parent_id)) {
			$eq_id = $this->EE->input->post('eq_id');
			$parent_id = $this->EE->input->post('parent_id');
		}

		if (is_numeric($eq_id) && is_numeric($parent_id)) {
			$data = array(
				'parent_id' => $parent_id
			);

			$this->EE->db->where('id', $eq_id);
			$this->EE->db->update('em_equipment', $data);

			/* Log Action *******************************************/
			$this->log->add(10, $eq_id);
		} else {
			show_error('invalid_input');
		}
	}

	/*
	 *	changes the description to $description for a given $eq_id 
	 */
	function set_current_member($eq_id=NULL, $member_id=NULL) {
		if (is_null($eq_id) && is_null($member_id)) {
			$eq_id = $this->EE->input->post('eq_id', TRUE);
			$member_id = $this->EE->input->POST('current_member_id', TRUE);
		}
		echo "member: ".is_numeric($member_id);

		echo "eq_id: ".is_numeric($eq_id);
		if (is_numeric($eq_id) && is_numeric($member_id)) {
			$data = array(
				'current_member_id' => $member_id
			);

			$this->EE->db->where('id', $eq_id);
			$this->EE->db->update('em_equipment', $data);

			/* Log Action *******************************************/
			$this->log->add(9, $eq_id);
		} else {
			show_error('invalid_input');
		}
	}

	/*
	 *	changes the description to $description for a given $eq_id
	 */
	function set_assigned_member($eq_id=NULL, $member_id=NULL) {
		if (is_null($eq_id) && is_null($member_id)) {
			$eq_id = $this->EE->input->post('eq_id');
			$member_id = $this->EE->input->post('assigned_member');
			if($member_id == "NULL") { $member_id = NULL; }
		}

		if (is_numeric($eq_id) && (is_numeric($member_id) || is_null($member_id)) ) {
			$data = array(
				'assigned_member_id' => $member_id
			);

			$this->EE->db->where('id', $eq_id);
			$this->EE->db->update('em_equipment', $data);

			/* Log Action *******************************************/
			$this->log->add(8, $eq_id, 'set assigned member');
		} else {
			show_error('invalid_input');
		}
	}

	/*
	* Changes the activity_value to $activity_value for a given $eq_id
	*/
	function set_activity_value($eq_id=NULL, $activity_value=NULL) {
		if (is_null($eq_id) && is_null($activity_value)) {
			$eq_id = $this->EE->input->post('eq_id');
			$activity_value = $this->EE->input->post('activity_value');
			if($activity_value == "NULL") { $activity_value = NULL; }
		}

		if (is_numeric($eq_id) && (is_numeric($activity_value) || is_null($activity_value)) ) {
			$data = array(
				'activity_value' => $activity_value
			);

			$this->EE->db->where('id', $eq_id);
			$this->EE->db->update('em_equipment', $data);

			/* Log Action *******************************************/
			$this->log->add(20, $eq_id, 'set activity value');
		} else {
			show_error('invalid_input');
		}
	}

	/*
	* Changes the activity_time to $activity_time for a given $eq_id
	*/
	function set_activity_time($eq_id=NULL, $activity_time=NULL) {
		if (is_null($eq_id) && is_null($activity_time)) {
			$eq_id = $this->EE->input->post('eq_id');
			$activity_time = $this->EE->input->post('activity_time');
			if($activity_time == "NULL") { $activity_time = NULL; }

			$activity_time = (!is_numeric($activity_time) ? strtotime($activity_time) : $activity_time);
		}
		
		if (is_numeric($eq_id)) {
			$data = array(
				'activity_time' => $activity_time
			);

			$this->EE->db->where('id', $eq_id);
			$this->EE->db->update('em_equipment', $data);

			/* Log Action *******************************************/
			$this->log->add(19, $eq_id, 'set activity time');
		} else {
			show_error('invalid_input');
		}
	}

/* Actions **********************************************/

	/*
	 *	Check Out action
	 *	check out is stored as the member_id in em_equipment.current_memeber_id field
	 *	NULL current_member_id = equipment is checked into a facility
	 */
	function check_out($eq_id=NULL, $notes=NULL, $password=NULL) {
		if(is_null($eq_id)) { $eq_id = $this->EE->input->get_post('eq_id', TRUE); }
		if(is_null($notes)) { $notes = $this->EE->input->post('notes', TRUE); }
		if(is_null($password)) { $password = $this->EE->input->post('password', TRUE); }

		if( is_numeric($eq_id) ) {
			$eq = $this->details($eq_id);

			if($password) {
				//secure checkout
				$active_member_id = $eq['assigned_member_id'];

				$this->EE->load->library('auth');
				$this->EE->lang->loadfile('login');

				$authorized = $this->EE->auth->authenticate_id($active_member_id, $password);

				if (! $authorized) {
					show_error('wrong password');
					exit;
				}

			} else {
				$active_member_id = $this->EE->session->userdata['member_id'];
			}

			if($eq['assigned_member_id']==$active_member_id) {
				//Equipment is assigned to currently signed in member
				if(is_null($eq['current_member_id'])) {
					//equipment is not checked out to anybody
					if( $eq['status']=='OK_to_use' ) {
						//equipment is OK to use

						//we can proceed with the check out
						$data = array(
							'current_member_id' => $active_member_id
						);

						$this->EE->db->where('id', $eq_id);
						$this->EE->db->update('em_equipment', $data);

						/* Log Action */
						$this->log->add(2, $eq_id);

						$this->EE->output->show_message(array(
							'title'   => 'Check Out',
							'heading' => 'Message:',
							'content' => 'Successfully checked out '.$eq['serialnum'],
							'link'    => array($this->EE->functions->form_backtrack('-1'), 'Return')
						));

						return TRUE;
					} else {
						//equipment is NOT OK to use, return status code.
						show_error('equipment is NOT OK to use');
					}
				} else {
					//equipment is checked out to somebody
					if($eq['current_member_id']==$active_member_id) {
						//equipment is already checked out by current member
						show_error('you already have '.$eq['serialnum'].' checked out');
					} else {
						//equipment is checked out to somebody else
						show_error($eq['serialnum'].' is checked out to somebody else');
					}
				}
			} else {
				//need RSO permission?
				$error = 'You have not been assigned this piece of equipment.  Please get the RSO or dispatcher to assign it to you';
				if ($eq['assigned_member_id']) { $error .= ' or have '.$eq['assigned_member_screen_name'].' transfer it to you'; }
				$error .= '.';

				show_error($error);
			}
		} else {
			show_error('invalid_input');
		}
	}


	/*
	 *	Check In action
	 */
	function check_in($eq_id=NULL, $facility_id=NULL, $notes=NULL, $status_id=NULL) {
		if(is_null($eq_id)) { $eq_id = $this->EE->input->get_post('eq_id', TRUE); }
		if(is_null($facility_id)) { $facility_id = $this->EE->input->get_post('facility', TRUE); }
		if(is_null($notes)) { $notes=$this->EE->input->post('notes', TRUE); }
		if(is_null($status_id)) { $status_id=$this->EE->input->post('status_id', TRUE); }

		// if status is not set, it is OK to use
		if (!$status_id) { $status_id=1; }
		if( is_numeric($eq_id) && is_numeric($facility_id) ) {
			$active_member_id = $this->EE->session->userdata['member_id'];
			$eq = $this->details($eq_id);

			if($eq['current_member_id'] == $active_member_id) {
				$data = array(
					'current_member_id' 	=> NULL,
					'current_facility_id' 	=> $facility_id,
					'status_id' 			=> $status_id
				);

				$this->EE->db->where('id', $eq_id);
				$this->EE->db->update('em_equipment', $data);
			
				/* Log Action */
				$this->log->add(1, $eq_id, $notes);

				$this->EE->output->show_message(array(
					'title'   => 'Check In',
					'heading' => 'Message:',
					'content' => $eq['serialnum'].' checked in successfully.',
					'link'    => array($this->EE->functions->form_backtrack('-1'), 'Return')
				));

				return TRUE;
			} else {
				show_error('not_current_user');
			}
		} else {
			show_error('invalid_input');
		}
	}

	/*
	 *	Transfer to other member action
	 */
	function transfer($eq_id=NULL, $new_member_id=NULL, $notes=NULL) {
		if(is_null($eq_id)) { $eq_id = $this->EE->input->get_post('eq_id', TRUE); }
		if(is_null($new_member_id)) { $new_member_id = $this->EE->input->get_post('new_member_id', TRUE); }
		if(is_null($notes)) { $notes = $this->EE->input->post('notes', TRUE); }
		

		
		if( is_numeric($eq_id) && is_numeric($new_member_id) ) {
			$new_member= $this->member_list($new_member_id);
			$eq = $this->details($eq_id);
			$active_member_id = $this->EE->session->userdata['member_id'];

			if($eq['current_member_id']==$active_member_id) {
				// Currently signed in user has the equipment checked out and can transfer it.
				if( !is_null($new_member_id) ) {
					if ($eq['current_member_id'] != $new_member_id) {						
						$notes = json_encode( array('previous_member_id' => $active_member_id, 'user_notes' => $notes) );
						
						$data = array(
							'current_member_id' => $new_member_id
						);

						$this->EE->db->where('id', $eq_id);
						$this->EE->db->update('em_equipment', $data);

						/* Log Action */
						$this->log->add(9, $eq_id, $notes);

						$this->EE->output->show_message(array(
							'title'   => 'Transfer',
							'heading' => 'Message:',
							'content' => 'Successfully transfered '.$eq['serialnum'].' to '.$new_member[0]['screen_name'],
							'link'    => array($this->EE->functions->form_backtrack('-1'), 'Return')
						));
					} else {
						show_error('can not transfer to yourself');
					}
				} else {
					show_error('new_member_id not provided');
				}
			} else {
				show_error('you do not have equipment #'.$eq_id.' checked out');
			}
		} else {
			show_error('invalid_input');
		}
	}
}

/*************************************************************************************
 *	Equipment Types
 *************************************************************************************/
class Equipment_types extends Equipment_manager_api {
	function __construct() {
		$this->EE =& get_instance();
	}


	function details() {
		return $this->EE->db->get('em_equipment_types')->result_array();
	}

	function add($data=NULL) {
		if ( is_null($data) ) {
			$data = array (
				'type' => $this->EE->input->POST('type', TRUE),
				'model' => $this->EE->input->POST('model', TRUE),
				'manufacturer' => $this->EE->input->POST('manufacturer', TRUE),
				'description' => $this->EE->input->POST('description', TRUE),
				//'calibration_required_interval' => $this->EE->input->POST('calibration_required_interval', TRUE),
				//'calibration_form_default' => $this->EE->input->POST('calibration_form_default', TRUE),
				//'audit_required_interval' => $this->EE->input->POST('audit_required_interval', TRUE),
				//'audit_form_default' => $this->EE->input->POST('audit_form_default', TRUE),
				//'maintenance_required_interval' => $this->EE->input->POST('maintenance_required_interval', TRUE),
				//'maintenance_form_default' => $this->EE->input->POST('maintenance_form_default', TRUE),
				'activity_rate' => $this->EE->input->POST('activity_rate', TRUE),
				'activity_units' => $this->EE->input->POST('activity_units', TRUE)
			);
		}
			
		$this->EE->db->insert('em_equipment_types', $data);

		return json_encode( array('id' => $this->EE->db->insert_id()) );
	}
}

/*************************************************************************************
 *	Action log related functions
 *************************************************************************************/
class Log extends Equipment_manager_api {
	function __construct() {
		$this->EE =& get_instance();
	}

	/*
	 *	Retrieve action log entries
	 *	The action log stores an entry each time a member makes a change to a piece of equipment
	 *	Can be filtered to a specific eq_id and take custom pagination values
	 */
	function entries($eq_id=NULL, $offset=NULL, $limit=NULL) {
		//set defaults
		if(is_null($offset)){$offset=0;}
		if(is_null($limit)){$limit=30;}

		//main query
		$this->EE->db->select(
			'em_log.id               AS id,
			members.member_id        AS member_id,
			members.screen_name      AS member_screen_name,
			em_log.equipment_id      AS eq_id,
			em_equipment.serialnum   AS eq_serialnum,
			em_log_actions.id        AS action_id,
			em_log_actions.action    AS action,
			em_log.action_timestamp  AS action_timestamp'
		);
		$this->EE->db->from('em_log');
		$this->EE->db->join('em_log_actions', 'em_log_actions.id = em_log.action_id');
		$this->EE->db->join('em_equipment', 'em_equipment.id = em_log.equipment_id');
		$this->EE->db->join('members', 'members.member_id = em_log.member_id');

		//filter to $eq_id if specified
		if ($eq_id) { $this->EE->db->where('equipment_id', $eq_id); }

		//limit results unless $offset = -1
		if ($offset != -1){ $this->EE->db->limit($limit, $offset); }

		//order by reverse chronological
		$this->EE->db->order_by('action_timestamp', 'desc');

		$query = $this->EE->db->get();
		return $query->result_array();
	}

	/*
	 *	Creates a new action log entry
	 */
	function add($action_id, $eq_id, $note=NULL) {
		if(is_null($note)) {
			if(isset($_POST['notes'])) {
				$note = $this->EE->input->POST('note', TRUE);
			} else {
				$note = 'No note';
			}
		}

		$data = array (
			'equipment_id' => $eq_id,
			'action_id' => $action_id,
			'notes' => $note,
			'member_id' => $this->EE->session->userdata('member_id'),
			'action_timestamp' => time()
		);
			
		$this->EE->db->insert('em_log', $data);
	}
}

/*************************************************************************************
 *	Audit related functions
 *************************************************************************************/
class Audit extends Equipment_manager_api {
	function __construct() {
		$this->EE =& get_instance();
	}

	/*
	 *	Retrieve audit log entries
	 *	The audit log stores an entry every time an audit is performed
	 *	Can be filtered to a specific eq_id and take custom pagination values
	 */
	function entries($eq_id=NULL, $offset=NULL, $limit=NULL) {
		//set defaults
		if(is_null($offset)){$offset=0;}
		if(is_null($limit)){$limit=30;}

		//filter to $eq_id if specified
		if ($eq_id) { $this->db->where('equipment_id', $eq_id); }

		//limit results unless $offset = -1
		if ($offset != -1){ $this->EE->db->limit($limit, $offset); }

		//order by reverse chronological
		$this->EE->db->order_by('audit_datetime', 'desc');

		$query = $this->EE->db->get('audit_results');
		return $query->result_array();
	}

	function template($id=NULL) {
		if (is_null($id)) {
			//$this->db->
		}
	}

	/*
	 *	creates a new audit log entry
	 */
	function add($eq_id, $form_passed, $form_values) {
		$data = array (
			'equipment_id' => $eq_id,
			'form_passing' => $form_passed,
			'form_values' => $form_values,
			'member_id' => $this->EE->session->userdata('member_id'),
			'audit_timestamp' => time()
		);

		$this->EE->db->insert('em_audit_log', $data);
	}
}