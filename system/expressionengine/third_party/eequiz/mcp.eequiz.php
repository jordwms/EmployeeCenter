<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/* 
===================================================== 
File: mcp.eequiz.php 
----------------------------------------------------- 
Purpose: eeQuiz Control Panel 
===================================================== 
*/ 

require_once("common.php");

class Eequiz_mcp { 
	
	var $system_name = "Eequiz";
	var $module_name = "4-eeQuiz";
	var $module_url = "";
	var $module_uri_middle = "";
	var $question_types = array();
	
    // --------------------------------
    //  Constructor
    // --------------------------------
	
    function Eequiz_mcp() 
    { 
        $this->EE =& get_instance();
		$this->EE->load->library('javascript');
		$this->EE->load->helper('form'); 
		
		//--------------------------------
		// set up some important vars
		
		$this->module_uri_middle = 'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=eequiz';
		$this->module_url = BASE.AMP.$this->module_uri_middle;
		
		$theme_folder_url = $this->EE->config->item('theme_folder_url');
		if (substr($theme_folder_url, -1) != '/') $theme_folder_url .= '/';
		$theme_folder_url .= "third_party/eequiz/";
		
		
		//--------------------------------
		// stylesheets, and javascripts
		
		$this->EE->cp->add_js_script(array('effect' => 'core',
										   'effect' => 'blind',
										   'plugin' => 'fancybox'));
		$js_libs = array('formutility', 'jquery.ghosttext', 'mcp.eequiz', 'jquery.lightswitch'); //'fancybox/jquery.fancybox-1.3.1', 
		foreach ($js_libs as $js) $this->EE->cp->load_package_js($js);
		//foreach ($js_libs as $js) $this->EE->cp->add_to_head("<script type='text/javascript' charset='utf-8' src='{$theme_folder_url}{$js}.js'></script>");

		$this->EE->javascript->output("moduleURL = '".html_entity_decode($this->module_url)."';");
		$this->EE->javascript->output("$.fn.lightSwitch.defaults.imageDir = '{$theme_folder_url}lightswitch/';");
		$this->EE->javascript->output("$('.lightswitch').lightSwitch();");
		$this->EE->javascript->output("eequiz.shortnameSeparator = '".($this->EE->config->item('word_separator') != "dash" ? '_' : '-')."';");
		
		$css = array('mcp.css', 'fancybox/jquery.fancybox-1.3.1.css');
		foreach ($css as $item) $this->EE->cp->add_to_head("<link rel='stylesheet' type='text/css' href='{$theme_folder_url}{$item}' />");
		
		
		//--------------------------------
		// gather question types
		
		$this->question_types = array();
		if ($handle = opendir(QUESTION_TYPES_PATH))
		{
			while (false !== ($file = readdir($handle)))
			{
				if (is_file(QUESTION_TYPES_PATH.$file) && $file != "." && $file != ".." && !preg_match('/^\./', $file))
				{
					$this->question_types[] = array(
						"filename" => $file,
						"classname" => str_replace(".php", "", $file)
					);
					require_once(QUESTION_TYPES_PATH.$file);
				}
			}
			closedir($handle);
		}
    } 

	
	
	function index()
	{
		$questions = $this->EE->db->query("SELECT question_id FROM exp_eequiz_questions");
		
		if ($questions->num_rows() == 0) return $this->view_documentation();
		else return $this->overview();
	}

	/*
	* Gives a list all employees and the number of passing quizzes per quiz group
	*/
	function overview() {
		$this->EE->load->library('table');
		$this->EE->load->library('score');
		$this->_breadcrumbs(array('overview'));

		// Get all quiz groups
		$vars['quiz_groups'] = $this->EE->db->get('eequiz_quiz_groups')->result_array();

		// Assemble headers
		$vars['headers'] = array('employee' => array('header' => "Employee Name"));
		foreach($vars['quiz_groups'] as &$group) {
			$vars['headers'] 	= array_merge( $vars['headers'], array($group['name'] => array('header' => $group['name']) ));
		}
		$vars['defaults'] = array(
		    'sort' => array('employee' => 'asc')
		);
		
		return $this->EE->load->view('overview', $vars, TRUE);
	}

	function overview_datasource($state) {
		$limit = 5;
		$offset = $state['offset'];
		$this->sort = $state['sort'];

		$quiz_details_uri 	= $this->module_url.AMP.'method=score_card'.AMP.'id=';
		$member_uri 		= BASE.AMP.'C=myaccount'.AMP.'id=';
		$method_url			= $this->module_url.AMP.'method=overview';

		// Get all employees
		$employees = $this->EE->db->select('member_id, username')
										->from('members')
										->order_by('username', 'ASC') // remove when paginating
										// ->limit($limit, $offset)
										->get()
										->result_array();
		// Get all quiz groups
		$vars['quiz_groups'] = $this->EE->db->get('eequiz_quiz_groups')->result_array();
		
		foreach($vars['quiz_groups'] as &$group) {
			// Total quizzes in each group
			$group['total'] 	= $this->EE->score->number_in_group( $group['name'] );

			foreach($employees as $user) {
				// Match an employee ID with number of passing quizzes per group
				$rows[$user['member_id']]['employee'] 		= '<a href="'.$member_uri.$user['member_id'].'">'. $user['username'] .'</a>';
				$rows[$user['member_id']][$group['name']]	= '<a href="'.$quiz_details_uri.$user['member_id'].AMP.'prefix='.$group['name'].'">'
																.$this->EE->score->number_passing_in_group( $group['name'], $user['member_id']).' / '.$group['total']
																.'</a>';
			}
		}
		#pagination related
		// usort($rows, array($this, '_sort_rows'));

		return array(
			'rows'	=> $rows
			);

		# Pagination does not quite work...
		// return array(
		//     'rows' => array_slice($rows, $offset, $limit),
		//     'pagination' => array(
		//     	'page_query_string' => 	TRUE,
		//     	'base_url'			=> $method_url,
		//         'per_page'   		=> $limit,
		//         'total_rows' 		=> count($rows)
		//     )
		// );
	}

	/*
	* Sorting function meant to help with pagination when the datasource uses arrays (rathar than objects)
	*/
	function _sort_rows($a, $b)
	{
	    foreach ($this->sort as $key => $dir) {
	        if ($a[$key] !== $b[$key]) {
	            $ret = +1;

	            if ($a[$key] < $b[$key] OR $dir == 'desc') {
	                $ret = -1;
	            }
	            return $ret;
	        }
	    }
	    return 0;
	}

	/*
	* Shows an individual employee's scores on every quiz in a particular group.
	*/
	function score_card($prefix=NULL, $member_id=NULL) {
		$this->EE->load->library('table');
		$this->EE->load->library('score');
		$this->_breadcrumbs(array('score_card'));

		if( is_null($prefix) ) {
			$vars['prefix'] 	= $this->EE->input->get('prefix');
			$vars['member_id'] 	= $this->EE->input->get('id');
		}

		$vars['headers'] = array(
							'name' 	=> array('header' => "Quiz Name"),
							'score' => array('header' => "Score"),
							'date' 	=> array('header' => "Date Completed")
							);

		// $vars['quizzes'] = $this->EE->score->score_card2($prefix, $member_id);

		$vars['defaults'] = array(
		    'sort' => array('name' => 'asc')
		);

		return $this->EE->load->view('score_card', $vars, TRUE);
	}

	function score_card_datasource($state, $params) {
		$quizzes = $this->EE->score->score_card2($params['prefix'], $params['member_id']);
		$rows = array();
		foreach($quizzes as $quiz) {
			if(is_null($quiz['user_grade'])) {
				$quiz['user_grade'] = 0;
			}
			$rows[ $quiz['quiz_id'] ]['name'] 	= $quiz['quiz_title'];
			$rows[ $quiz['quiz_id'] ]['score'] 	= $quiz['user_grade'].'%';

			// Handler for incomplete quizzes
			if(is_null($quiz['last_answer_time'])) {
				$rows[ $quiz['quiz_id'] ]['date'] = 'Incomplete';
			} else {
				$rows[ $quiz['quiz_id'] ]['date'] 	= date('F d Y', $quiz['last_answer_time']); 
			}
		}

		return array(
			'rows'	=> $rows
			);
	}

	/*
	* Lists all current quiz types, and has a form for creating a new quiz type.
	* Clicking a current type navigates to a "list" pg for all quizzes of that group.
	*/
	function manage_quiz_groups() {
		$this->EE->load->library('table');
		$this->EE->load->library('score');


		$this->_breadcrumbs(array('manage_quiz_groups'));

		// Retrieve all quiz groups in the db
		$vars['quiz_groups'] = $this->EE->score->get_quiz_groups();
		
		$vars['group_url'] 					= $this->module_url.AMP.'method=quiz_group_details'.AMP.'id=';
		$vars['action_url_delete'] 			= $this->module_url.AMP.'method=delete_quiz_group';
		$vars['action_url_create'] 			= $this->module_url.AMP.'method=create_quiz_group';
		$vars['form_hidden'] 				= FALSE;
		$vars['form_attributes'] 			= FALSE;


		return $this->EE->load->view('manage_quiz_groups', $vars, TRUE);
	}

