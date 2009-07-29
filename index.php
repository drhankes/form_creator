<?php

/**
 Based on the concepts of Niklas at vailo.wordpress.com
 The Form class should be used instead of adding form-html-tags in your PHP pages.
 With this class you can auto-generate forms from MySQL database tables and set default and custom validation rules and layout.
 You can also use the Form class without a MySQL database and create the form from scratch using the addField() method.
**/

Plugin::setInfos(array(
    'id'          => 'form_creator',
    'title'       => 'Form Creator', 
    'description' => 'Form wizard allows for dynamic form creation.', 
    'version'     => '0.0.1', 
    'license'     => 'AGPL',
    'author'      => 'David Hankes',
    'website'     => 'http://www.davidhankes.com/')
);

include_once('lib/Common.php');

class Form extends Page {

	private $_fields, $_method, $_action, $_id, $_db, $_table, $_validate = true;
	static private $numForms = 0;

	public function __construct($method, $action, $array=array()) 
	{		
		$this->_method = $method;
		$this->_action = $action;
		$this->_db = Common::ifKeyOr('db', $array, false);
		$this->_table = Common::ifKeyOr('table', $array, false);

		if (!array_key_exists('id', $array)) {
			Form::$numForms++;
			$this->_id = 'form_'.Form::$numForms."";
		} else {
			$this->_id = $array['id'];
		}
		
		$_SESSION[$this->_id] = $this;
	}

	public function addField($name, $type, $attributes=array('format'=>'string', 'required'=>false, 'min'=>false, 'max'=>false)) 
	{
		$this->_fields[$name]['name'] = $name;
		$this->_fields[$name]['type'] = $type;
		$this->_fields[$name]['value'] = Common::ifKeyOr('value', $attributes, false);
		$attributes['label'] = Common::ifKeyOr('label', $attributes, $name);
		$attributes['showLabel'] = Common::ifKeyOr('showLabel', $attributes, true);
		
		if ($type == 'file') {
			$this->_method = 'post';
		} else if ($type == 'radio' || $type == 'checkbox') {
			$attributes['format'] = false;
		}
		if(array_key_exists('snippet', $attributes)) {
			foreach($attributes['snippet'] as $key => $value) {
				$attributes['snippet'][$key] = htmlspecialchars($attributes['snippet'][$key]);
			}
		}
		$this->_fields[$name]['attributes'] = $attributes;
	}

	public function getField($field) 
	{
		if(array_key_exists($field, $this->_fields)) {
			return $this->_fields[$field];
		}
		return false;
	}

	public function setField($key, $field, $value) 
	{
		if(array_key_exists($key, $this->_fields)) {
			$this->_fields[$key][$field] = $value;
		}
	}

	public function setAttribute($key, $field, $attribute, $value) 
	{
		if(array_key_exists($key, $this->_fields)) {
			$this->_fields[$key][$field][$attribute] = $value;
		}
	}

	public function removeField($key) 
	{
		if(array_key_exists($key, $this->_fields)) {
			unset($this->_fields[$key]);
		}
	}

	public function generate($attributes=array('required'=>false, 'format'=>'string', 'min'=>false, 'max'=>false, 'showLabel'=>true, 'values'=>null)) 
	{
		if ($this->_db && $this->_table) {
			$result = $this->_db->fetchQuery("SELECT * FROM $this->_table");
			$columns = $this->_db->fetchQuery("SHOW COLUMNS FROM $this->_table");
			$date_types = array('datetime', 'date', 'time', 'timestamp');
			$n = mysql_num_fields($result);
			
			for ($i = 0; $i < $n; $i++) {
				$fieldAttributes = $attributes;
				$tupleAttributes = mysql_fetch_field($result, $i);
				if(!$tupleAttributes->primary_key) {
					$tupleFlags = mysql_result($columns, $i, 1);
					$value = mysql_result($columns, $i,4);
					$name = $tupleAttributes->name;
					$type = (strtolower($name) == 'password') ? 'password' : 'text';
					$label = $name;
					$fieldAttributes['max'] = mysql_field_len($result, $i);
					$fieldAttributes['format'] = (strtolower($name) == 'email') ? 'email' : 'string';
					if(!is_null($attributes['values'])) {
						$value = $attributes['values'][$i];
					}
					if ($tupleAttributes->numeric) {
						$fieldAttributes['format'] = 'number';
						$fieldAttributes['max'] = false;
					} else if($tupleAttributes->blob) {
						if($tupleFlags == 'text') {
							$fieldAttributes['format'] = 'text';
							$type = 'textarea';
						} else {
							$type = 'file';
							if(!empty($value)) {
								$fieldAttributes['exist'] = $value;
								$label .= ' (exists)';
							}
						}
					} else if(strtolower(substr($tupleFlags,0,4)) == 'enum') {
						$type = 'radio';
						foreach(explode("','",substr($tupleFlags,6,-2)) as $v) {
							$array[$v] = $v;
						}
						$value = $array;
					}
					if(in_array($tupleAttributes->type, $date_types)) {
						$fieldAttributes['format'] = 'date';
					}
					if($tupleAttributes->not_null) {
						$fieldAttributes['required'] = true;
						$label .= ' *';
					}
					$fieldAttributes['label'] = $label;
					$fieldAttributes['value'] = $value;
					$this->addField($name, $type, $fieldAttributes);
				}
			}
		} else {
			trigger_error('Method [' . __FUNCTION__ . '] failed [R: A database session and a table must be added.]', WARNING);
		}
	}

