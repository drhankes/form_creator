This needs to be a snippet to use anywhere:

<script language="javascript" src="frog/plugins/form_creator/js/jquery.js"></script>
<script language="javascript" src="frog/plugins/form_creator/js/Validate.js"></script>


Here is a sample php script.

<?php 

$form = new Form('post', BASE_URL.'forms/test');
	$form->addField('username', 'text', array('required'=>true, 'format'=>'string'));
	$form->addField('testing', 'radio', array('required'=>true, 'max'=>2, 'min'=>1, 'value'=>array('Yes'=>false, 'No'=>false)));
	$form->addField('password', 'password', array('required'=>true, 'format'=>'letter'));
	$form->addField('options', 'checkbox', array('required'=>true, 'max'=>2, 'min'=>1, 'value'=>array('Yes'=>false, 'No'=>false)));
	$form->addField('submit', 'submit', array('value'=>'Submit'));
	$form->addField('reset', 'reset', array('value'=>'Reset'));
	echo $form->create();
?>