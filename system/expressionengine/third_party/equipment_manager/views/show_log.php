<?php if (count($items) > 0): ?>
<?=form_open($action_url, '', $form_hidden)?>


<?
    //Headers for display() table
    $this->table->set_template($cp_table_template);
    $this->table->set_heading(
        lang('eq_serialnum'),
        lang('action'),
        "Logging ".lang('screen_name'),
        "On date");

    foreach($items as $entry) {
        $this->table->add_row(
                $entry['eq_serialnum'],
                $entry['action'],
                $entry['member_screen_name'],
                $entry['action_timestamp']      
            );
    }
    echo $this->table->generate();

?>

<div class="tableFooter">
    <div class="tableSubmit">
        <?=form_submit(array('name' => 'submit', 'value' => lang('submit'), 'class' => 'submit')).NBS.NBS.form_dropdown('action', $options)?>
    </div>

    <span class="js_hide"><?=$pagination?></span>
    <span class="pagination" id="filter_pagination"></span>
</div>

<?=form_close()?>

<?php else: ?>
<?=lang('no_matching_results')?>
<?php endif; ?>