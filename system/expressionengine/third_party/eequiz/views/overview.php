<div id="overview" class="eequiz">
<p><?=lang('overview_tip')?></p>
<p><?=lang('overview_tip2')?></p>
<?php
    $this->table->set_columns($headers);
    $data = $this->table->datasource('overview_datasource', $defaults);

    echo $data['table_html'];
    // echo $data['pagination_html'];
?>
</div>