<?php

if (!defined("QUIZ_ENGINE_PATH"))		define("QUIZ_ENGINE_PATH", PATH_THIRD.'eequiz'.DIRECTORY_SEPARATOR);
if (!defined("QUESTION_TYPES_PATH"))	define("QUESTION_TYPES_PATH", QUIZ_ENGINE_PATH.'types'.DIRECTORY_SEPARATOR);

if (phpversion() < 5.2) require('JSON_PHP4.php');
require_once("utility.php");
require_once("Question.php");
require_once("Quiz.php");


class ModUtil {
	
	static $EE;
	
	static function init()
	{
		self::$EE =& get_instance();
	}
	
	// -------------------------------------
	//  _refresh_cached_answer_data
	// -------------------------------------

	static function refresh_cached_answer_data(&$quiz, $member_id, $do_anonymous)
	{
		if ($member_id == 0) {
			
			$temp_quiz = new Quiz();
			$temp_quiz->initFromDB($quiz->quiz_id);
			
			$cached_to_update = self::$EE->db->query("SELECT * FROM exp_eequiz_cached_scores WHERE quiz_id={$quiz->quiz_id}");
			foreach ($cached_to_update->result_array() as $row) {
				
				if ($do_anonymous && $row["member_id"] > 0) continue;
				elseif (!$do_anonymous && $row["member_id"] < 0) continue;
				
				$temp_quiz->initUserData($row["member_id"], $do_anonymous);
				ModUtil::refresh_cached_answer_data($temp_quiz, ($row["member_id"] < 0 ? -1*$row["member_id"] : $row["member_id"]), $do_anonymous);
			}
		}
		else {
			
			if ($do_anonymous) $member_id = -1*$member_id;
		
			self::insert_or_update("eequiz_cached_scores", array(
					"quiz_id"	=> $quiz->quiz_id,
					"member_id"	=> $member_id,
					"score"		=> $quiz->score,
					"percent"	=> $quiz->score/($quiz->max_score > 0 ? $quiz->max_score : 1)
				), array(
					'quiz_id' => $quiz->quiz_id,
					'member_id' => $member_id
				));
		}
	}
	
	
	
	static function insert_or_update($table, $data, $where)
    {
        $query = self::$EE->db->get_where($table, $where);

        if ($query->num_rows() == 0)
        {
            self::$EE->db->insert($table, $data);
            return self::$EE->db->insert_id();
        }
        elseif ($query->num_rows() == 1)
        {
            self::$EE->db->where($where)->update($table, $data);
            return true;
        }
    }


}

ModUtil::init();

?>