<?php
class score {
	public function __construct() {
		$this->EE =& get_instance();
	}

	function score_card($group_id=NULL, $active_member_id=NULL) {
		if( !( is_null($group_id) && is_null($active_member_id) )) {
			//mmm chunky SQL
			$scores = $this->EE->db->query(
				"SELECT exp_eequiz_quizzes.quiz_id AS quiz_id,
					exp_eequiz_quizzes.title AS quiz_title,
					ROUND(scores.percent*100) AS grade_percent,
					quiz_progress.last_answer_time AS last_answer_time,
					exp_eequiz_quizzes.passing_grade AS passing_grade, 
					IF (scores.percent*100 > exp_eequiz_quizzes.passing_grade, 'TRUE', 'FALSE') AS passing,
					DATEDIFF(NOW(), FROM_UNIXTIME(last_answer_time)) AS days_since_last_answer
				FROM exp_eequiz_quizzes
				INNER JOIN exp_eequiz_group_quizzes ON exp_eequiz_group_quizzes.quiz_id = exp_eequiz_quizzes.quiz_id  AND exp_eequiz_group_quizzes.quiz_group_id = $group_id
				LEFT JOIN (
					SELECT quiz_id, MAX(time) AS last_answer_time FROM exp_eequiz_mappings
					INNER JOIN exp_eequiz_progress ON exp_eequiz_progress.mapping_id=exp_eequiz_mappings.mapping_id
					WHERE member_id = $active_member_id
					GROUP BY exp_eequiz_mappings.quiz_id
				) AS quiz_progress ON exp_eequiz_quizzes.quiz_id=quiz_progress.quiz_id
				LEFT JOIN (
					SELECT * FROM exp_eequiz_cached_scores WHERE member_id=$active_member_id
				) AS scores ON scores.quiz_id=exp_eequiz_quizzes.quiz_id
				WHERE exp_eequiz_quizzes.disabled=0
				ORDER by exp_eequiz_quizzes.title"
			);

			return $scores->result_array();
		}
	}
	
	function score_card2($group_id=NULL, $active_member_id=NULL) {
		if( !( is_null($group_id) && is_null($active_member_id) )) {
			//Extra Chunky SQL...
			//I don't really trust the "cached_answers" table, so I'm pulling the full results from progress
			//I'd Imagine this could be a little less crazy (fewer sub queries anyone?), but it gets the job done accurately with all the data you'd want
			//Also, I'd reason that the .0019 seconds this takes on my server is better than the 4+ second page render time the normal quiz tag takes
			$scores = $this->EE->db->query(
				"SELECT 
					exp_eequiz_quizzes.quiz_id AS quiz_id,
					exp_eequiz_quizzes.title AS quiz_title,
					user_grade,
					passing_grade,
					num_correct,
					num_required,
					last_answer_time,
					days_since_last_answer,
					attempted_all_required
				FROM 
				exp_eequiz_quizzes
				LEFT JOIN (
					SELECT 
						exp_eequiz_mappings.quiz_id,
						ROUND((SUM(exp_eequiz_questions.weight)/min_passing_score)*100) AS user_grade,
						COUNT(*) AS num_correct,
						num_required,
						MAX(exp_eequiz_progress.time) AS last_answer_time,
						DATEDIFF(NOW(), FROM_UNIXTIME(exp_eequiz_progress.time)) AS days_since_last_answer,
						IF (quiz_progress.num_attempted >= quiz_progress.num_required, 'TRUE', 'FALSE') AS attempted_all_required
					FROM exp_eequiz_progress
					INNER JOIN exp_eequiz_mappings ON exp_eequiz_mappings.mapping_id = exp_eequiz_progress.mapping_id
					INNER JOIN exp_eequiz_questions ON exp_eequiz_questions.question_id = exp_eequiz_mappings.question_id
					INNER JOIN exp_eequiz_quizzes ON exp_eequiz_quizzes.quiz_id = exp_eequiz_mappings.quiz_id
					INNER JOIN (
						SELECT quiz_id, SUM(weight) as min_passing_score
						FROM exp_eequiz_mappings
						INNER JOIN exp_eequiz_questions ON exp_eequiz_questions.question_id = exp_eequiz_mappings.question_id
						WHERE optional = 0
						GROUP BY quiz_id
					) AS quiz_required_scores ON quiz_required_scores.quiz_id = exp_eequiz_mappings.quiz_id
					INNER JOIN (
						SELECT exp_eequiz_mappings.quiz_id, COUNT(*) AS num_attempted, num_required
						FROM exp_eequiz_progress
						INNER JOIN exp_eequiz_mappings ON exp_eequiz_mappings.mapping_id = exp_eequiz_progress.mapping_id
						INNER JOIN exp_eequiz_questions ON exp_eequiz_questions.question_id = exp_eequiz_mappings.question_id
						INNER JOIN (
							SELECT quiz_id, COUNT(*) AS num_required
							FROM exp_eequiz_questions
							INNER JOIN exp_eequiz_mappings ON exp_eequiz_mappings.question_id = exp_eequiz_questions.question_id
							WHERE optional=0
							GROUP BY quiz_id
						) AS required ON required.quiz_id = exp_eequiz_mappings.quiz_id
						WHERE optional=0 AND member_id=$active_member_id
						GROUP BY exp_eequiz_mappings.quiz_id
					) AS quiz_progress ON quiz_progress.quiz_id = exp_eequiz_mappings.quiz_id
					WHERE 
						member_id=$active_member_id
						AND user_answer=answer
					GROUP BY exp_eequiz_mappings.quiz_id
				) AS user_quiz_results ON user_quiz_results.quiz_id = exp_eequiz_quizzes.quiz_id	
				INNER JOIN exp_eequiz_group_quizzes 
				ON exp_eequiz_quizzes.quiz_id = exp_eequiz_group_quizzes.quiz_id AND exp_eequiz_group_quizzes.quiz_group_id = $group_id"
			);

			return $scores->result_array();
		}
	}
	
