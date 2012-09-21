<pre><?//=print_r($items)?></pre>
<div id="equipment_list" class="equipment_manager">
<?php if (count($items) > 0):
    // Creates a select tag with a list of all equipment statuses
    function generate_status_code_select($item, $status_code) {
        $val = form_open($status_code['action_url'], $status_code['attributes'], array('eq_id' => $item['id']));
        $val.= '<select name="status">'; 

        for ($i=1; $i<7; $i++) { // i < 7 because there are 6 status codes
            $val.= '<option value="'.$i.'"';
            if($item['status_id'] == $i) {
                $val.= ' selected="selected"';
            }
            $val.= '>'.lang("status_code_$i").'</option>';
        }      

        $val.= '</select>';
        $val.= form_close();
        return $val;
    }

    // Creates select element filled with a list of all members
    function generate_member_select($item, $members, $assigned_member) {
        $val = form_open($assigned_member['action_url'], $assigned_member['attributes'], array('eq_id' => $item['id']));
        $val.= '<select name="assigned_member">';
        $val.= '<option value="NULL">No User</option>';

        foreach ($members as $entry) {
            $val.= '<option value="'.$entry['member_id'].'"';
            if( $item['assigned_member_id'] == $entry['member_id'] ) {
                $val.= ' selected="selected"';
            }
            $val.= '>'.$entry['screen_name'].'</option>';
        }
        
        $val.= '</select>';
        $val.= form_close();

        return $val;
    }

    // creates a select element with a list of all facilities
    function generate_facility_select($item, $location, $storage_facility) {
        $val = form_open($storage_facility['action_url'], $storage_facility['attributes'], array('eq_id' => $item['id']));
        $val.= '<select name="facility_id">'; 
       
        foreach ($location as $entry) {
            $val.= '<option value="'.$entry['id'].'"';
            if ($item['assigned_facility_name'] == $entry['facility_name']) { 
                $val.= ' selected="selected"';
            }
            $val.= '>'.$entry['facility_name'].'</option>';
        }
            
        $val.= '</select>';
        $val.= form_close();

        return $val;
    }

    // lists all eq with parent_id = current eq's id
    function list_children($items, $id, $edit_link) {
        $val = '';
        foreach($items as $item) {
            if ($item['parent_id'] == $id) {
                $val.= '<a href="'.$edit_link.$item['id'].'">'.$item['serialnum'].'</a>';
            }
        }
        // If there are no children
        if (empty($val)) { $val = 'N/A'; }
        return $val;
    }

    
    $this->table->set_template($cp_table_template);
    $this->table->set_heading(
        lang('eq_serialnum'),
        lang('eq_model'),
        lang('eq_child'),
        lang('status_code'),
        lang('assigned_member'),
        lang('storage_facility'),
        lang('activity')
    );

    // Generate the table
    foreach ($items as $item) {
        if($item['parent_id'] == 0) {
            $this->table->add_row(
                '<a href="'.$edit_link.$item['id'].'">'.$item['serialnum'].'</a>',
                $item['description'],
                list_children($items, $item['id'], $edit_link),
                generate_status_code_select($item, $status_code),
                generate_member_select($item, $members, $assigned_member),
                generate_facility_select($item, $location, $storage_facility),
                $item['current_activity'].' '.$item['activity_units']
            );
        }

    }

    echo $this->table->generate();
?>
    <div class="tableFooter">
        <span class="js_hide"><?//=$pagination?></span>
        <span class="pagination" id="filter_pagination"></span>
    </div>
<?php else: ?>
<?=lang('no_matching_results')?> <br /> <br />
<?php endif; ?>
    <a href="<?echo $add_item_link; ?>"><button><?=lang('add_equipment')?></button></a>
</div> <!-- equipment_list -->
<? $this->load->view('javascript/ajax.php'); ?>