<form id="facility_list form" class="equipment_manager">
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
<div class="tableFooter">
    <div class="tableSubmit">
        <?=form_submit(array('name' => 'submit', 'value' => lang('submit'), 'class' => 'submit')).NBS.NBS.form_dropdown('action', $options)?>
    </div>

    <span class="js_hide"><?=$pagination?></span>
    <span class="pagination" id="filter_pagination"></span>
</div>
</form>
<button id="new_facility"><?=lang('add_new_facility')?> </button>
    <?=form_open($action_url, $attributes, $form_hidden)?>
        <table>
            <tr><th><?=lang('new_facility')?></th><th><?=lang('physical_location')?></th></tr>
            <tr>
                <td><input type="text" name="facility_name" /></td>
                <td><input type="text" name="location" /></td>
            </tr>
        </table>
        <input type="submit" />
        <div class="results"> </div>
    <?=form_close()?><!--facility_list -->
<script type="text/javascript">
    $(document).ready(function(){
        var success = "<p style=\"color: red;\">Successfully added to the database!</p>";
        var invalid_error = "<p style=\"color: red;\">The input is invalid. Only commas, dashes, periods, a-z, 0-9 may be entered.</p>";
        var incomplete_error = "<p style=\"color: red;\">Failure: Please fill out both the \"Serial Number\" and \"Equipment model\" fields as completely as possible.</p>";

        $('#add_facility').keydown(function (e) { if(e.which == 13) event.preventDefault(); });

        $('#new_facility').click(function(){$('#add_facility').fadeIn(2000); });

        $('#add_facility').submit(function() {
            event.preventDefault();
            console.log(this);

            // Only the most recent feedback is posted.
            $('.results').empty();

            // Either are empty
            if($('#facility_name').val()== ''|| $('#location').val()=='') { 
                $(incomplete_error).appendTo('.results'); 
            }
            else {
                console.log($(this).serialize());
                $.ajax({
                    url: $(this).attr('action'),
                    type: 'POST',
                    data: $(this).serialize(),
                    success:function() { 
                        $(success).appendTo('.results');
                        location.reload();
                    },
                    error: function() {
                        $(invalid_error).appendTo('.results');
                    }
                });
            }
        });

        function isValid(str) {
            regex = /\w+\.*\,*\s*\-*/g;
            valid = regex.test(str);
            return valid;
        }
    });
</script>