	//Returns the number of quizzes that a user is passing in a group
	//If I trusted the cached_scores table, this would be simpler.
	//The group is defined as the template tag parameter: group_id
	//Default behavior is current user
	function number_passing_in_group($group_id=NULL, $active_member_id=NULL) {
		if( !( is_null($group_id) && is_null($active_member_id) )) {
			//mmm chunky SQL
			$number_passing_in_group = $this->EE->db->query(
				"SELECT COUNT(*) AS number_passing
				FROM exp_eequiz_group_quizzes
				INNER JOIN 
				(
					SELECT exp_eequiz_mappings.quiz_id, exp_eequiz_quizzes.passing_grade, ROUND((SUM(weight) / min_passing_score)*100) AS user_grade
					FROM exp_eequiz_progress
					INNER JOIN exp_eequiz_mappings ON exp_eequiz_mappings.mapping_id = exp_eequiz_progress.mapping_id
					INNER JOIN exp_eequiz_questions ON exp_eequiz_questions.question_id = exp_eequiz_mappings.question_id
					INNER JOIN exp_eequiz_quizzes ON exp_eequiz_mappings.quiz_id = exp_eequiz_quizzes.quiz_id
					RIGHT JOIN (
						SELECT quiz_id, SUM(weight) as min_passing_score
						FROM exp_eequiz_mappings
						INNER JOIN exp_eequiz_questions ON exp_eequiz_questions.question_id=exp_eequiz_mappings.question_id
						WHERE optional = 0
						GROUP BY quiz_id
					) AS quiz_required_scores ON quiz_required_scores.quiz_id = exp_eequiz_mappings.quiz_id
					WHERE 
						user_answer=answer 
						AND member_id = $active_member_id
					GROUP BY exp_eequiz_mappings.quiz_id
				) AS user_grades
				ON user_grades.quiz_id=exp_eequiz_group_quizzes.quiz_id
				WHERE quiz_group_id = $group_id AND user_grade>=passing_grade"
			);
			
			return $number_passing_in_group->row('number_passing');
		}
	}

	//Returns the number of quizzes in a "group"
	//The group is defined as the template tag parameter: group_id
	function number_in_group($group_id=NULL, $member_id=NULL) {
		$query = $this->EE->db->select('COUNT(*) AS number_in_group')
		->from('eequiz_group_quizzes')
		->where(array('quiz_group_id' => $group_id))
		->get();
		
		return $query->row('number_in_group');
	}

	function passing_all_in_group($group_id=NULL, $active_member_id=NULL){
		if($this->number_passing_in_group($group_id, $active_member_id)==$this->number_in_group($group_id)){
			return true;
		} else {
			return false;
		}
	}

	// Returns all quiz groups 
	function get_quiz_groups() {
		return $this->EE->db->select('*')
							->from('eequiz_quiz_groups')
							->order_by('name','ASC')
							->get()
							->result_array();
	}

	// For a given quiz-group ID, returns all quizzes associated with that quiz.
	function get_quizzes_in_group($group_id, $select='*') {
		return $this->EE->db->select($select)
							->from('eequiz_group_quizzes')
							->where('quiz_group_id', $group_id)
							->join('eequiz_quizzes', 'eequiz_group_quizzes.quiz_id = eequiz_quizzes.quiz_id')
							->order_by('title', 'ASC')
							->get()
							->result_array();

	}

	/*
	* For a given quiz-group ID, returns all quizzes not associated with that quiz-group.
	*/
	function get_quizzes_not_in_group($group_id) {
		/* ---- STRONG QUERY ----
		SELECT *
		FROM `exp_eequiz_quizzes`
		LEFT JOIN `exp_eequiz_group_quizzes` ON  `exp_eequiz_quizzes`.`quiz_id` = `exp_eequiz_group_quizzes`.`quiz_id`
		WHERE COALESCE(`exp_eequiz_group_quizzes`.`quiz_group_id`,0) != 1
		*/	
		return $query = $this->EE->db->select('eequiz_quizzes.quiz_id, eequiz_quizzes.title')
							->from('eequiz_quizzes')
							->join('eequiz_group_quizzes', 'eequiz_quizzes.quiz_id = eequiz_group_quizzes.quiz_id', 'left')
							->where("COALESCE(`exp_eequiz_group_quizzes`.`quiz_group_id`,0) != $group_id")
							->order_by('title', 'ASC')
							->get()
							->result_array();
	}
} // END CLASS