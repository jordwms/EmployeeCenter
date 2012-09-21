<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
===================================================== 
File: upd.eequiz.php 
----------------------------------------------------- 
Purpose: eeQuiz update/install/uninstall 
===================================================== 
*/

require_once("common.php");

class Eequiz_upd { 
	
    var $version = '1.9.2';
	var $system_name = "Eequiz";
	
    function Eequiz_upd() 
    {
		$this->EE =& get_instance();
    }
	

    function install() 
    { 
		$this->EE->load->dbforge();		
		
		//--------------------------------
		// ee specific
		
		$this->EE->db->insert('modules', array(
			'module_name'		=> $this->system_name,
			'module_version'	=> $this->version,
			'has_cp_backend'	=> 'y'
			));
		
		$this->EE->db->insert('actions', array(
			'class'		=> $this->system_name,
			'method'	=> 'ajax_submit_question'
			));
		
		$this->EE->db->insert('actions', array(
			'class'		=> $this->system_name,
			'method'	=> 'ajax_get_question'
			));
		
		//--------------------------------
		// questions table
		
		$this->EE->dbforge->add_field(array(
			'question_id'			=> array('type' => 'int', 'constraint' => '10', 'unsigned' => TRUE, 'null' => FALSE, 'auto_increment' => TRUE),
			'question_shortname'	=> array('type' => 'varchar', 'constraint' => '255'),
			'title'					=> array('type' => 'text'),
			'text'					=> array('type' => 'text', 'null' => FALSE),
			'explanation'			=> array('type' => 'text'),
			'classname'				=> array('type' => 'varchar', 'constraint' => '32'),
			'optional'				=> array('type' => 'tinyint'),
			'settings'				=> array('type' => 'text'),
			'answer'				=> array('type' => 'text'),
			'max_attempts'			=> array('type' => 'int'),
			'weight'				=> array('type' => 'int'),
			'tags'					=> array('type' => 'text')
			));
		$this->EE->dbforge->add_key('question_id', TRUE);
		$this->EE->dbforge->create_table('eequiz_questions');
		
		//--------------------------------
		// quizzes table
		
		$this->EE->dbforge->add_field(array(
			'quiz_id'			=> array('type' => 'int', 'constraint' => '10', 'unsigned' => TRUE, 'null' => FALSE, 'auto_increment' => TRUE),
			'title'				=> array('type' => 'text'),
			'url_title'			=> array('type' => 'varchar', 'constraint' => '50'),
			'description'		=> array('type' => 'text'),
			'quiz_template_id'	=> array('type' => 'int', 'constraint' => '10', 'unsigned' => TRUE, 'null' => FALSE),
			'disabled'			=> array('type' => 'tinyint'),
			'feedback_mode'		=> array('type' => 'int', 'constraint' => '2'),
			'one_at_a_time'		=> array('type' => 'tinyint'),
			'randomize'			=> array('type' => 'tinyint'),
			'passing_grade'		=> array('type' => 'int'),
			'settings'			=> array('type' => 'text'),
			));
		$this->EE->dbforge->add_key('quiz_id', TRUE);
		$this->EE->dbforge->create_table('eequiz_quizzes');
		
		//--------------------------------
		// quiz templates table
		
		$this->EE->dbforge->add_field(array(
			'quiz_template_id'	=> array('type' => 'int', 'constraint' => '10', 'unsigned' => TRUE, 'null' => FALSE, 'auto_increment' => TRUE),
			'title'				=> array('type' => 'varchar', 'constraint' => '64'),
			'template'			=> array('type' => 'text')
			));
		$this->EE->dbforge->add_key('quiz_template_id', TRUE);
		$this->EE->dbforge->create_table('eequiz_quiz_templates');
		
		//--------------------------------
		// mapping table
		
		$this->EE->dbforge->add_field(array(
			'mapping_id'	=> array('type' => 'int', 'constraint' => '10', 'unsigned' => TRUE, 'null' => FALSE, 'auto_increment' => TRUE),
			'quiz_id'		=> array('type' => 'int', 'constraint' => '10', 'unsigned' => TRUE, 'null' => FALSE),
			'question_id'	=> array('type' => 'int', 'constraint' => '10', 'unsigned' => TRUE, 'null' => FALSE),
			'order'			=> array('type' => 'int')
			));
		$this->EE->dbforge->add_key('mapping_id', TRUE);
		$this->EE->dbforge->add_key('quiz_id');
		$this->EE->dbforge->add_key('question_id');
		$this->EE->dbforge->create_table('eequiz_mappings');
		
		//--------------------------------
		// progress table
		
		$this->EE->dbforge->add_field(array(
			'progress_id'	=> array('type' => 'int', 'constraint' => '10', 'unsigned' => TRUE, 'null' => FALSE, 'auto_increment' => TRUE),
			'member_id'		=> array('type' => 'int', 'constraint' => '10', 'unsigned' => TRUE, 'null' => FALSE),
			'mapping_id'	=> array('type' => 'int', 'constraint' => '10', 'unsigned' => TRUE, 'null' => FALSE),
			'user_answer'	=> array('type' => 'text'),
			'time'			=> array('type' => 'int', 'constraint' => '10')
			));
		$this->EE->dbforge->add_key('progress_id', TRUE);
		$this->EE->dbforge->add_key('mapping_id');
		$this->EE->dbforge->create_table('eequiz_progress');
		
		//--------------------------------
		// create anonymous member table
		
		$this->EE->dbforge->add_field(array(
			'anonymous_member_id'	=> array('type' => 'int', 'constraint' => '10', 'unsigned' => TRUE, 'null' => FALSE, 'auto_increment' => TRUE),
			'anonymous_key'			=> array('type' => 'varchar', 'constraint' => '16'),
			'create_time'			=> array('type' => 'int', 'constraint' => '10')
			));
		$this->EE->dbforge->add_key('anonymous_member_id', TRUE);
		$this->EE->dbforge->create_table('eequiz_anonymous_members');
		
		//--------------------------------
		// create anonymous progress table
		
		$this->EE->dbforge->add_field(array(
			'anonymous_progress_id'	=> array('type' => 'int', 'constraint' => '10', 'unsigned' => TRUE, 'null' => FALSE, 'auto_increment' => TRUE),
			'anonymous_member_id'	=> array('type' => 'int', 'constraint' => '10', 'unsigned' => TRUE, 'null' => FALSE),
			'mapping_id'			=> array('type' => 'int', 'constraint' => '10', 'unsigned' => TRUE, 'null' => FALSE),
			'user_answer'			=> array('type' => 'text'),
			'time'					=> array('type' => 'int', 'constraint' => '10')
			));
		$this->EE->dbforge->add_key('anonymous_progress_id', TRUE);
		$this->EE->dbforge->add_key('mapping_id');
		$this->EE->dbforge->create_table('eequiz_anonymous_progress');
		
		//--------------------------------
		// create cached_scores
			
		$this->EE->dbforge->add_field(array(
			'cached_score_id'		=> array('type' => 'int', 'constraint' => '10', 'unsigned' => TRUE, 'null' => FALSE, 'auto_increment' => TRUE),
			'quiz_id'				=> array('type' => 'int', 'constraint' => '10', 'unsigned' => TRUE, 'null' => FALSE),
			'member_id'				=> array('type' => 'int', 'constraint' => '10', 'null' => FALSE),
			'score'					=> array('type' => 'int', 'constraint' => '10'),
			'percent'				=> array('type' => 'float')
			));
		$this->EE->dbforge->add_key('cached_score_id', TRUE);
		$this->EE->dbforge->create_table('eequiz_cached_scores');
		
		//--------------------------------
		// default data
		
		$template_data = <<<EOT
<h1>({question_number} of {num_questions}). {question_title}</h1>

<div class="question_text">{text}</div>

{answer_section}
{correctness}
{feedback_section}

<div class="question_info">
	{type}<br />
	used {attempts} out of {max_attempts} attempts ({remaining_attempts} remaining)<br />
	earned {score} out of {weight} points<br />
</div>

<div class="question_controls">{previous} {next} {submit}</div>
EOT;
		
		$this->EE->db->insert('eequiz_quiz_templates', array(
			'quiz_template_id'	=> '1',
			'title'				=> 'Full',
			'template'			=> $template_data
			));
			
		$template_data = <<<EOT
<div class="question_progress">Question {question_number} of {num_questions}</div>

<div class="question_text">{text}</div>

{answer_section}

{correctness}

<div class="question_controls">{previous} {next} {submit}</div>
EOT;
		
		$this->EE->db->insert('eequiz_quiz_templates', array(
			'quiz_template_id'	=> '2',
			'title'				=> 'Basic',
			'template'			=> $template_data
			));
		
		return TRUE; 
	}
	

