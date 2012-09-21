<div name="equipment_types" class="equipment_manager">
    <form id="eq_type">
    <?
        $this->table->set_template($cp_table_template);
        $this->table->set_heading(
            lang('select'),
            lang('eq_type'),
            lang('eq_model'), 
            lang('eq_manufacturer'), 
            lang('eq_description'), 
            //lang('calibration_required_interval'), 
            //lang('calibration_form_default'), 
            //lang('audit_required_interval'), 
            //lang('audit_form_default'), 
            //lang('maintenance_required_interval'), 
            //lang('maintenance_form_default'), 
            //lang('maintenance_decay_rate')
            lang('activity_rate'),
            lang('activity_units')
        );

        // Generate the table
        foreach($items as $item) {

            $this->table->add_row(
                '<input type="checkbox" value="'.$item['id'].'"></input>',
                $item['type'],
                $item['model'],
                $item['manufacturer'],
                $item['description'],
                //$item['calibration_required_interval'],
                //$item['calibration_form_default'],
                //$item['audit_required_interval'],
                //$item['audit_form_default'],
                //$item['maintenance_required_interval'],
                //$item['maintenance_decay_rate'],
                $item['activity_rate'],
                $item['activity_units']
            );
        }
        echo $this->table->generate();
        $this->table->clear();
    ?>
    <div class="tableFooter">
        <div class="tableSubmit">
            <?=form_submit(array('name' => 'submit', 'value' => lang('submit'), 'class' => 'submit')).NBS.NBS.form_dropdown('action', $options)?>
        </div>

        <span class="js_hide"><?//=$pagination?></span>
        <span class="pagination" id="filter_pagination"></span>
    </div>
    </form>
    <button id="add_eq_button"><?=lang('add_equipment_type')?> </button>
    <?
    echo form_open($action_url,$form_attributes,$form_hidden);

    $tmpl = array (
        'table_open'          => '<table border="0" cellpadding="4" cellspacing="4">',

        'heading_row_start'   => '<tr>',
        'heading_row_end'     => '</tr>',
        'heading_cell_start'  => '<th>',
        'heading_cell_end'    => '</th>',

        'row_start'           => '<tr>',
        'row_end'             => '</tr>',
        'cell_start'          => '<td>',
        'cell_end'            => '</td>',

        'row_alt_start'       => '<tr>',
        'row_alt_end'         => '</tr>',
        'cell_alt_start'      => '<td>',
        'cell_alt_end'        => '</td>',

        'table_close'         => '</table>'
   );

    $this->table->set_template($tmpl);
    $this->table->set_heading(
        lang('eq_type'), 
        lang('eq_model'),
        lang('eq_manufacturer'),
        lang('eq_description'), 
        //lang('calibration_required_interval'), 
        //lang('calibration_form_default'), 
        //lang('audit_required_interval'), 
        //lang('audit_form_default'), 
        //lang('maintenance_required_interval'), 
        //lang('maintenance_form_default'),
        lang('activity_rate'),
        lang('activity_units')
    );
    $this->table->add_row(
        '<input type="text" name="type" />',
        '<input type="text" name="model" />',
        '<input type="text" name="manufacturer" />',
        '<input type="text" name="description" />',
        '<input type="text" name="activity_rate" />',
        '<input type="text" name="activity_units" />'
    );
    echo $this->table->generate();
    $this->table->clear();
    ?>
    <input type="submit"></button>
    <?=form_close(); ?>
    <div id="results"></div>
    <script type="text/javascript">
        $(document).ready(function(){
            //make sure the main table has a tbody, even if there are no rows
            if ($('table.mainTable').has('tbody').length == 0) {
                $('table.mainTable').append('<tbody></tbody>');
            }

            var success = "<p style=\"color: red;\">Successfully added to the database!</p>";
            var invalid_error = "<p style=\"color: red;\">The input is invalid. Only commas, dashes, periods, a-z, 0-9 may be entered.</p>";

            $('#add_eq_button').click(function() { $('#add_eq_type').fadeIn(2000); });

            $('#add_eq_type').keydown(function(event) { 
                if(event.which == 13) {
                    event.preventDefault(); 
                }
            });

            $('form#add_eq_type').submit(function(event) {
                event.preventDefault();
                var formVals = $(this).serializeArray();

                // Clear so only the most recent feedback is posted.
                $('.results').empty();
                $.ajax({
                    url: $(this).attr('action'),
                    type: 'post',
                    data: $(this).serialize(),
                    success: function() {
                        /*
                        $('table.mainTable > tbody:last').append('<tr>');
                        $('table.mainTable > tbody:last').append('<td><input type="checkbox" value=""></input></td>');
                        for (var i = 0; i < formVals.length; i++) {
                            if (formVals[i].name !== 'XID') {
                                $('table.mainTable > tbody:last').append('<td>' + formVals[i].value + '</td>');
                            }
                        }
                        $('table.mainTable > tbody:last').append('</tr>');
                        */
                        $(success).appendTo('#results');

                        window.location.reload();
                    },
                    error: function() {
                        $(invalid_error).appendTo('#results');
                    }
                });
            });
        });
    </script>
</div>  <!-- equipment_types -->