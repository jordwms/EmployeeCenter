<h1><?=lang('quiz_information')?></h1>
<table class="mainTable">
<tbody>
		<?php foreach ($information as $k => $row) { ?>
			<tr class="<?= $k%2==1 ? "odd" : "even" ?>">
				<td style='width: 320px;'><label><?=$row['label']?><span><?=$row['description']?></span></label></td>
				<td><?=$row['content']?></td>
			</tr>
		<?php } ?>
</tbody>
</table>


<h1><?=lang('quiz_settings')?></h1>
<table class="mainTable">
<tbody>
		<?php foreach ($settings as $k => $row) { ?>
			<tr class="<?= $k%2==1 ? "odd" : "even" ?>">
				<td style='width: 320px;'><label><?=$row['label']?><span><?=$row['description']?></span></label></td>
				<td><?=$row['content']?></td>
			</tr>
		<?php } ?>
</tbody>
</table>

<h1><?=lang('quiz_questions')?></h1>
<table class='mainTable'>
<tbody>
	<tr>
		<td colspan="2"><?=lang('quiz_add_question_desc')?></td>
	</tr>
	<tr class="odd">
		<td style="width: 50%; vertical-align: top;">
			<label>Questions In This Quiz</label>
			<ul id='question_list'>
				<?php foreach ($mappings as $k => $m) { ?>
					<li id='quiz_question_<?=$m['question_id']?>_<?=$m['mapping_id']?>' class='<?=$m['tags']?> <?=""/*($m['question_id'>=$recent_id_limit)?"recent":""*/?>'>
						<?=$m['title']?> (<?=$m['shortname']?>)
					</li>
				<?php } ?>
			</ul>
		</td>
		<td style="width: 50%; vertical-align: top;">
			<div class="unused_questions_filter" style="">
			Filter: <input id="tags_filter" type="text" value="" onkeyup="editQuiz.filterQuestions();" />
			<input class="remove_filter_btn" type="button" onclick='editQuiz.clearFilter();' style=""></div>
			</div>
			<label>Unused Questions</label>
			<ul id='unused_question_list'>
				<?php foreach ($unused_questions as $k => $uq) { ?>
					<li id='quiz_question_<?=$uq['question_id']?>_0' class="<?=$uq['tags']?> <?=$uq['question_id'] >= $recent_id_limit?"recent":""?> <?= !in_array($uq['question_id'], $used_by_other) ? "unused" : "" ?>">
						<?=$uq['title']?> (<?=$uq['question_shortname']?>)
					</li>
				<?php } ?>
			</ul>
		</td>
	</tr>
</tbody>
</table>

<?=$extra?>

<input type='submit' value='submit' />