    function uninstall() 
    {
		$this->EE->load->dbforge();	
		
		//--------------------------------
		// ee specific
		
		$this->EE->db->select('module_id');
		$query = $this->EE->db->get_where('modules', array('module_name' => $this->system_name));

		$this->EE->db->where('module_id', $query->row('module_id'));
		$this->EE->db->delete('module_member_groups');
		
		$this->EE->db->where('module_name', $this->system_name);
		$this->EE->db->delete('modules');

		$this->EE->db->where('class', $this->system_name);
		$this->EE->db->delete('actions');
		
		//--------------------------------
		// eeQuiz tables
		
		$this->EE->dbforge->drop_table('eequiz_questions');
		$this->EE->dbforge->drop_table('eequiz_quizzes');
		$this->EE->dbforge->drop_table('eequiz_quiz_templates');
		$this->EE->dbforge->drop_table('eequiz_progress');
		$this->EE->dbforge->drop_table('eequiz_mappings');
		$this->EE->dbforge->drop_table('eequiz_anonymous_members');
		$this->EE->dbforge->drop_table('eequiz_anonymous_progress');

		return TRUE; 
	}
	
	
	function update($current = '')
	{
		if ($current == '' OR $current == $this->version)
		{
			return FALSE;
		}
		
		$this->EE->load->dbforge();	
		
		if ($current < '1.2')
		{
			// create anonymous member table
			
			$this->EE->dbforge->add_field(array(
				'anonymous_member_id'	=> array('type' => 'int', 'constraint' => '10', 'unsigned' => TRUE, 'null' => FALSE, 'auto_increment' => TRUE),
				'anonymous_key'			=> array('type' => 'varchar', 'constraint' => '16'),
				'create_time'			=> array('type' => 'int', 'constraint' => '10')
				));
			$this->EE->dbforge->add_key('anonymous_member_id', TRUE);
			$this->EE->dbforge->create_table('eequiz_anonymous_members');
			
			// create anonymous progress table
			
			$this->EE->dbforge->add_field(array(
				'anonymous_progress_id'	=> array('type' => 'int', 'constraint' => '10', 'unsigned' => TRUE, 'null' => FALSE, 'auto_increment' => TRUE),
				'anonymous_member_id'	=> array('type' => 'int', 'constraint' => '10', 'unsigned' => TRUE, 'null' => FALSE),
				'mapping_id'			=> array('type' => 'int', 'constraint' => '10', 'unsigned' => TRUE, 'null' => FALSE),
				'user_answer'			=> array('type' => 'text'),
				'time'					=> array('type' => 'int', 'constraint' => '10')
				));
			$this->EE->dbforge->add_key('anonymous_progress_id', TRUE);
			$this->EE->dbforge->add_key('mapping_id');
			$this->EE->dbforge->create_table('eequiz_anonymous_progress');
			
			// update quiz settings... change track_answers to anonymous
			
			$quizzes = $this->EE->db->query("SELECT * FROM exp_eequiz_quizzes");
			foreach ($quizzes->result_array() as $q)
			{
				$settings = special_unserialize($q["settings"]);
				$settings["anonymous"] = (!isset($settings["track_answers"])) ? FALSE : !$settings["track_answers"];
				unset($settings["track_answers"]);
				
				$data = array("settings" => special_serialize($settings));
				
				$this->EE->db->where('quiz_id', $q["quiz_id"]);
				$this->EE->db->update('eequiz_quizzes', $data);
			}
		}
		
		if ($current < '1.2.1')
		{
			// add 'weight' multiple choice options
			$questions = $this->EE->db->query("SELECT * FROM exp_eequiz_questions");
			foreach ($questions->result_array() as $q)
			{
				$settings = special_unserialize($q["settings"]);
				if (isset($settings["options"]))
				{
					foreach ($settings["options"] as $k => $v) $settings["options"][$k]["weight"] = 0;
					
					$this->EE->db->where('question_id', $q["question_id"]);
					$this->EE->db->update('eequiz_questions', array("settings" => special_serialize($settings)));
				}
			}
		}
		
		if ($current < '1.5')
		{
			// add 'tags' to questions
			$this->EE->db->query("ALTER TABLE exp_eequiz_questions ADD COLUMN tags TEXT");
		}
		
		if ($current < '1.7.4')
		{
			// 1.6.0 - 1.7.1 didn't create table on new installs... delete it if exists, remake table, recreate data
			
			$this->EE->dbforge->drop_table('eequiz_cached_scores');
			
			// create the cached_scores table, which is used with answer_data tags
			
			$this->EE->dbforge->add_field(array(
				'cached_score_id'		=> array('type' => 'int', 'constraint' => '10', 'unsigned' => TRUE, 'null' => FALSE, 'auto_increment' => TRUE),
				'quiz_id'				=> array('type' => 'int', 'constraint' => '10', 'unsigned' => TRUE, 'null' => FALSE),
				'member_id'				=> array('type' => 'int', 'constraint' => '10', 'null' => FALSE),
				'score'					=> array('type' => 'int', 'constraint' => '10'),
				'percent'				=> array('type' => 'float')
				));
			$this->EE->dbforge->add_key('cached_score_id', TRUE);
			$this->EE->dbforge->create_table('eequiz_cached_scores');
			
			// cache existing scores
			
			// members
			
			$takers = $this->EE->db->query("SELECT DISTINCT member_id FROM exp_eequiz_progress");
			foreach ($takers->result_array() as $taker)
			{
				$quizzes = $this->EE->db->query("
					SELECT * 
					FROM exp_eequiz_quizzes 
					WHERE quiz_id IN (
						SELECT DISTINCT quiz_id 
						FROM exp_eequiz_progress AS p INNER JOIN exp_eequiz_mappings AS m ON p.mapping_id=m.mapping_id
						WHERE member_id={$taker["member_id"]})
					");
				foreach ($quizzes->result_array() as $quiz_data)
				{
					$quiz = new Quiz();
					$quiz->initFromDB($quiz_data["quiz_id"], $quiz_data);
					if ($quiz->anonymous) continue;
					$quiz->initUserData($taker["member_id"], FALSE);
					
					// insert cached member data
					
					$this->EE->db->insert("eequiz_cached_scores", array(
						"quiz_id"	=> $quiz->quiz_id,
						"member_id"	=> $taker["member_id"],
						"score"		=> $quiz->score,
						"percent"	=> $quiz->score/($quiz->max_score > 0 ? $quiz->max_score : 1)
					));
				}
			}
			
			// anonymous
			
			$takers = $this->EE->db->query("SELECT DISTINCT anonymous_member_id FROM exp_eequiz_anonymous_progress");
			foreach ($takers->result_array() as $taker)
			{
				$quizzes = $this->EE->db->query("
					SELECT * 
					FROM exp_eequiz_quizzes 
					WHERE quiz_id IN (
						SELECT DISTINCT quiz_id 
						FROM exp_eequiz_anonymous_progress AS p INNER JOIN exp_eequiz_mappings AS m ON p.mapping_id=m.mapping_id
						WHERE anonymous_member_id={$taker["anonymous_member_id"]})
					");
				foreach ($quizzes->result_array() as $quiz_data)
				{
					$quiz = new Quiz();
					$quiz->initFromDB($quiz_data["quiz_id"], $quiz_data);
					if (!$quiz->anonymous) continue;
					$quiz->initUserData($taker["anonymous_member_id"], TRUE);
					
					// insert cached member data
					
					$this->EE->db->insert("eequiz_cached_scores", array(
						"quiz_id"	=> $quiz->quiz_id,
						"member_id"	=> -1*$taker["anonymous_member_id"],
						"score"		=> $quiz->score,
						"percent"	=> $quiz->score/($quiz->max_score > 0 ? $quiz->max_score : 1)
					));
				}
			}
		}
		
		if ($current < '1.8.3')
		{
			// add 'url_title' to quizzes
			$this->EE->db->query("ALTER TABLE exp_eequiz_quizzes ADD COLUMN url_title VARCHAR(50)");
		}
		
		return TRUE;
	}
	
} 
// END CLASS