	private function createField($field) 
	{	
		$fieldClass = !is_null($field['attributes']['fieldClass']) ? $field['attributes']['fieldClass'] : 'formooField';
		$boxClass = !is_null($field['attributes']['boxClass']) ? $field['attributes']['boxClass'] : '';
		$value = !is_null($field['attributes']['value']) ? $field['attributes']['value'] : '';
		$snippet = !is_null($field['attributes']['snippet']) ? $field['attributes']['snippet'] : '';
		$type = $field['type'];
		$name = $field['name'];
		$id = $this->_id . '_' . $name;
		
		if ($type != 'hidden') {
			$str = htmlspecialchars_decode($snippet['before']);
			$str .= $boxClass ? '<div class='.$boxClass.'>' : '<div>';
			$str .= htmlspecialchars_decode($snippet['front']);
			if($field['attributes']['showLabel'] && $type != 'submit' && $type != 'reset') {
				$str .= $this->createLabel($id, $field);
			}
		}
		if ($type == 'text' || $type == 'password' || $type == 'hidden') {
			$str .= '<input type="'.$type.'" class="'.$fieldClass.'" id="'.$id.'" name="'.$name.'" value="'.$value.'" />';
		} else if ($type == 'file') {
			$str .= '<input type="'.$type.'" class="'.$fieldClass.'" id="'.$id.'" name="'.$name.'" />';
		} else if ($type == 'radio' || $type == 'checkbox') {
				foreach($value as $key => $val) {
					$checked = '';
					if ($val) { $checked = 'checked="checked"'; }					
					$uid = "$this->_id.".strtolower($key)."";
					$str .= '<input type="'.$type.'" class="'.$fieldClass.'" id="'.$id.'" name="'.$name.'" value="'.$key.'" '.$checked.'/>';
					$str .= '<label for="'.$id.'">'.$key.'</label>';
					if($val != end($value)) {
						$str .= '<br/>';
					}
				}
		} else if ($field['type'] == 'textarea') {
			$str .= '<textarea class="'.$fieldClass.'" id="'.$id.'" name="'.$name.'">'.$value.'</textarea>';
		} else if ($field['type'] == 'select') {
			$str .= '<select id="'.$id.'" name="'.$name.'" class="'.$fieldClass.'">';
			foreach($value as $key => $val) {
				if(!$val) { $val = $key;}							
				if($val == $field['attributes']['selected']) {
					$str .= '<option value="'.$key.'" selected="true">'.$val.'</option>';
				} else {
					$str .= '<option value="'.$key.'">'.$val.'</option>';
				}
			}	
			$str .= '</select>';
		} else if ($type == 'submit') {
			$str .= '<input type="submit" class="'.$fieldClass.'" id="'.$this->_id.'" value="'.$value.'"/>';
		} else if ($type == 'reset') {
			$str .= '<input type="reset" class="'.$fieldClass.'" id="'.$this->_id.'" value="'.$value.'"/>';
		}
		if ($field['type'] != 'hidden') {
			$str .= htmlspecialchars_decode($snippet['back']);
			$str .= '<span class="formooPatrol"></span>';
			$str .= '</div>';
		}
		$str .= htmlspecialchars_decode($snippet['after']);
		return $str;
	}

	private function createLabel($for, $field) 
	{
		$field = array_key_exists('label', $field['attributes']) ? $field['attributes']['label'] : $field['name'];
		return "<label for='".$for."' class='label'>".ucwords($field)."</label><br/>";
	}

	public function create() 
	{
		if(!empty($this->_fields)) {
			$enctype = ($this->_method == 'post') ? 'enctype="multipart/form-data"' : '';
			$str = "<form id=\"$this->_id\" class=\"formoo\"  action=\"$this->_action\" method=\"".strtolower($this->_method)."\" $enctype>";
			foreach($this->_fields as $field) {
				$str .= "\n" . $this->createField($field);
			}
			$str .= '</form>';
			$str .= "<div id=\"log_$this->_id\" class='log'></div>";
			return $str;
		}
	}

	public function view() 
	{
		$str = "<p><pre>$this->_id for $this->_table<br/>";
		if(!empty($this->_fields)) {
			foreach($this->_fields as $field) {
				$str .= 'name => '.$field['name'].' :: type => '.$field['type'].' :: attributes => ';
				foreach($field['attributes'] as $key => $value) {
					if (!$value) { $value = 0; }
						$str .= "$key: $value, ";
				}
			}
		} else {
			trigger_error('Method [' . __FUNCTION__ . '] failed [R: No fields added]', WARNING);
		}
		$str .= '</pre></p>';
		echo $str;
	}
}
?>