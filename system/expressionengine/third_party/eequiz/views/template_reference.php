
		<div class="subsection">
			<h2>Simple Value Tags</h2>
			<p>The following tags are values only and include no html/css. Feel free to use them in conditionals.</p>
			<ul>
			<li>{num_questions}</li>
			<li>{question_number}</li>
			<li>{question_title}</li>
			<li>{question_shortname}</li>
			<li>{question_tags}</li>
			<li>{text}</li>
			<li>{attempts}</li>
			<li>{max_attempts}</li>
			<li>{remaining_attempts}</li>
			<li>{answer_time format="m/d/Y"} : The time the user entered their last answer. The format parameter is optional; see php's <a href="http://php.net/manual/en/function.date.php">date documentation</a> for details on how to use it.</li>
			<li>{score} : The user's earned score for this question</li>
			<li>{weight} : The amount of points this question is worth</li>
			<li>{correctness} : Will be either "correct", "incorrect", "partially_correct", depending on the user's answer.</li>
			<li>{feedback_explanation} : This is the overall question explanation, with no extra html. Use this if you want to get at what's inside {feedback_section}</li>
			<li>{feedback_extra} : This is question-specific extra feedback, with no html. Use this if you want to get at what's inside {feedback_section}</li>
			<li>{quiz_title}</li>
			<li>{quiz_id}</li>
			<li>{quiz_description}</li>
			<li>{quiz_score} : The amount of points the user has earned on this quiz</li>
			<li>{quiz_percent} : The user's current score for this quiz, expressed as a number between 0 and 100</li>
			<li>{quiz_max_score} : The max amount of points possible on this quiz</li>
			<li>{quiz_passing_score} : The amount of points to be considered passing for this quiz</li>
			</ul>
		</div>
		<div class="subsection">
			<h2>Section Tags</h2>
			<p>The following tags include html and css classes. See documentation for styling guidelines.</p>
			<dl>
			<dt>{answer_section}</dt>
			<dd>The section where the user enters their answer</dd>
			<dt>{feedback_section}</dt>
			<dd>The section where feedback is provided to the user</dd>
			<dt>{correctness}</dt>
			<dd>The section that tells whether the user got the problem right or wrong (ex: &lt;div class='correct_mark'&gt;&lt;span&gt;correct&lt;/span&gt;&lt;div&gt;). If used within a conditional, it will evaluate to "", "correct", "incorrect", or "partially_correct".</dd>
			<dt>{previous label="optional label"}</dt>
			<dd>The button to go to the previous question. Will automatically evaluate to nothing if quiz display is set to 'view all at once'. Use the optional label param to customize the button.</dd>
			<dt>{next label="optional label"}</dt>
			<dd>The button to go to the next question. Will automatically evaluate to nothing if quiz display is set to 'view all at once'. Use the optional label param to customize the button.</dd>
			<dt>{submit label="optional label"}</dt>
			<dd>The button to submit the user's answer. Use the optional label param to customize the button.</dd>
			<dt>{submit_and_advance label="optional label"}</dt>
			<dd>This button will submit the user's answer and automatically advance to the next question. If this is on an all-at-once quiz, it will revert to a regular submit button. Use the optional label param to customize the button.</dd>
			</dl>
		</div>
