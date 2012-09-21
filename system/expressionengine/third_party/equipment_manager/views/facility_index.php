<form id="facility_list form" class="equipment_manager">
<div id="table">
    <?
    $this->table->set_template($cp_table_template);
    $this->table->set_heading(
        lang('select'),
        lang('storage_facility'), 
        lang('physical_location'));

    foreach($items as $entry) {
        $this->table->add_row(
                '<input type="checkbox" value="'.$entry['id'].'"></input>',
                $entry['facility_name'], 
                $entry['location']);
    }
    echo $this->table->generate();
    ?>
</div>
<div class="tableFooter">
    <div class="tableSubmit">
        <?=form_submit(array('name' => 'submit', 'value' => lang('submit'), 'class' => 'submit')).NBS.NBS.form_dropdown('action', $options)?>
    </div>

    <span class="js_hide"><?//$pagination?></span>
    <span class="pagination" id="filter_pagination"></span>
</div>
</form>