	/*
	* Insert a new quiz group to the table and return to manage_quiz_groups()
	*/
	function create_quiz_group() {
		$data = array( 'name' => $this->EE->input->post('new_quiz_group_name'));
		$this->EE->db->insert('eequiz_quiz_groups', $data);

		return $this->manage_quiz_groups();
	}

	function delete_quiz_group() {
		// Delete all rows
		if($this->EE->input->post('ALL')) {
			$this->EE->db->empty_table('eequiz_quiz_groups');
		}
		else {
			foreach($_POST as $element => $value) {
			   	// name of checkboxes = eequiz_quiz_groups.id
			    if(is_numeric($element)) {
			        $this->EE->db->where( array('id' => $this->EE->input->post($element)) )
								->delete('eequiz_quiz_groups');
			    }
			}			
		}
		return $this->manage_quiz_groups();
	}

	// Lists all the quizzes in a group and a dropdown to add a quiz not currently in the group
	function quiz_group_details($group_id=NULL) {
		$this->EE->load->library('table');
		$this->EE->load->library('score');

		if( is_null($group_id) ) {
			$group_id = $this->EE->input->get('id');
		}
		$this->_breadcrumbs(array('quiz_group_details'));

		$vars['quiz_details_uri']	= $this->module_url.AMP.'method=edit_quiz'.AMP.'quiz_id=';
		$vars['action_url_add'] 		= $this->module_url.AMP.'method=add_to_group';
		$vars['form_hidden'] 		= array(
										'quiz_group_id' => $group_id
										);
		$vars['form_attributes'] 	= FALSE;

		$vars['action_url_delete'] 			= $this->module_url.AMP.'method=remove_from_group';
		$vars['form_attributes_delete']		= array(
												'id'	=> 'current_quizzes_form'
												);
		$vars['remove_submit_button'] = '<input form="current_quizzes_form" type="button" value="delete" />';

		$vars['quizzes'] 				= $this->EE->score->get_quizzes_in_group($group_id);
		$vars['quizzes_not_in_group'] 	= $this->EE->score->get_quizzes_not_in_group($group_id);
		$vars['group_name'] 			= $this->EE->db->select('name')
														->from('eequiz_quiz_groups')
														->where(array('id' =>$group_id))
														->get()
														->row()
														->name;

		$vars['quiz_dropdown'] = '';

		foreach( $vars['quizzes_not_in_group'] as $q ) {
			$vars['quiz_dropdown'].= '<option value="'.$q['quiz_id'].'">'.$q['title'].'</option>';
		}

		return $this->EE->load->view('quiz_group_details', $vars, TRUE);
	}

	function add_to_group() {
		if( !is_null($this->EE->input->post('new_quiz')) ){
			$data = array(
				'quiz_id' 		=> $this->EE->input->post('new_quiz'),
				'quiz_group_id'	=> $this->EE->input->post('quiz_group_id')
				);

			// Protection from duplicate entries (via page resubmission, etc.)
			$is_present = $this->EE->db->get_where('eequiz_group_quizzes', $data);
			$is_present = count( $is_present->result_array() );

			if (!$is_present){
				$this->EE->db->insert('eequiz_group_quizzes', $data);
			}
		}

		return $this->quiz_group_details( $data['quiz_group_id'] );
	}

	function remove_from_group() {
		// Delete all rows related to this group
		if($this->EE->input->post('ALL')) {
			$this->EE->db->where( array('quiz_group_id' => $this->EE->input->post('quiz_group_id')) )
						->delete('eequiz_group_quizzes');
		}
		else {
			$quizzes = array();
			foreach($_POST as $element => $value) {
			   	// name of checkboxes = eequiz_group_quizzes.id
			    if(is_numeric($element)) {
			        $this->EE->db->where( array('id' => $this->EE->input->post($element)) )
								->delete('eequiz_group_quizzes');
			    }
			}			
		}
		return $this->quiz_group_details($this->EE->input->post('quiz_group_id'));
	}
	
	// --------------------------------
    //  Questions Functions
    // --------------------------------
	
