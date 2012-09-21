<script type= "text/javascript">
$(document).ready(function() {
	var success = "<p style=\"color: red;\">Successfully updated in the database.</p>";
	var failure = "<p style=\"color: red;\">The input is invalid. Only commas, periods, a-z, 0-9 may be entered.</p>";
		
    $('.equipment_manager form').change(function() {
        $.post($(this).attr('action'), $(this).serialize(),  $(success).appendTo(this) );
    });
});
</script><!-- End AJAX -->