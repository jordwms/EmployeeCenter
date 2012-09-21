<button id="new_facility"><?=lang('add_new_facility')?> </button>
    <?=form_open($action_url, $attributes, $form_hidden)?>
        <table>
            <tr><th>New Facility</th><th>Physical Location</th></tr>
            <tr>
                <td><input type="text" id="facility_name" /></td>
                <td><input type="text" id="location" /></td>
            </tr>
        </table>
        <button>Submit</button>
        <div class="results"> </div>
    <?=form_close()?><!--facility_list -->
