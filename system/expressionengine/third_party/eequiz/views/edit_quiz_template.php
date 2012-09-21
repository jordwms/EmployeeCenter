
<h1><?=lang('template_information')?></h1>

<table class="mainTable">
<tbody>
	<tr class="odd">
		<td style='width: 320px;'><label><?=lang('quiz_template_title_lbl')?><span><?=lang('quiz_template_title_desc')?></span></label></td>
		<td><input class='v_required text_input_long' type='text' name='title' id='title' value='<?=$title?>' /></td>
	</tr>
</tbody>
</table>


<h1><?=lang('template_settings')?></h1>
<table class="mainTable"><tbody>
	<tr class="odd">
		<td><div><textarea class='quiz_template' name='template'><?=$template?></textarea></div></td>
	</tr>
</tbody>
</table>


<h1><?=lang('template_reference')?></h1>
<table class="mainTable"><tbody>
	<tr class="odd">
		<td class="template_reference"><?=$reference?></td>
	</tr>
</tbody></table>


<input type='submit' value='submit' />