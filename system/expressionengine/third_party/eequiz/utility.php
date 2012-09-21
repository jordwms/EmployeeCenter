<?php

if (!defined('UTILITY_INCLUDED')) {

	define('UTILITY_INCLUDED', 'x');

	function special_serialize($var) {

		return base64_encode(serialize($var));
	}

	function special_unserialize($var) {
		
		return stripslashes_deep(unserialize(base64_decode($var)));
	}
	
	function stripslashes_deep($value) {
		
		$value = is_array($value) ?
			array_map('stripslashes_deep', $value) :
			stripslashes($value);
		
		return $value;
	}

	function escape_inner_html($string) {

		return htmlspecialchars($string, ENT_QUOTES);
	}

	function escape_inner_js($string) {

		$string = addslashes($string);
		$string = htmlspecialchars($string, ENT_QUOTES);
		
		return $string;
	}

	function inline_js($js) {
		
		return "\n<script type='text/javascript'>\n//<![CDATA[\n{$js}\n//]]>\n</script>\n";
	}

	
	
	function html_attributes($vars) {
		
		$string = "";
	
		if (!is_array($vars)) return $string;
		
		foreach ($vars as $k => $v) {
			
			$string .= "{$k}='{$v}' ";
		}
		
		return $string;
	}
	
	function html_hidden_inputs($vars) {
		
		$string = "";
		
		if (!is_array($vars)) return $string;
		
		foreach ($vars as $k => $v) {
			
			$cleaned = $v;
			if ($cleaned != '') {
				$cleaned = htmlspecialchars($cleaned);
				$cleaned = str_replace("'", "&#39;", $cleaned);
			}
			
			$string .= "<input type='hidden' name='{$k}' value='{$cleaned}' />\n";
		}
		
		return $string;
	}
	
}

?>