	function view_questions() 
	{ 
		//-------------------------------
		// Breadcrumbs, page title
		
		$r = $this->_breadcrumbs(array('view_questions'));
		
		//-------------------------------
		// Gather data
		
		$questions = $this->EE->db->query("SELECT * FROM exp_eequiz_questions ORDER BY question_id ASC");
		$vars = array(
			'module_url'		=> $this->module_url,
			'questions'			=> $questions->result_array(),
			'question_types'	=> $this->question_types
		);
		
		foreach ($vars['questions'] as $k => $q)
		{
			$contained = $this->EE->db->query("SELECT * FROM (SELECT DISTINCT quiz_id FROM exp_eequiz_mappings WHERE question_id={$q['question_id']}) as ids 
											INNER JOIN exp_eequiz_quizzes AS q ON ids.quiz_id=q.quiz_id ORDER BY q.title");
			$vars['questions'][$k]['contained'] = $contained->result_array();
		}
		
		//-------------------------------
		// Render page
		
		$r .= $this->_cp_header();
		
		$r .= $this->EE->load->view('view_questions', $vars, TRUE);
		
		//$this->EE->javascript->output("$('a#create_question_button').fancybox({'modal' : true});");
		
		$delete_prompt = $this->EE->lang->line("delete_question_prompt");
		$this->EE->javascript->output("viewQuestions.delete_message = '{$delete_prompt}';");
		
		$r .= $this->_cp_footer();
		
		return $r; 
	} 
	
	
	
	function edit_question()
	{
		//-------------------------------
		// Breadcrumbs, page title
		
		$r = $this->_breadcrumbs(array('view_questions', 'edit_question'));
		
		//-------------------------------
		// Gather data
		
		$question_id = $this->EE->input->get_post('question_id');
		$new = ($question_id === FALSE);
		
		$classname = $this->EE->input->get_post('question_type');
		if (!$new) {
			$classname = $this->EE->db->query("SELECT classname FROM exp_eequiz_questions WHERE question_id=".$question_id);
			if ($classname->num_rows() == 0) return "Error: invalid question_id.";
			$row = $classname->row_array();
			$classname = $row['classname'];
		}
		
		$question = new $classname();
		$question->initFromDB($question_id);
		$view_data = $question->getEditData();
		
		//-------------------------------
		// Render page
		
		$r .= $this->_cp_header();
		
		$r .= form_open($this->module_uri_middle.AMP.'method=ajax_update_question', array(
			'method'	=> 'post',
			'name'		=> 'question_form',
			'id'		=> 'question_form',
			'class'		=> 'css_form',
			'onsubmit'	=> 'return editQuestion.submitQuestionForm();',
			));
		$r .= html_hidden_inputs(array(
									'classname'		=> $classname,
									'question_id'	=> $question->question_id,
								));
		
		$r .= $this->EE->load->view('edit_question', $view_data, TRUE);
		
		$r .= "</form>";
		
		$this->EE->javascript->output("eequiz.liveShortname('question_shortname', 'title');");
		
		$r .= $this->_cp_footer();
		
		return $r;
    } 
	
	
	
	function ajax_questions_table()
	{
		$column = $this->EE->input->get_post('sort');
		$direction = $this->EE->input->get_post('direction');
		$questions = $this->EE->db->query("SELECT * FROM exp_eequiz_questions ORDER BY {$column} {$direction}");
		
		$html = "";
		
		foreach ($questions->result_array() as $k => $t)
		{
			$contained_links = '';
			$contained = $this->EE->db->query("SELECT * FROM (SELECT DISTINCT quiz_id FROM exp_eequiz_mappings WHERE question_id={$t['question_id']}) as quizzes 
								INNER JOIN exp_eequiz_quizzes AS info ON quizzes.quiz_id=info.quiz_id ORDER BY info.title");
			foreach ($contained->result_array() as $in_quiz)
			{
				$contained_links .= "<a href='".$this->module_url.'&method=edit_quiz&quiz_id='.$in_quiz['quiz_id']."'>".$in_quiz['title'].'</a>, ';
			}
			if ($contained->num_rows() > 0) $contained_links = substr($contained_links, 0, strlen($contained_links)-2);
			
			$row_class = ($k%2 == 1) ? " class='odd' " : " class='even' ";
			
			$coded_title = htmlentities($t['title'], ENT_QUOTES);
			$html .= <<<EOT
<tr {$row_class}>
<td>{$t['question_id']}</td>
<td><a href='{$this->module_url}&method=edit_question&question_id={$t['question_id']}'>{$t['title']}</a></td>
<td>{$t['question_shortname']}</td>
<td>{$contained_links}</td>
<td><a class='duplicate_btn' href='javascript:void(0)' onclick='viewQuestions.duplicateQuestion({$t['question_id']});'>Duplicate</a></td>
<td><a class='delete_btn' href='javascript:void(0)' onclick='eequiz.showConfirmation( viewQuestions.delete_message.replace("{question_title}", "{$coded_title}"), "viewQuestions.deleteQuestion({$t['question_id']})");'>Delete</a></td>
</tr>
EOT;
		}
		
		$json = array(
			'num_rows' => $questions->num_rows,
			'html_string' => $html
		);
		echo json_encode($json);
		exit();
	}
	
	
	
	function ajax_update_question()
	{
		$new = !$this->EE->input->get_post('question_id');
		$classname = $this->EE->input->get_post('classname');
		
		//ob_start();
		//echo "class: ".$classname;
		//print_r($_POST);
		//ob_end_flush();
		//exit();
		
		$question = new $classname();
		$question->initFromPost();
		
		if (!$question->dbSync()) {
			$json = array(
				'success'		=> FALSE,
				'message'		=> "There was an error with your data. Please make sure the shortname you provided is unique.",
				'question_id'	=> $this->EE->input->get_post('question_id')
			);
			ob_start();
			echo json_encode($json);
			ob_flush();
			exit();
		}
		
		if (!$new) {
		
			// Update cached scores
			$quizzes = $this->EE->db->query("SELECT DISTINCT(quiz_id) FROM exp_eequiz_mappings WHERE question_id=".$this->EE->input->get_post('question_id'));
			foreach ($quizzes->result_array() as $q) {
				$quiz = new Quiz();
				$quiz->initFromDB($q["quiz_id"]);
				ModUtil::refresh_cached_answer_data($quiz, 0, TRUE);	// all anonymous
				ModUtil::refresh_cached_answer_data($quiz, 0, FALSE);	// all members
			}
		}
		
		$json = array(
			'success'		=> TRUE,
			'message'		=> $new ? "Successfully created question." : "Successfully updated question.",
			'question_id'	=> $question->question_id
		);
		ob_start();
		echo json_encode($json);
		ob_flush();
		exit();
	}
	
	
	
	function ajax_duplicate_question()
	{
		$question_id = $this->EE->input->get_post('question_id');
		
		$query = $this->EE->db->query("SELECT * FROM exp_eequiz_questions WHERE question_id={$question_id} LIMIT 1");
		if ($query->num_rows() == 0) {
			$json = array(
				'success'		=> FALSE,
				'message'		=> "Error, that question does not exist.",
				'question_id'	=> $question_id
			);
			echo json_encode($json);
			exit();
		}
		
		$query = $query->row_array();
		$question = new $query['classname']();
		$question->initFromDB($query['question_id'], $query); // init from already-obtained db data
		
		$shortnames = $this->EE->db->query("SELECT question_id FROM exp_eequiz_questions WHERE question_shortname LIKE '{$question->question_shortname}%'");
		$question->question_id = 0; //will perform insertion when dbSync is called
		$question->question_shortname .= "_copy".$shortnames->num_rows();
		$question->title .= " (copy ".$shortnames->num_rows().")";
		
		if (!$question->dbSync()) {
			$json = array(
				'success'		=> FALSE,
				'message'		=> "There was an error duplicating this question."
			);
			echo json_encode($json);
			exit();
		}
		
		$json = array(
			'success'		=> TRUE,
			'message'		=> "Successfully duplicated question."
		);
		echo json_encode($json);
		exit();
	}
	
	
	
	function ajax_delete_question()
	{
		$question_id = $this->EE->input->get_post('question_id');
		
		// Make sure question_id exists
		$query = $this->EE->db->query("SELECT question_id FROM exp_eequiz_questions WHERE question_id={$question_id}");
		if ($query->num_rows() == 0) {
			$json = array(
				'success'		=> FALSE,
				'message'		=> "Error, that question does not exist.",
				'question_id'	=> $question_id
			);
			echo json_encode($json);
			exit();
		}
		
		// Update cached scores
		$quizzes = $this->EE->db->query("SELECT DISTINCT(quiz_id) FROM exp_eequiz_mappings WHERE question_id={$question_id}");
		foreach ($quizzes->result_array() as $q) {
			$quiz = new Quiz();
			$quiz->initFromDB($q["quiz_id"]);
			ModUtil::refresh_cached_answer_data($quiz, 0, TRUE);	// all anonymous
			ModUtil::refresh_cached_answer_data($quiz, 0, FALSE);	// all members
		}
		
		// Delete user answers to question
		$maps = $this->EE->db->query("SELECT * FROM exp_eequiz_mappings WHERE question_id={$question_id}");
		foreach ($maps->result_array() as $m)
		{
			$this->EE->db->query("DELETE FROM exp_eequiz_progress WHERE mapping_id='{$m['mapping_id']}'");
		}
		
		// Remove question from quizzes
		$this->EE->db->delete("eequiz_mappings", array('question_id' => $question_id));
		//$this->EE->db->query("DELETE FROM exp_eequiz_mappings WHERE question_id={$question_id}");
		
		// Delete actual question
		$this->EE->db->delete("eequiz_questions", array('question_id' => $question_id));
		//$this->EE->db->query("DELETE FROM exp_eequiz_questions WHERE question_id={$question_id} LIMIT 1");
		
		// Done
		$json = array(
			'success'		=> TRUE,
			'message'		=> "Successfully deleted question.",
			'question_id'	=> $question_id
		);
		echo json_encode($json);
		exit();
	}
    
	
	
	
	
	
	
	
	
	
	
	
	
	// --------------------------------
    //  Quiz Functions
    // --------------------------------
	
	function view_quizzes()
	{
		//-------------------------------
		// HTML Title and navigation crumblinks 
		
		$this->_breadcrumbs(array('view_quizzes'));
		
		//-------------------------------
		// Gather data
		
		$quizzes = $this->EE->db->query("SELECT * FROM exp_eequiz_quizzes");
		$vars = array(
			'quizzes'		=> $quizzes->result_array(),
			'module_url'	=> $this->module_url
		);
		
		//-------------------------------
		// Render page
		
		$r = $this->_cp_header();
		
		$r .= $this->EE->load->view('view_quizzes', $vars, TRUE);
		
		$delete_prompt = $this->EE->lang->line("delete_quiz_prompt");
		$this->EE->javascript->output("viewQuizzes.delete_message = '{$delete_prompt}';");
		
		$r .= $this->_cp_footer();
		
		return $r; 
	}
	
	
	
	function edit_quiz()
	{
		//-------------------------------
		// Breadcrumbs, page title
		
		$r = $this->_breadcrumbs(array('view_quizzes', 'edit_quiz'));
		
		//-------------------------------
		// Gather data
		
		$quiz_id = $this->EE->input->get_post('quiz_id');
		$new = ($this->EE->input->get_post('quiz_id') == FALSE);
		
		$quiz = new Quiz();
		$quiz->initFromDB($quiz_id);
		$view_data = $quiz->getEditData();
		$view_data['mappings'] = $quiz->mappings;
		 
		$questions = $this->EE->db->query("
			SELECT q.question_id, q.title, q.question_shortname, q.tags 
			FROM exp_eequiz_questions AS q 
			WHERE q.question_id NOT IN (
				SELECT m.question_id FROM exp_eequiz_mappings AS m WHERE m.quiz_id=".($quiz_id > 0 ? $quiz_id : "0").
				")
			ORDER BY q.title ASC");
		$view_data['unused_questions'] = $questions->result_array();
		
		
		$number_of_recent = 15;
		$recent_id_limit = $this->EE->db->query("SELECT question_id FROM exp_eequiz_questions ORDER BY question_id DESC LIMIT {$number_of_recent}");
		if ($recent_id_limit->num_rows() < $number_of_recent) $view_data['recent_id_limit'] = -1;
		else {
			$rows = $recent_id_limit->result_array();
			$view_data['recent_id_limit'] = $rows[$number_of_recent-1]["question_id"];
		}
		
		$view_data['used_by_other'] = array();
		$used_by_other_query = $this->EE->db->query("SELECT DISTINCT question_id FROM exp_eequiz_mappings");
		foreach ($used_by_other_query->result_array() as $row) $view_data['used_by_other'][] = $row["question_id"];
		
		//-------------------------------
		// Render page
		
		$r .= $this->_cp_header();
		
		$r .= form_open($this->module_uri_middle.AMP.'method=ajax_update_quiz', array(
			'method'	=> 'post',
			'name'		=> 'quiz_form',
			'id'		=> 'quiz_form',
			'onsubmit'	=> 'return editQuiz.submitQuizForm();',
			));
		$r .= html_hidden_inputs(array(
			'quiz_id'	=> $quiz_id,
			));
		
		$r .= $this->EE->load->view('edit_quiz', $view_data, TRUE);
		
		$r .= "</form>";
		
		//-------------------------------
		// Javascript
		
		$sortable_js = <<<EOT

var options = {
	cursor: 'move',
	revert: 250,
	tolerance: 'pointer',
	receive: function() {
		var unusedHeight = $("ul#unused_question_list").css("height", "auto").height();
		var usedHeight = $("ul#question_list").css("height", "auto").height();
		var max = unusedHeight > usedHeight ? unusedHeight : usedHeight;
		$("ul#unused_question_list").height(max);
		$("ul#question_list").height(max);
		editQuiz.filterQuestions();
	}
};

options.connectWith = "ul#question_list";
$("ul#unused_question_list").sortable(options).disableSelection();

options.connectWith = "ul#unused_question_list";
$("ul#question_list").sortable(options).disableSelection();

options.receive();

EOT;
		
		$this->EE->javascript->output($sortable_js);
		
		$r .= $this->_cp_footer();
		
		return $r;
	}
	
	
	
	function ajax_quizzes_table()
	{
		$column = $this->EE->input->get_post('sort');
		$direction = $this->EE->input->get_post('direction');
		$quizzes = $this->EE->db->query("SELECT * FROM exp_eequiz_quizzes ORDER BY {$column} {$direction}");
		
		$html = "";
		
		foreach ($quizzes->result_array() as $i => $t)
		{	
			$row_class = ($i%2 == 1) ? " class='odd' " : " class='even' ";
			$coded_title = htmlentities($t['title'], ENT_QUOTES);
			
			$html .= <<<EOT
<tr {$row_class}>
<td>{$t['quiz_id']}</td>
<td><a href='{$this->module_url}&method=edit_quiz&quiz_id={$t['quiz_id']}'>{$t['title']}</a></td>
<td>
	<a class='duplicate_btn' href='{$this->module_url}&method=view_answer_data&quiz_id={$t['quiz_id']}'>View Answer Data</a>
</td>
<td>
	<a class='duplicate_btn' href='javascript:void(0)' onclick='viewQuizzes.duplicateQuiz({$t['quiz_id']});'>Duplicate</a>
</td>
<td>
	<a class='delete_btn' href='javascript:void(0)' onclick='eequiz.showConfirmation( viewQuizzes.delete_message.replace("{quiz_title}", "{$coded_title}"), "viewQuizzes.deleteQuiz({$t['quiz_id']})");'>Delete</a>
</td>
</tr>
EOT;
		}
		
		$json = array(
			'num_rows'	=> $quizzes->num_rows(),
			'html_string'	=> $html
		);
		echo json_encode($json);
		exit();
	}
	
	
	
	function ajax_update_quiz()
	{
		$new = !$this->EE->input->get_post('quiz_id');
		
		$quiz = new Quiz();
		$quiz->initFromPost();
		$quiz_id = $quiz->dbSync();
		
		if (!$quiz_id) {
			$json = array(
				'success'		=> FALSE,
				'message'		=> "There was an error creating or updating this quiz.",
				'quiz_id'		=> $this->EE->input->get_post('quiz_id')
			);
			echo json_encode($json);
			exit();
		}
		else {
			
			// update cached scores
			
			$tempQuiz = new Quiz();
			$tempQuiz->initFromDB($quiz_id);
			ModUtil::refresh_cached_answer_data($tempQuiz, 0, TRUE);	// all anonymous
			ModUtil::refresh_cached_answer_data($tempQuiz, 0, FALSE);	// all members
		}
		
		$json = array(
			'success'		=> TRUE,
			'message'		=> $new ? "Successfully created quiz." : "Successfully updated quiz.",
			'quiz_id'		=> $quiz->quiz_id
		);
		echo json_encode($json);
		exit();
	}
	
	
	
	function ajax_duplicate_quiz()
	{
		$quiz_id = $this->EE->input->get_post('quiz_id');
		
		$query = $this->EE->db->query("SELECT * FROM exp_eequiz_quizzes WHERE quiz_id={$quiz_id} LIMIT 1");
		if ($query->num_rows() == 0) {
			$json = array(
				'success'		=> FALSE,
				'message'		=> "Error, that quiz does not exist.",
				'quiz_id'		=> $quiz_id
			);
			echo json_encode($json);
			exit();
		}
		
		$query = $query->row_array();
		
		$this->EE->db->where("quiz_id", $quiz_id);
		$mappings = $this->EE->db->get("eequiz_mappings");
		
		$query["quiz_id"] = "";
		$query["title"] .= " (copy)";
		$this->EE->db->insert('eequiz_quizzes', $query);
		$quiz_id = $this->EE->db->insert_id();
		
		foreach ($mappings->result_array() as $mapping) {
			$mapping["mapping_id"] = "";
			$mapping["quiz_id"] = $quiz_id;
			$mapping["`order`"] = $mapping["order"];
			unset($mapping["order"]);
			$this->EE->db->insert('eequiz_mappings', $mapping);
		}
		
		$json = array(
			'success'		=> TRUE,
			'message'		=> "Successfully duplicated quiz."
		);
		echo json_encode($json);
		exit();
	}
	
	
	
	function ajax_delete_quiz()
	{
		$quiz_id = $this->EE->input->get_post('quiz_id');
		
		// Make sure question_id exists
		$query = $this->EE->db->query("SELECT quiz_id FROM exp_eequiz_quizzes WHERE quiz_id={$quiz_id}");
		if ($query->num_rows() == 0) {
			$json = array(
				'success'		=> FALSE,
				'message'		=> "Error, that quiz does not exist."
			);
			echo json_encode($json);
			exit();
		}
		
		// Delete user answers to questions from quiz
		$maps = $this->EE->db->query("SELECT * FROM exp_eequiz_mappings WHERE quiz_id={$quiz_id}");
		foreach ($maps->result_array() as $m)
		{
			$this->EE->db->delete("eequiz_progress", array('mapping_id' => $m['mapping_id']));
			//$this->EE->db->query("DELETE FROM exp_eequiz_progress WHERE mapping_id='{$m['mapping_id']}'");
		}
		
		// Remove mappings to quiz
		$this->EE->db->delete("eequiz_mappings", array('quiz_id' => $quiz_id));
		//$this->EE->db->query("DELETE FROM exp_eequiz_mappings WHERE quiz_id={$quiz_id}");
		
		// Delete actual quiz
		$this->EE->db->delete("eequiz_quizzes", array('quiz_id' => $quiz_id));
		//$this->EE->db->query("DELETE FROM exp_eequiz_quizzes WHERE quiz_id={$quiz_id} LIMIT 1");
		
		// update cached scores (delete cached entries)
		$this->EE->db->query("DELETE FROM exp_eequiz_cached_scores WHERE quiz_id={$quiz_id}");
		
		// Done
		$json = array(
			'success'		=> TRUE,
			'message'		=> "Successfully deleted quiz."
		);
		echo json_encode($json);
		exit();
	}
    
	
	
	
	
	
	
	
	
	
	// --------------------------------
    //  Quiz Template Functions
    // --------------------------------
	
	function view_quiz_templates()
	{
		//-------------------------------
		// HTML Title and navigation crumblinks 
		
		$this->_breadcrumbs(array('view_quiz_templates'));
		
		//-------------------------------
		// Gather data
		
		$templates = $this->EE->db->get("eequiz_quiz_templates");
		$vars = array(
			'templates'		=> $templates->result_array(),
			'module_url'	=> $this->module_url
		);
		
		//-------------------------------
		// Render page
		
		$r = $this->_cp_header();
		
		$r .= $this->EE->load->view('view_quiz_templates', $vars, TRUE);
		
		$delete_prompt = $this->EE->lang->line("delete_quiz_template_prompt");
		$this->EE->javascript->output("viewTemplates.delete_message = '{$delete_prompt}';");
		
		$r .= $this->_cp_footer();
		
		return $r; 
	}
	
	
	
	function edit_quiz_template()
	{
		//-------------------------------
		// Breadcrumbs, page title
		
		$r = $this->_breadcrumbs(array('view_quiz_templates', 'edit_quiz_template'));
		
		//-------------------------------
		// Gather data
		
		$quiz_template_id = $this->EE->input->get_post('quiz_template_id');
		$new = ($quiz_template_id == FALSE);
		
		if (!$new)
		{
			$template = $this->EE->db->get_where("eequiz_quiz_templates", array('quiz_template_id'=>$quiz_template_id), 1);
			if ($template->num_rows() == 0) return "Error: Invalid quiz_template_id";
			$template = $template->row_array();
		}
		else
		{
			$template = array(
				'quiz_template_id'	=> "",
				'title'				=> "",
				'template'			=> ""
			);
		}
		$template["reference"] = $this->EE->load->view("template_reference", array(), TRUE);
		
		//-------------------------------
		// Render page
		
		$r .= $this->_cp_header();
		
		$r .= form_open($this->module_uri_middle.AMP.'method=ajax_update_quiz_template', array(
			'method'	=> 'post',
			'name'		=> 'quiz_template_form',
			'id'		=> 'quiz_template_form',
			'onsubmit'	=> 'return editTemplate.submit();'
			));
		$r .= html_hidden_inputs(array(
			'quiz_template_id'	=> $quiz_template_id,
			));
		
		$r .= $this->EE->load->view('edit_quiz_template', $template, TRUE);
		
		$r .= "</form>";
		
		$r .= $this->_cp_footer();
		
		return $r;
	}
	
	
	
	function ajax_quiz_templates_table()
	{
		$column = $this->EE->input->get_post('sort');
		$direction = $this->EE->input->get_post('direction');
		$quizzes = $this->EE->db->query("SELECT * FROM exp_eequiz_quiz_templates ORDER BY {$column} {$direction}");
		
		$html = "";
		
		foreach ($quizzes->result_array() as $i => $t)
		{	
			$row_class = ($i%2 == 1) ? " class='odd' " : " class='even' ";
			$coded_title = htmlentities($t['title'], ENT_QUOTES);
			
			$html .= <<<EOT
<tr {$row_class}>
<td>{$t['quiz_template_id']}</td>
<td><a href='{$this->module_url}&method=edit_quiz_template&quiz_template_id={$t['quiz_template_id']}'>{$t['title']}</a></td>
<td><a class='delete_btn' href='javascript:void(0)' onclick='eequiz.showConfirmation( viewTemplates.delete_message.replace("{template_title}", "{$coded_title}"), "viewTemplates.deleteTemplate({$t['quiz_template_id']})");'>Delete</a></td>
</tr>
EOT;
		}
		
		$json = array(
			'num_rows'	=> $quizzes->num_rows(),
			'html_string'	=> $html
		);
		echo json_encode($json);
		exit();
	}
	
	
	
	function ajax_update_quiz_template()
	{
		$new = !$this->EE->input->get_post('quiz_template_id');
		$quiz_template_id = $this->EE->input->get_post('quiz_template_id');
	
		$data = array(
			'quiz_template_id'	=> "",
			'title'				=> $this->EE->input->get_post('title'),
			'template'			=> $this->EE->input->get_post('template'),
		);
		
		if ($new)
		{
			$data['quiz_template_id'] = "";
			$this->EE->db->insert('eequiz_quiz_templates', $data);
			$quiz_template_id = $this->EE->db->insert_id();
		}
		else
		{
			$data['quiz_template_id'] = $quiz_template_id;
			$this->EE->db->where('quiz_template_id', $quiz_template_id);
			$this->EE->db->update('eequiz_quiz_templates', $data);
		}
		
		$json = array(
			'success'			=> TRUE,
			'message'			=> $new ? "Successfully created quiz template." : "Successfully updated quiz.",
			'quiz_template_id'	=> $quiz_template_id
		);
		echo json_encode($json);
		exit();
	}
	
	
	
	function ajax_delete_quiz_template()
	{
		$quiz_template_id = $this->EE->input->get_post('quiz_template_id');
		
		// Make sure exists
		$query = $this->EE->db->query("SELECT quiz_template_id FROM exp_eequiz_quiz_templates WHERE quiz_template_id={$quiz_template_id}");
		if ($query->num_rows() == 0) {
			$json = array(
				'success'		=> FALSE,
				'message'		=> "Error, that template does not exist."
			);
			echo json_encode($json);
			exit();
		}
		
		// Set any quizzes using this template to template=0
		$this->EE->db->query("UPDATE exp_eequiz_quizzes SET quiz_template_id=0 WHERE quiz_template_id={$quiz_template_id}");
		
		// Delete template
		$this->EE->db->delete("eequiz_quiz_templates", array('quiz_template_id' => $quiz_template_id));
		
		// Done
		$json = array(
			'success'		=> TRUE,
			'message'		=> "Successfully deleted template."
		);
		echo json_encode($json);
		exit();
	}
	
	
	
	
	
	
	
	
	// --------------------------------
    //  Answer Data Functions
    // --------------------------------
	
	function view_answer_data()
	{
		//-------------------------------
		// Get some data
		//-------------------------------
		
		$quiz_id = $this->EE->input->get_post('quiz_id');
		
		$quiz = new Quiz();
		$quiz->initFromDB($quiz_id);
		if (!$quiz->quiz_id) return "Error: invalid quiz_id.";
		
		//-------------------------------
		// HTML Title and navigation crumblinks 
		//-------------------------------
		
		$r = $this->_breadcrumbs(array('view_answer_data') );
		$r .= $this->_cp_header();
		
		$subject = $this->EE->input->get_post("subject");
		$query = $this->EE->input->get_post("query");
		$selecteds = array(
			($subject == "username") ? "selected='selected' " : "",
			($subject == "screen_name") ? "selected='selected' " : "",
			($subject == "member_group") ? "selected='selected' " : "",
		);
		$group_options = "";
		$groups = $this->EE->db->query("SELECT group_id, group_title FROM exp_member_groups");
		foreach ($groups->result_array() as $g) {
			$sel_text = ($subject == "member_group" && $query == $g['group_id']) ? "selected='selected' " : "";
			$group_options .= "<option value='{$g['group_id']}' {$sel_text}>{$g['group_title']}</option>";
		}
		$query_dropdown_visible = ($subject == "member_group") ? "" : "style='display: none;' ";
		$query_text_visible = ($subject == "member_group") ? "style='display: none;' " : "";
		
		$r .= "<h1>Filter</h1>";
		$r .= <<<EOT
<div class='section'>
<form id="filter_form" action="{$this->module_url}&method=view_answer_data&quiz_id={$quiz_id}" method="get" onsubmit="return false;">
	Filter:&nbsp;&nbsp;<select name='filter_subject' id='filter_subject' onchange='viewAnswers.changeSubject();'>
		<option value='username' {$selecteds[0]}>Username</option>
		<option value='screen_name' {$selecteds[1]}>Screen Name</option>
		<option value='member_group' {$selecteds[2]}>Member Group</option>
	</select>&nbsp;&nbsp;
	By:&nbsp;&nbsp;
	<select name='filter_member_group' id='filter_member_group' {$query_dropdown_visible}>{$group_options}</select>
	<input type='text' name='filter_query' id='filter_query' value="{$query}" {$query_text_visible}/>&nbsp;&nbsp;
	<input type="button" value="Submit" onclick="viewAnswers.submitFilter(this.form);" />&nbsp;&nbsp;
	<input type="button" value="Clear" onclick="viewAnswers.clearFilter(this.form);" />
</form>
</div>
EOT;

		// build anon and member table, tracking some overall stats at the same time
		
		$answer_chart_table = "";
		$scores = array();
		$num_passing = 0;
		$max_score = 0;
		$num_participants = 0;
		
		// $table_type == 1 => anonymous
		for ($table_type=0; $table_type <=1; $table_type++)
		{
			if (!$quiz->anonymous && $table_type==1) break;
			
			$do_anonymous = ($quiz->anonymous && $table_type==1) ? 1 : 0;
			
			if ($do_anonymous) 
			{
				$participants = $this->EE->db->query("SELECT DISTINCT p.anonymous_member_id AS member_id
											FROM exp_eequiz_anonymous_progress AS p 
												INNER JOIN exp_eequiz_mappings AS m ON p.mapping_id=m.mapping_id
											WHERE m.quiz_id={$quiz->quiz_id} ORDER BY p.anonymous_member_id");
				if ($participants->num_rows() == 0) continue;//return $r."No results.";
			}
			else
			{
				$participant_where = "";
				if ($subject == "member_group") $participant_where = "AND mem.group_id={$query} ";
				if ($subject == "username") $participant_where = "AND mem.username LIKE '%{$query}%' ";
				if ($subject == "screen_name") $participant_where = "AND mem.screen_name LIKE '%{$query}%' ";
				
				$participants = $this->EE->db->query("SELECT DISTINCT p.member_id, mem.screen_name, mem.username, mem.group_id
											FROM exp_eequiz_progress AS p 
												INNER JOIN exp_eequiz_mappings AS m ON p.mapping_id=m.mapping_id
												INNER JOIN exp_members AS mem ON p.member_id=mem.member_id
											WHERE m.quiz_id={$quiz->quiz_id} {$participant_where} ORDER BY p.member_id");//m.`order`");
				if ($participants->num_rows() == 0) continue;//return $r."No results.";
			}
			
			$num_participants += $participants->num_rows();
			
			
			//-------------------------------
			// Table of member-question answers
			//-------------------------------
			
			$answer_chart_table .= "<h1>".$this->EE->lang->line(!($do_anonymous) ? "question_statistics" : "anon_question_statistics")."</h1>";
			
			$h1_text = $this->EE->lang->line('question_header');
			$h2_text = $this->EE->lang->line('final_answers_header');
			$h3_text = $this->EE->lang->line('all_answers_header');
			
			$answer_chart_table .= <<<EOT
	<div style="width: 100%; overflow-x: scroll; overflow-y: visible; margin-bottom: 50px;">
	<table class="all_answers_table">
	<thead>
	<tr>
	<th style='width: 150px;'>Member</th><th>Score</th>
EOT;
			$questions = $this->EE->db->query(" SELECT m.`order`, q.text, m.mapping_id
												FROM exp_eequiz_mappings AS m 
													INNER JOIN exp_eequiz_questions AS q ON m.question_id=q.question_id
												WHERE m.quiz_id={$quiz->quiz_id} ORDER BY m.`order`");
			foreach ($questions->result_array() as $q) $answer_chart_table .= "<th>{$q['order']}. {$q['text']}<br /><a href='javascript:void(0)' onclick='viewAnswers.deleteAnswers({$quiz_id}, 0, {$q['mapping_id']}, {$do_anonymous})'>erase question answers</a></th>";
		
			$answer_chart_table .= <<<EOT
		</tr>
	</thead>
	<tbody>
EOT;
		
			$all_answers = array();
			
			foreach ($participants->result_array() as $p)
			{
				//echo "[{$p['member_id']}]";
				$quiz->initUserData($p['member_id'], $do_anonymous);
				
				$scores[] = $quiz->score;
				$max_score = $quiz->max_score;
				if ($quiz->percent >= $quiz->passing_grade) $num_passing++;
				
				if ($do_anonymous)
					$answer_chart_table .= "<tr><td>Anonymous Member ID: {$p['member_id']}<br /><a href='javascript:void(0)' onclick='viewAnswers.deleteAnswers({$quiz_id}, {$p['member_id']}, 0, 1)'>erase member answers</a></td><td class='score_cell'>{$quiz->score} / {$quiz->max_score} ({$quiz->percent}%)</td>";
				else
					$answer_chart_table .= "<tr><td>{$p['screen_name']} ({$p['username']})<br /><a href='javascript:void(0)' onclick='viewAnswers.deleteAnswers({$quiz_id}, {$p['member_id']}, 0, 0)'>erase member answers</a></td><td class='score_cell'>{$quiz->score} / {$quiz->max_score} ({$quiz->percent}%)</td>";
				
				$i = 0;
				foreach ($quiz->questions as $q)
				{
					if (!isset($all_answers["{$i}"])) $all_answers["{$i}"] = array();
					if ($q->classname != "Matching" && $q->classname != "Essay")
					{
						if (!isset($all_answers["{$i}"]["{$q->last_answer_formatted}"])) $all_answers["{$i}"]["{$q->last_answer_formatted}"] = 0;
						$all_answers["{$i}"]["{$q->last_answer_formatted}"]++;
					}
					$i++;
					
					$answer = ($q->attempts > 0) ? $q->last_answer_formatted : "not answered";
					$max_attempts = ($q->max_attempts == 0) ? "infinite" : $q->max_attempts;
					
					$answer_chart_table .= "<td class='{$q->correctness_class}'>{$answer}<br /><span style='color: #999;'>{$q->attempts} / {$max_attempts} attempts</span><br /><span style='color: #999;'>{$q->score} / {$q->max_weight} points</span><br /><span style='color: #999;'>{$q->last_time_formatted}</span><br /><a href='javascript:void(0)' onclick='viewAnswers.deleteAnswers({$quiz_id}, {$p['member_id']}, {$q->mapping_id}, ".($do_anonymous?1:0).")'>erase answer</a></td>";
				}
				$answer_chart_table .= "</tr>";
			}
		
			$answer_chart_table .= "<tr class='odd_row'><td><b>Most Common Answers</b></td><td class='score_cell'>NA</td>";
			foreach ($all_answers as $q_answers)
			{
				$answer_chart_table .= "<td>";
				if (count($q_answers) == 0) $answer_chart_table .= "answers too complex";
				else
				{
					arsort($q_answers);
					foreach ($q_answers as $k => $v)
					{
						$answer_chart_table .= "{$k} <span style='color: #999;'>({$v} answer".($v > 1 ? "s":"").")</span><br />";
					}
				}
				$answer_chart_table .= "</td>";
			}
			$answer_chart_table .= "</tr>";
			
			$answer_chart_table .= "</tbody></table></div>";
		}
		
		if (count($scores) == 0) return $r."No results.";
		
		//-------------------------------
		// Table of quiz stats
		//-------------------------------
		
		$mean = 0;
		$median = 0;
		$low_score = 999999999;
		$high_score = -1;
		foreach ($scores as $s)
		{
			if ($s < $low_score) $low_score = $s;
			if ($s > $high_score) $high_score = $s;
			$mean += $s;
		}
		
		$low_score = $low_score."&nbsp;&nbsp;&nbsp;(".($max_score==0?0:number_format(100*$low_score/$max_score, 1))."%)";
		$high_score = $high_score."&nbsp;&nbsp;&nbsp;(".($max_score==0?0:number_format(100*$high_score/$max_score, 1))."%)";
		
		$mean /= count($scores);
		$mean .= "&nbsp;&nbsp;&nbsp;(".($max_score==0?0:number_format(100*$mean/$max_score, 1))."%)";
		
		if (count($scores)%2 == 1) $median = $scores[(count($scores)-1)/2];
		else $median = ($scores[count($scores)/2] + $scores[(count($scores)/2)-1])/2;
		$median .= "&nbsp;&nbsp;&nbsp;(".($max_score==0?0:number_format(100*$median/$max_score, 1))."%)";
		
		$r .= "<h1>".$this->EE->lang->line('quiz_statistics')."</h1>";
		
		$h1_text = $this->EE->lang->line('statistic_header');
		$h2_text = $this->EE->lang->line('value_header');
		
		$quiz_stats_table = <<<EOT
<table id="quiz_statistics">
<thead>
	<tr>
	<!-- <th class='' style='width: 200px;'>{$h1_text}</th>
	<th class=''>{$h2_text}</th> -->
	<th style='width: 14%'>Number of Participants</th>
	<th style='width: 14%'>Currently Passing</th>
	<th style='width: 14%'>Max Possible Score</th>
	<th style='width: 14%'>Low Score</th>
	<th style='width: 14%'>High Score</th>
	<th style='width: 14%'>Mean</th>
	<th style='width: 14%'>Median</th>
	</tr>
</thead>
<tbody id='templates_tbody'>
<tr class='odd_row' style='text-align: center;'>
	<td>{$num_participants}</td>
	<td>{$num_passing}</td>
	<td>{$max_score}</td>
	<td>{$low_score}</td>
	<td>{$high_score}</td>
	<td>{$mean}</td>
	<td>{$median}</td>
</tr>
</tbody>
</table>
EOT;
		
		$r .= $quiz_stats_table;
		$r .= $answer_chart_table;
		$r .= $this->_cp_footer();
		
		return $r; 
	}
	
	
	
	function ajax_delete_answers()
	{
		$member_id = $this->EE->input->get_post('member_id');
		$mapping_id = $this->EE->input->get_post('mapping_id');
		$quiz_id = $this->EE->input->get_post('quiz_id');
		$anonymous = $this->EE->input->get_post('anonymous');
		
		$message = "";
		
		if ($member_id && $mapping_id) {
			
			// delete a user's answer to a question
			
			$this->EE->db->delete($anonymous?"eequiz_anonymous_progress":"eequiz_progress", array(
				'mapping_id' => $mapping_id, 
				$anonymous?'anonymous_member_id':'member_id' => $member_id
				));
			
			$message = "Successfully deleted user's answers to the quiz question.";
			
			// update cached scores (update single score)
			$quiz = new Quiz();
			$quiz->initFromDB($quiz_id);
			$quiz->initUserData($member_id, $anonymous);
			ModUtil::refresh_cached_answer_data($quiz, $member_id, $anonymous);
		}
		elseif ($mapping_id && !$member_id) {
			
			// delete all answers to a question
			
			if ($anonymous)
				$this->EE->db->delete("eequiz_anonymous_progress", array('mapping_id' => $mapping_id));
			else
				$this->EE->db->delete("eequiz_progress", array('mapping_id' => $mapping_id));
			
			$message = "Successfully deleted all answers to this quiz question.";
			
			// update cached scores
			$quiz = new Quiz();
			$quiz->initFromDB($quiz_id);
			ModUtil::refresh_cached_answer_data($quiz, 0, $anonymous);
		}
		elseif (!$mapping_id && $member_id && $quiz_id) {
			
			// delete all of a user's answers to this quiz
			
			$this->EE->db->where("quiz_id", $quiz_id);
			$mappings = $this->EE->db->get("eequiz_mappings");
			
			foreach ($mappings->result_array() as $mapping) {
			
				$this->EE->db->delete($anonymous?"eequiz_anonymous_progress":"eequiz_progress", array(
					'mapping_id' => $mapping["mapping_id"], 
					$anonymous?'anonymous_member_id':'member_id' => $member_id
					));
			}
			
			$message = "Successfully deleted all of the the user's answers to this quiz.";
			
			// update cached scores (delete user score)
			$cached_score_member_id = $anonymous ? -1*$member_id : $member_id;
			$this->EE->db->query("DELETE FROM exp_eequiz_cached_scores WHERE member_id={$cached_score_member_id} AND quiz_id={$quiz_id}");
		}
		
		$json = array(
			'success'		=> TRUE,
			'message'		=> $message
		);
		echo json_encode($json);
		exit();
	}
	
	
	
	
	
	
	
	// --------------------------------
    //  Documentation Functions
    // --------------------------------
	
	function view_documentation()
	{
		$r = $this->_breadcrumbs(array('documentation') );
		$r .= $this->_cp_header();
		
		$r .= $this->EE->load->view('documentation', array(), TRUE);

		//$this->EE->cp->add_js_script(array('ui' => 'accordion'));
		//$this->EE->javascript->output("$('#doc_container').accordion({autoHeight: false,header: 'h3'});");
		
		$this->EE->javascript->output("
			$('div#doc_container h3').click(function(){
					$(this).next().toggle('blind');
					$(this).toggleClass('active');
					$('span', $(this)).toggle();
				});
			$('div#doc_container div.doc_section').hide();
			$('div#doc_container h3').prepend('<span class=\"active\" style=\"display: none;\">[-] </span><span class=\"default\">[+] </span>');
			
			$('div#doc_container h3:eq(0)').toggleClass('active');
			$('div#doc_container h3:eq(0) span').toggle();
			$('div#doc_container div.doc_section:eq(0)').show();
		");
		
		$r .= $this->_cp_footer();
		
		return $r;
	}
	
	
	
	
	
	
	
	// --------------------------------
    //  Export Answers
    // --------------------------------
	
	function export_answers()
	{
		/*$this->EE->output->enable_profiler(TRUE);
		$this->EE->output->set_profiler_sections(array(
			'queries' => TRUE,
			'benchmarks' => TRUE,
			'memory_usage' => TRUE,
			'config' => FALSE,
			'controller_info' => FALSE,
			'get' => FALSE,
			'http_headers' => FALSE,
			'post' => FALSE,
			'uri_string' => FALSE
		));
		$profile_string = "<table><tbody>";$profile_total = 0;$profile_time = microtime(TRUE);*/
		
		$quiz_id = $this->EE->input->get_post('quiz_id');
		
		$this->EE->load->helper('download');
		
		if ($quiz_id)
		{
			$answers = $this->EE->db->query("
				SELECT p.user_answer, p.member_id, m.`order`, m.quiz_id, m.question_id, qt.classname, qt.answer, mem.username
				FROM 
					(SELECT MAX(inner_p.progress_id) AS the_progress_id
					 FROM exp_eequiz_progress AS inner_p 
					 GROUP BY member_id, mapping_id) AS left_p
					INNER JOIN exp_eequiz_progress AS p ON left_p.the_progress_id=p.progress_id
					INNER JOIN 
						(SELECT * FROM exp_eequiz_mappings WHERE quiz_id={$quiz_id}) AS m ON p.mapping_id=m.mapping_id
					INNER JOIN exp_eequiz_questions AS qt ON m.question_id=qt.question_id
					INNER JOIN exp_members AS mem ON p.member_id=mem.member_id
				ORDER BY m.quiz_id, p.member_id, m.`order` ASC");
			
			$anon_answers = $this->EE->db->query("
				SELECT p.user_answer, p.anonymous_member_id AS member_id, m.`order`, m.quiz_id, m.question_id, qt.classname, qt.answer
				FROM 
					(SELECT MAX(inner_p.anonymous_progress_id) AS the_progress_id 
					 FROM exp_eequiz_anonymous_progress AS inner_p 
					 GROUP BY anonymous_member_id, mapping_id) AS left_p
					INNER JOIN exp_eequiz_anonymous_progress AS p ON left_p.the_progress_id=p.anonymous_progress_id
					INNER JOIN 
						(SELECT * FROM exp_eequiz_mappings WHERE quiz_id={$quiz_id}) AS m ON p.mapping_id=m.mapping_id
					INNER JOIN exp_eequiz_questions AS qt ON m.question_id=qt.question_id
				ORDER BY m.quiz_id, p.anonymous_member_id, m.`order` ASC");
		}
		else
		{
			$answers = $this->EE->db->query("
				SELECT p.user_answer, p.member_id, m.`order`, m.quiz_id, m.question_id, qt.classname, qt.answer, mem.username
				FROM 
					(SELECT MAX(inner_p.progress_id) AS the_progress_id
					 FROM exp_eequiz_progress AS inner_p 
					 GROUP BY member_id, mapping_id) AS left_p
					INNER JOIN exp_eequiz_progress AS p ON left_p.the_progress_id=p.progress_id
					INNER JOIN exp_eequiz_mappings AS m ON p.mapping_id=m.mapping_id
					INNER JOIN exp_eequiz_questions AS qt ON m.question_id=qt.question_id
					INNER JOIN exp_members AS mem ON p.member_id=mem.member_id
				ORDER BY m.quiz_id, p.member_id, m.`order` ASC");
			
			$anon_answers = $this->EE->db->query("
				SELECT p.user_answer, p.anonymous_member_id AS member_id, m.`order`, m.quiz_id, m.question_id, qt.classname, qt.answer
				FROM 
					(SELECT MAX(inner_p.anonymous_progress_id) AS the_progress_id 
					 FROM exp_eequiz_anonymous_progress AS inner_p 
					 GROUP BY anonymous_member_id, mapping_id) AS left_p
					INNER JOIN exp_eequiz_anonymous_progress AS p ON left_p.the_progress_id=p.anonymous_progress_id
					INNER JOIN exp_eequiz_mappings AS m ON p.mapping_id=m.mapping_id
					INNER JOIN exp_eequiz_questions AS qt ON m.question_id=qt.question_id
				ORDER BY m.quiz_id, p.anonymous_member_id, m.`order` ASC");
		}
		//$profile_time = (microtime(TRUE)- $profile_time);$profile_total += $profile_time;$profile_string .= "<tr><td>query time</td><td>{$profile_time}</td></tr>";$profile_time = microtime(TRUE);
		
		$all_answers = array_merge($answers->result_array(), $anon_answers->result_array());
		$answers->free_result();
		$anon_answers->free_result();
		
		//$profile_time = (microtime(TRUE)- $profile_time);$profile_total += $profile_time;$profile_string .= "<tr><td>merge time</td><td>{$profile_time}</td></tr>";$profile_time = microtime(TRUE);
		
		$del = "\t";
		
		$export_row = array("quiz_id", "question_number (question_id)", "username (member_id)", "correct_answer", "final_answer");
		$data = implode($del, $export_row)."\r\n";
		
		$cached_corrects = array();
		
		foreach ($all_answers as $a) {
			
			$answer_text = $a["user_answer"];
			
			switch ($a["classname"]) {
				
				case "MultipleChoice":
					
					$mult = strstr($a["answer"], " ")!==FALSE;
					
					if (!isset($cached_corrects[$a["question_id"]])) {
						if ($a["answer"] === "") $cached_corrects[$a["question_id"]] = "NA";
						else {
							if ($mult) $cached_corrects[$a["question_id"]] = "options ".$a["answer"];//str_replace(" ", ", ", $a["answer"]);
							else $cached_corrects[$a["question_id"]] = "option ".$a["answer"];
						}
					}
					
					if (strncmp($answer_text, "WRITE-IN:", 9) != 0) {
						if ($mult) $answer_text = "options ".$a["answer"];//str_replace(" ", ", ", $answer_text);
						else $answer_text = "option ".$answer_text;
					}
					else $answer_text = $answer_text;
					
					break;
					
				case "TrueFalse":
				
					if (!isset($cached_corrects[$a["question_id"]])) {
						$cached_corrects[$a["question_id"]] = ($a["answer"] == "1") ? "true" : "false";
					}
					
					$answer_text = ($answer_text == "1") ? "true" : "false";
					break;
					
				case "Matching":
				
					if (!isset($cached_corrects[$a["question_id"]])) {
						$parts = explode(" ", $a["answer"]);
						foreach ($parts as $k => $v) $parts[$k] = "p".($k+1)."-c{$v}";
						$cached_corrects[$a["question_id"]] = implode(", ", $parts);
					}
					
					$parts = explode(" ", $answer_text);
					foreach ($parts as $k => $v) $parts[$k] = "p".($k+1)."-c{$v}";
					$answer_text = implode(", ", $parts);
					
					break;
					
				case "FillInTheBlank":
				
					if (!isset($cached_corrects[$a["question_id"]])) {
						$cached_corrects[$a["question_id"]] = $a["answer"];
					}
					
					break;
					
				case "Essay":
				
					if (!isset($cached_corrects[$a["question_id"]])) {
						$cached_corrects[$a["question_id"]] = "NA";
					}
					
					$answer_text = str_replace(array("\r", "\n", "\t"), " ", $answer_text);
					
					break;
					
				default: break;
			}
			
			$correct_answer = $cached_corrects[$a["question_id"]];
			
			$data .= $a["quiz_id"].$del.
				"{$a["order"]} ({$a["question_id"]})".$del.
				(isset($a["username"]) ? $a["username"] : "anonymous")." ({$a["member_id"]})".$del.
				$correct_answer.$del.
				$answer_text."\r\n";
		}
		
		//$profile_time = (microtime(TRUE)- $profile_time);$profile_total += $profile_time;$profile_string .= "<tr><td>process time</td><td>{$profile_time}</td></tr>";$profile_string .= "<tr><td>total time</td><td>{$profile_total}</td></tr>";$profile_string .= "<tbody></table>";
		
		// FOR TESTING ---------------------------------------------------------------------
		//$data = $profile_string.$data;
		//$data = str_replace("\r\n", "<br />", $data);$data = str_replace("\t", "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;", $data);return $data;
		// ---------------------------------------------------------------------------------
		
		$filename = "";
		if ($quiz_id) $filename = "quiz{$quiz_id}answers";
		else $filename = "all_answers";
		$filename .= ".".date("Y-m-d").".txt";
		
		force_download($filename, $data);
	}
	
	// --------------------------------
    //  Export Scores
    // --------------------------------
	
	function export_scores()
	{
		$this->EE->load->helper('download');
		
		$quiz_id = $this->EE->input->get_post('quiz_id');
		
		if ($quiz_id)
		{
			$query = $this->EE->db->query("
				SELECT cs.*, m.username, q.title 
				FROM exp_eequiz_cached_scores AS cs
				LEFT JOIN exp_members AS m ON cs.member_id=m.member_id
				LEFT JOIN exp_eequiz_quizzes AS q ON cs.quiz_id=q.quiz_id
				WHERE cs.quiz_id={$quiz_id}
				ORDER BY cs.quiz_id, cs.score DESC
				");
		}
		else
		{
			$query = $this->EE->db->query("
				SELECT cs.*, m.username, q.title 
				FROM exp_eequiz_cached_scores AS cs
				LEFT JOIN exp_members AS m ON cs.member_id=m.member_id
				LEFT JOIN exp_eequiz_quizzes AS q ON cs.quiz_id=q.quiz_id
				ORDER BY cs.quiz_id, cs.score DESC
				");
		}
		
		$del = "\t";
		
		$export_row = array("quiz (quiz_id)", "username (member_id)", "score", "percent");
		$data = implode($del, $export_row)."\r\n";
		
		foreach ($query->result_array() as $row) {
			
			$export_row = array(
				"{$row["title"]} ({$row["quiz_id"]})",
				$row["member_id"] > 0 ? "{$row["username"]} ({$row["member_id"]})" : "anonymous (".(-1*$row["member_id"]).")",
				$row["score"],
				number_format($row["percent"]*100, 2, ".", "")
			);
			
			$data .= implode($del, $export_row)."\r\n";
		}
		
		// FOR TESTING ---------------------------------------------------------------------
		//$data = str_replace("\r\n", "<br />", $data);
		//$data = str_replace("\t", "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;", $data);
		//return $data;
		// ---------------------------------------------------------------------------------
		
		$filename = "";
		if ($quiz_id) $filename = "quiz{$quiz_id}scores";
		else $filename = "all_scores";
		$filename .= ".".date("Y-m-d").".txt";
		
		force_download($filename, $data);
	}
	
	
	
	
	
	// --------------------------------
    //  Private Functions
    // --------------------------------
	
	function _breadcrumbs($crumbs)
	{	
		$this->EE->cp->set_breadcrumb($this->module_url, $this->module_name);
		
		foreach ($crumbs as $k => $v)
		{
			if ($k == count($crumbs)-1)
				$this->EE->cp->set_variable('cp_page_title', $this->EE->lang->line($v));
			else
				$this->EE->cp->set_breadcrumb($this->module_url.AMP."method=".$v, $this->EE->lang->line($v));
		}
		
		$this->EE->cp->set_right_nav(array(
			$this->EE->lang->line('overview') => $this->module_url.AMP.'method=overview',
			$this->EE->lang->line('manage_quiz_groups') => $this->module_url.AMP.'method=manage_quiz_groups',
			$this->EE->lang->line('view_questions') => $this->module_url.AMP.'method=view_questions',
			$this->EE->lang->line('view_quizzes') => $this->module_url.AMP.'method=view_quizzes',
			$this->EE->lang->line('view_quiz_templates') => $this->module_url.AMP.'method=view_quiz_templates',
			$this->EE->lang->line('export_all_answers') => $this->module_url.AMP.'method=export_answers',
			"Export Scores" => $this->module_url.AMP.'method=export_scores',
			$this->EE->lang->line('view_documentation') => $this->module_url.AMP.'method=view_documentation'
		));
		
		return "";
	}
	
	
	
	function _cp_header()
	{
		// Clean up any old user data (for when users have been removed)
		$this->EE->db->query("DELETE FROM exp_eequiz_progress WHERE member_id NOT IN (SELECT member_id FROM exp_members)");
		
		$r = "";
		/*
		$r .= <<<EOT
<!--[if IE 6]>
<link rel="stylesheet" type="text/css" href="{$asset_folder}mcp.ie6.css" />
<![endif]-->
<!--[if IE 7]>
<link rel="stylesheet" type="text/css" href="{$asset_folder}mcp.ie7.css" />
<![endif]-->
EOT;
		*/
		
		$r .= "\n\n\n\n<script type='text/javascript'>\n";
		$r .= "//<![CDATA[\n";
		$r .= "moduleURL = '{$this->module_url}'; QEAjaxURL = '{$this->module_url}&method=ajax_request';";
		$r .= "//]]>\n";
		$r .= "</script>\n\n\n\n\n";
		
		$r .= "<div id='iv'>";
		
		return $r."\n\n\n";
	}
	
	
	
	function _cp_footer()
	{
		$create_prompt = $this->EE->lang->line("create_question_prompt");
		
		$types_radio = "";
		sort($this->question_types);
		foreach ($this->question_types as $k => $q_type) 
		{
			$formatted = preg_replace('/([A-Z])/', ' \1', $q_type['classname']);
			$types_radio .= "<input type='radio' name='create_question_type' value='{$q_type['classname']}' ".($k==0 ? "checked='checked' " : "")." /> {$formatted}<br />";
		}
		
		if ($this->EE->input->get("message") == "success") 
			$this->EE->javascript->output("$.ee_notice('Successfully edited item.', {type:'success'});");
			//$this->EE->javascript->output("$.ee_notice('Success!', {type:'success', open:true});");
			//$this->EE->javascript->output("eequiz.showPopup(true, 'Success!');");
		
		$this->EE->javascript->compile();
		
		/*
				<div style="display:none;"><div id="create_question_prompt">
			<div class="prompt_content">
				<p>{$create_prompt}</p>
				<div id='create_question_type_radio_wrapper'>{$types_radio}</div>
			</div>
			<div class="prompt_actions">
				<a class="prompt_continue" href="javascript:void(0)" onclick="eequiz.createQuestion();" >continue</a> 
				<a class="prompt_cancel" href="javascript:void(0)" onclick="$.fancybox.close();" >cancel</a>
			</div>
		</div></div>*/
		
		return <<<EOT
		
		<div class='message_box' style='display: none;'></div>
		
		</div> <!-- close #iv -->
EOT;
	}
	
	
	
	
	
} 
// END CLASS