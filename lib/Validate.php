<?php
	function validateField($value, $field) 
	{
		$attributes = $field['attributes'];
		$type = $field['type'];
		$name = $field['name'];
									
		if(array_key_exists('required', $attributes) && $attributes['required'] == true) {
			if((strlen($value) == 0 && !$attributes['exist']) || ($value == 0 && $type == 'checkbox')) {
				$result .= "".ucwords($name)." is required. ";
			}
		}
		
		if ($attributes['format'] && $result == '' && strlen($value) != 0) {
			$result .= validateValue($value, $attributes, $name);	
		}
		
		if(($type == 'checkbox' || $type ='select') && $result == '') {
			$min = $attributes['min'];
			$max = $attributes['max'];
			if(!empty($min) && $value < $min) {
				$result .= "".ucwords($name)." requires $min selections.";
			} else if(!empty($max) && $value > $max) {
				$result .= "".ucwords($name)." only $max selections allowed.";
			}
		}
		
		if($result) {
			return $result;
		}	
		return false;
	}

	function validateValue($value, $attributes, $name)
	{
		$format = $attributes['format'];
		$min = $attributes['min'];
		$max = $attributes['max'];
		$result = '';

		switch($format) {
			case 'letter':
				$result .= checkValue($value, "(^[a-zA-Z≈Âƒ‰÷ˆ]*$)", $name, $min, $max, 'char');
				break;
			case 'number':
				$result .= checkValue($value, "(^[\d]*$)", $name, $min, $max, 'int');
				break;
			case 'date':
				$result .= checkValue($value, "(^[\d:\-\s]*$)", $name, $min, $max, 'char');
				break;
			case 'string':
				$result .= checkValue($value, "(^[\w\d\s\.\?\'\"\-\+,:≈Âƒ‰÷ˆ\(\)\/]*$)", $name, $min, $max, 'char');
				break;
			case 'email':
				if(!filter_var($value, FILTER_VALIDATE_EMAIL)) {
					$result .= "".ucwords($name)." format not valid. ";
				}
				break;
			case 'text':
				$result .= checkValue($value, "(^[\w\d\s\.\?\'\"@\-\+,:≈Âƒ‰÷ˆ\(\)%]*$)", $name, $min, $max, 'char');
				break;
			case 'file':
				if(array_key_exists('folder', $attributes) && !is_dir($attributes['folder'])) {
					$result .= "".ucwords($name).": ".$attributes['folder']." isn't a valid folder. "; 
				}
				
				if(file_exists($value) && is_file($value)) {
					$file = pathinfo($value);
					$file_size = filesize($value);
					$file_extension = $file['extension'];
					if(array_key_exists('size', $attributes) && $attributes['size'] < $file_size) {
						$result .= "".ucwords($name)." file size is too large. ";
					}
					if(array_key_exists('extension', $attributes) && !in_array($file_extension, $attributes['extension'])) {
						$result .= "".ucwords($name)." hasn't a valid extension. ";
					}
				} else {
					$result .= "".ucwords($name)." is not a valid file. ";
				}
				break;
			default:
				break;
		}
		return $result;
	}

	function checkValue($value, $regexp, $name, $min, $max, $type) 
	{
		$result = '';
		if (!preg_match($regexp, utf8_decode($value), $match)) {
			$result .= "".ucwords($name)." format not valid. ";
		} else {
			if($type == 'int') { $length = $value; }
			else { $length = strlen($value); }
			if (($min) && ($length < $min)) {
				$result .= "".ucwords($name)." must be longer than $min. ";
			}
			if (($max) && ($length > $max)) {
				$result .= "".ucwords($name)." cannot be longer than $max. ";
			}	
		}
		return $result;
	}

	if (isset($_GET['form']) && isset($_GET['field']) && isset($_GET['value'])) {
		$form = $_SESSION[$_GET['form']];
		$field = $_GET['field'];
		$value = $_GET['value'];
		if ($form) {
			$field = $form->getField($field);
			if ($field) {
				
				echo validateField($value, $field);			
			}
		}
	}
	else {
		trigger_error('Method [Validation] failed [R: Form, field or/and value are not set]', WARNING);
	}
?>
