<?
    $this->table->set_template($cp_table_template);
    $this->table->set_heading(
        lang('select'),
        lang('storage_facility'), 
        lang('physical_location')
    );

    foreach($items as $entry) {
        $this->table->add_row(
                '<input type="checkbox" value="'.$entry['id'].'"></input>',
                $entry['facility_name'], 
                $entry['location']);
    }
    echo $this->table->generate();
?>