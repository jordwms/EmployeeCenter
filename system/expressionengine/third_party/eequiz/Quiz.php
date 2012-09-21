<?php

define("QUIZ_FEEDBACK_HIDE", 1);
define("QUIZ_FEEDBACK_SHOW", 2);
define("QUIZ_FEEDBACK_SHOW_IF_ATTEMPTED", 3);
define("QUIZ_FEEDBACK_SHOW_IF_DONE", 4);
define("QUIZ_FEEDBACK_SHOW_IF_WRONG", 5);

define("QUIZ_EMAIL_OFF", 0);
define("QUIZ_EMAIL_ON_PASS", 1);
define("QUIZ_EMAIL_ON_COMPLETE", 2);

class Quiz
{
	
	var $quiz_id				= 0;
	var $title					= "";
	var $url_title				= "";
	var $description			= "";
	var $quiz_template_id		= "";
	var $disabled				= FALSE;
	var $feedback_mode			= QUIZ_FEEDBACK_SHOW_IF_DONE;
	var $one_at_a_time			= TRUE;
	var $show_submit_all		= FALSE;
	var $anonymous				= FALSE;
	
	var $randomize				= FALSE;
	var $settings				= array();
	var $passing_grade			= 0;
	var $mappings				= array();
	var $mappings_to_orders		= array();
	var $num_questions			= 0;
	
	var $db_data				= array();
	
	var $member_id				= 0;
	var $percent				= 0;
	var $score					= 0;
	var $max_score				= 0;
	var $attempted_any			= FALSE;
	var $attempted_all			= FALSE;
	var $attempted_all_mandatory	= FALSE;
	var $questions				= array();
	var $last_time				= 0;
	var $last_time_formatted	= 0;
	
	var $email_mode				= QUIZ_EMAIL_OFF;
	var $email_recipients		= "";
	var $email_from				= "";
	var $email_subject			= "";
	var $email_message			= "";
	
	function Quiz()
	{
		$this->EE =& get_instance();
	}
	
	function initFromDB($quiz_id, $data = 0)
	{
		if (!$quiz_id) return;
		
		if ($data) $this->db_data = $data;
		else
		{
			$this->EE->db->where("quiz_id", $quiz_id);
			$this->db_data = $this->EE->db->get("exp_eequiz_quizzes", 1);
			//"SELECT * FROM exp_eequiz_quizzes WHERE quiz_id={$quiz_id} LIMIT 1"
			
			if ($this->db_data->num_rows() == 0) return;
			else $this->db_data = $this->db_data->row_array();
		}
		
		$this->quiz_id = $quiz_id;
		
		$this->title = $this->db_data["title"];
		$this->url_title = $this->db_data["url_title"];
		$this->description = $this->db_data["description"];
		$this->quiz_template_id = $this->db_data["quiz_template_id"];
		
		$this->disabled = $this->db_data["disabled"] == 1;
		$this->feedback_mode = $this->db_data["feedback_mode"];
		$this->one_at_a_time = $this->db_data["one_at_a_time"] == 1;
		$this->randomize = $this->db_data["randomize"] == 1;
		
		$this->passing_grade = $this->db_data["passing_grade"];
		
		$this->settings = special_unserialize($this->db_data["settings"]);
		$this->anonymous = (isset($this->settings["anonymous"])) ? $this->settings["anonymous"] : FALSE;
		$this->show_submit_all = (isset($this->settings["show_submit_all"])) ? $this->settings["show_submit_all"] : FALSE;
		
		$this->email_mode			= (isset($this->settings["email_mode"])) ? $this->settings["email_mode"] : QUIZ_EMAIL_OFF;
		$this->email_recipients		= (isset($this->settings["email_recipients"])) ? $this->settings["email_recipients"] : "";
		$this->email_from			= (isset($this->settings["email_from"])) ? $this->settings["email_from"] : "";
		$this->email_subject		= (isset($this->settings["email_subject"])) ? $this->settings["email_subject"] : "";
		$this->email_message		= (isset($this->settings["email_message"])) ? $this->settings["email_message"] : "";
		
		$maps = $this->EE->db->query("SELECT m.*, q.title AS q_title, q.question_shortname AS q_shortname, q.tags AS q_tags
							FROM exp_eequiz_mappings AS m INNER JOIN exp_eequiz_questions AS q ON m.question_id=q.question_id
							WHERE m.quiz_id={$this->quiz_id}
							ORDER BY m.order ASC");
							
		
		$this->num_questions = $maps->num_rows();
		
		foreach ($maps->result_array() as $m) {
			$this->mappings[] = array(
				'mapping_id'	=> $m['mapping_id'],
				'question_id'	=> $m['question_id'],
				'order'			=> $m['order'],
				'title'			=> $m['q_title'],
				'shortname'		=> $m['q_shortname'],
				'tags'			=> $m['q_tags']
			);
			$this->mappings_to_orders[$m['mapping_id']] = $m['order'];
		}
	}
	
	function initFromPost()
	{
		$this->quiz_id = $this->EE->input->get_post('quiz_id');
		
		$this->title = $this->EE->input->get_post("title");
		$this->url_title = $this->EE->input->get_post("url_title");
		$this->description = $this->EE->input->get_post("description");
		$this->quiz_template_id = $this->EE->input->get_post("quiz_template_id");
		
		$this->disabled = (!$this->EE->input->get_post("enabled"));
		$this->feedback_mode = $this->EE->input->get_post("feedback_mode");
		$this->one_at_a_time = (!$this->EE->input->get_post("all_at_once"));
		$this->randomize = ($this->EE->input->get_post("randomize") == 1);
		
		$this->passing_grade = $this->EE->input->get_post("passing_grade");
		
		$this->settings = array();
		$this->anonymous			= $this->settings["anonymous"]			= ($this->EE->input->get_post("anonymous") == 1);
		$this->show_submit_all		= $this->settings["show_submit_all"]	= ($this->EE->input->get_post("show_submit_all") == 1);
		$this->email_mode			= $this->settings["email_mode"]			= ($this->EE->input->get_post("email_mode"));
		$this->email_recipients		= $this->settings["email_recipients"]	= ($this->EE->input->get_post("email_recipients"));
		$this->email_from			= $this->settings["email_from"]			= ($this->EE->input->get_post("email_from"));
		$this->email_subject		= $this->settings["email_subject"]		= ($this->EE->input->get_post("email_subject"));
		$this->email_message		= $this->settings["email_message"]		= ($this->EE->input->get_post("email_message"));
		
		if (strlen($this->EE->input->get_post("quiz_questions_string")) > 0)
		{
			$i = 1;
			$id_strings = str_replace("quiz_question_", "", $this->EE->input->get_post("quiz_questions_string"));
			$id_strings = explode(" ", $id_strings);
			
			foreach ($id_strings as $id_string) {
				
				$ids = explode("_", $id_string);
				
				$this->EE->db->select("question_id, title, question_shortname");
				$this->EE->db->where("question_id", $ids[0]);
				$row = $this->EE->db->get("eequiz_questions");
				//"SELECT question_id, title, question_shortname FROM exp_eequiz_questions WHERE question_id={$ids[0]}"
				$row = $row->row_array();
				
				$this->mappings[] = array(
					'mapping_id'	=> $ids[1], //is zero if new mapping
					'question_id'	=> $ids[0],
					'title'			=> $row['title'],
					'shortname'		=> $row['question_shortname'],
					'order'			=> $i
				);
				
				$i++;
			}
		}
	}
	
	function initUserData($member_id, $do_anonymous)
	{
		$this->member_id = $member_id;
		
		$this->attempted_any = FALSE;
		$this->attempted_all = TRUE;
		$this->attempted_all_mandatory = TRUE;
		$this->score = 0;
		$this->max_score = 0;
		$this->percent = 0;
		
		$this->questions = array();
		$questions = $this->EE->db->query("SELECT * 
						FROM exp_eequiz_mappings AS m INNER JOIN exp_eequiz_questions AS q ON m.question_id=q.question_id 
						WHERE m.quiz_id={$this->quiz_id} ORDER BY m.`order` ASC");
		foreach ($questions->result_array() as $q)
		{
			require_once(QUESTION_TYPES_PATH.$q['classname'].'.php');
			
			$question = new $q['classname']();
			$question->initFromDB($q['question_id'], $q);
			$question->initUserData($q['mapping_id'], $member_id, $do_anonymous /*$this->anonymous */);
			
			$this->questions[] = $question;
			
			$this->max_score += $question->max_weight;
			$this->score += $question->score;
			
			if ($question->last_time > $this->last_time) {
				$this->last_time = $question->last_time;
				$this->last_time_formatted = $question->last_time_formatted;
			}
			
			if ($question->attempts == 0) {
				$this->attempted_all = FALSE;
				if (!$question->optional) $this->attempted_all_mandatory = FALSE;
			}
			else $this->attempted_any = TRUE;
		}
		
		$this->percent = ($this->max_score > 0) ? number_format(100*$this->score/$this->max_score, 1, '.', '') : 100;
	}
	
	
	
	
	
	
	function getEditData()
	{
		$information = array();
		
		$information[] = array(
			"label"			=> $this->EE->lang->line('quiz_title_lbl'),
			"description"	=> $this->EE->lang->line('quiz_title_desc'),
			"content"		=> "<input class='v_required text_input_long' name='title' id='title' type='text' value='".htmlentities($this->title, ENT_QUOTES)."' />"
		);
		$information[] = array(
			"label"			=> "URL Title",
			"description"	=> "This can be used instead of quiz_id to specify quizzes. Use this if you want meaningful urls.",
			"content"		=> "<input class='text_input_long' name='url_title' id='url_title' type='text' value='".htmlentities($this->url_title, ENT_QUOTES)."' />"
		);
		$information[] = array(
			"label"			=> $this->EE->lang->line('quiz_description_lbl'),
			"description"	=> $this->EE->lang->line('quiz_description_desc'),
			"content"		=> "<textarea name='description' id='description'>{$this->description}</textarea>"
		);
		
		$templates = $this->EE->db->get("eequiz_quiz_templates");
		$template_dropdown = "<select name='quiz_template_id'><option value='0'>---</option>";
		foreach ($templates->result_array() as $t) $template_dropdown .= "<option value='{$t['quiz_template_id']}' ".(($t['quiz_template_id'] == $this->quiz_template_id) ? "selected='selected' ": "").">{$t['title']}</option>";
		$template_dropdown .= "</select>";
		$information[] = array(
			"label"			=> $this->EE->lang->line('quiz_template_lbl'),
			"description"	=> $this->EE->lang->line('quiz_template_desc'),
			"content"		=> $template_dropdown
		);
		
		
		
		
		$settings = array();
		
		$settings[] = array(
			"label"				=> $this->EE->lang->line('quiz_status_lbl'),
			"description"		=> $this->EE->lang->line('quiz_status_desc'),
			"content"			=> "<input class='lightswitch' type='checkbox' name='enabled' value='1' ".(!$this->disabled ? "checked='checked' ": "")." />"
		);
		$settings[] = array(
			"label"				=> $this->EE->lang->line('quiz_feedback_lbl'),
			"description"		=> $this->EE->lang->line('quiz_feedback_desc'),
			"content"			=> "<select name='feedback_mode'>".
										"<option ".($this->feedback_mode == QUIZ_FEEDBACK_HIDE ? "selected='selected' " : "")." value='".QUIZ_FEEDBACK_HIDE."'>always hide</option>".
										"<option ".($this->feedback_mode == QUIZ_FEEDBACK_SHOW ? "selected='selected' " : "")." value='".QUIZ_FEEDBACK_SHOW."'>always show</option>".
										"<option ".($this->feedback_mode == QUIZ_FEEDBACK_SHOW_IF_ATTEMPTED ? "selected='selected' " : "")." value='".QUIZ_FEEDBACK_SHOW_IF_ATTEMPTED."'>show if attempted</option>".
										"<option ".($this->feedback_mode == QUIZ_FEEDBACK_SHOW_IF_DONE ? "selected='selected' " : "")." value='".QUIZ_FEEDBACK_SHOW_IF_DONE."'>show if no more attempts or correctly answered</option>".
										"<option ".($this->feedback_mode == QUIZ_FEEDBACK_SHOW_IF_WRONG ? "selected='selected' " : "")." value='".QUIZ_FEEDBACK_SHOW_IF_WRONG."'>show if incorrectly answered</option>".
										"</select>"
		);
		$settings[] = array(
			"label"				=> $this->EE->lang->line('quiz_display_lbl'),
			"description"		=> $this->EE->lang->line('quiz_display_desc'),
			"content"			=> "<input class='lightswitch' type='checkbox' name='all_at_once' value='1' ".(!$this->one_at_a_time ? "checked='checked' ": "")." />"
		);
		$settings[] = array(
			"label"				=> $this->EE->lang->line('quiz_submit_all_lbl'),
			"description"		=> $this->EE->lang->line('quiz_submit_all_desc'),
			"content"			=> "<input class='lightswitch' type='checkbox' name='show_submit_all' value='1' ".($this->show_submit_all ? "checked='checked' ": "")." />"
		);
		$settings[] = array(
			"label"				=> $this->EE->lang->line('quiz_anonymous_lbl'),
			"description"		=> $this->EE->lang->line('quiz_anonymous_desc'),
			"content"			=> "<input class='lightswitch' type='checkbox' name='anonymous' value='1' ".($this->anonymous ? "checked='checked' ": "")." />"
		);
		$settings[] = array(
			"label"				=> $this->EE->lang->line('quiz_passing_grade_lbl'),
			"description"		=> $this->EE->lang->line('quiz_passing_grade_desc'),
			"content"			=> "<input class='v_required positive_integer' name='passing_grade' id='passing_grade' type='text' value='{$this->passing_grade}' />"
		);
		$settings[] = array(
			"label"				=> "Email Notifications",
			"description"		=> "Select if and how you want email notifications to be sent. They can be turned off, sent when a user passes a quiz, or sent when a user answers all mandatory questions. If notifications are turned off, then the following email fields can be ignored.",
			"content"			=> "<select name='email_mode'>".
									"<option ".($this->email_mode == QUIZ_EMAIL_OFF ? "selected='selected' " : "")." value='".QUIZ_EMAIL_OFF."'>off</option>".
									"<option ".($this->email_mode == QUIZ_EMAIL_ON_PASS ? "selected='selected' " : "")." value='".QUIZ_EMAIL_ON_PASS."'>sent when user passes the quiz</option>".
									"<option ".($this->email_mode == QUIZ_EMAIL_ON_COMPLETE ? "selected='selected' " : "")." value='".QUIZ_EMAIL_ON_COMPLETE."'>sent when user completes the quiz</option>".
									"</select>"
		);
		$settings[] = array(
			"label"				=> "Email Recipients",
			"description"		=> "Enter who the email should be sent to, using commas if there are multiple recipients. If you would like the email to be sent to the user, add {user_email} to the list.<br />Example: admin@yoursite.com, {user_email}",
			"content"			=> "<input class='text_input_long' type='text' name='email_recipients' value='".$this->email_recipients."' />"
		);
		$settings[] = array(
			"label"				=> "Email From",
			"description"		=> "Who the email is from.",
			"content"			=> "<input class='text_input_long' type='text' name='email_from' value='".$this->email_from."' />"
		);
		$settings[] = array(
			"label"				=> "Email Subject",
			"description"		=> "The subject of the email. You may use {quiz_title}, {screen_name}, and/or {username} when using this.<br />Example: {username} just passed {quiz_title}",
			"content"			=> "<input class='text_input_long' type='text' name='email_subject' value='".$this->email_subject."' />"
		);
		$settings[] = array(
			"label"				=> "Email Message",
			"description"		=> "The main message of the email. You may use any ExpressionEngine tags here, and there are three additional variables provided for you: quiz_id, member_id, and anonymous (true if the user is an anonymous user). <br />Example: <br />".
									"{exp:eequiz:quizzes quiz_id='{quiz_id}'}<br / >{quiz_title} was just completed with a score of: {grade_score}<br / >{/exp:eequiz:quizzes}",
			"content"			=> "<textarea name='email_message' style='height: 200px;'>".$this->email_message."</textarea>"
		);
		
		
		
		
		$questions = array();
		
		/*$questions[] = array(
			"label"				=> $this->EE->lang->line('quiz_add_question_lbl'),
			"description"		=> $this->EE->lang->line('quiz_add_question_desc'),
			"content"			=> "<input class='v_positive_integer text_input_short' name='weight' id='weight' type='text' value='{$this->weight}' />"
		);*/
		
		
		
		
		$result = array(
			'information'		=> $information,
			'settings'			=> $settings,
			'questions'			=> $questions
		);
		
		$result['extra'] = <<<EOT
<script type="text/javascript">
//<![CDATA[

$(document).ready(function() {

	$("div#iv input[name='all_at_once']").change(function(){
		if (!$(this).attr('checked'))
			$("input[name='show_submit_all']").removeAttr('checked').attr('disabled', 'disabled').lightSwitch();
		else 
			$("input[name='show_submit_all']").removeAttr('disabled').lightSwitch();
	}).trigger("change");
});

//]]>
</script>
EOT;
		
		return $result;
	}
	
	
	
	
	function dbSync()
	{
		$data = array(
			'title'					=> $this->title,
			'url_title'				=> $this->url_title,
			'description'			=> $this->description,
			'quiz_template_id'		=> $this->quiz_template_id,
			
			'disabled'				=> ($this->disabled) ? 1 : 0,
			'feedback_mode'			=> $this->feedback_mode,
			'one_at_a_time'			=> $this->one_at_a_time ? 1 : 0,
			'randomize'				=> $this->randomize ? 1 : 0,
			'passing_grade'			=> $this->passing_grade,
			
			'settings'				=> special_serialize($this->settings)
		);
		
		// update or insert main quiz data
		
		if ($this->quiz_id)
		{
			$this->EE->db->where('quiz_id', $this->quiz_id);
			$this->EE->db->update('eequiz_quizzes', $data);
		}
		else
		{
			$data['quiz_id'] = '';
			
			$this->EE->db->insert('eequiz_quizzes', $data);
			$this->quiz_id = $this->EE->db->insert_id();
		}
		
		// delete any hanging mappings and associated answer data
		$map_id_string = array();
		foreach ($this->mappings as $map) {
			if ($map['mapping_id'] != 0) $map_id_string[] = $map['mapping_id'];
		}
		$map_id_string = implode(", ", $map_id_string);
		
		if (strlen($map_id_string) > 0)
		{
			// delete the mapping ids that aren't attached to the quiz
			$hanging_maps = $this->EE->db->query("SELECT * FROM exp_eequiz_mappings WHERE quiz_id={$this->quiz_id} AND mapping_id NOT IN ({$map_id_string})");
			foreach ($hanging_maps->result_array() as $map)
			{
				$this->EE->db->delete("eequiz_progress", array('mapping_id' => $map['mapping_id']));
				$this->EE->db->delete("eequiz_mappings", array('mapping_id' => $map['mapping_id']));
			}
		}
		else
		{
			// there are no questions in this quiz, so delete all mappings attached
			$hanging_maps = $this->EE->db->get_where("eequiz_mappings", array("quiz_id" => $this->quiz_id));
			foreach ($hanging_maps->result_array() as $map)
			{
				$this->EE->db->delete("eequiz_progress", array('mapping_id' => $map['mapping_id']));
				$this->EE->db->delete("eequiz_mappings", array('mapping_id' => $map['mapping_id']));
			}
		}
		
		// update or insert mappings
		foreach ($this->mappings as $map)
		{
			$data = array(
				'question_id'	=> $map['question_id'],
				'quiz_id'		=> $this->quiz_id,
				'`order`'			=> $map['order']
			);
			
			if ($map['mapping_id'] == 0)
			{
				$data['mapping_id'] = '';
				$this->EE->db->insert('eequiz_mappings', $data);
			}
			else
			{
				$this->EE->db->where('mapping_id', $map['mapping_id']);
				$this->EE->db->update('eequiz_mappings', $data); 
			}
		}
		
		return TRUE;
	}

}