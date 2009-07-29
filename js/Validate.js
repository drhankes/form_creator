$(document).ready(function() {
	
	$('form.formoo').submit(function() {
		var id = $(this).attr('id');
		var form = $('form#'+id);
		var formLog = $('div#log_'+form.attr('id'));
		var formValid = true;
		formLog.html('');						
		$(':input', form).each(function(i) {
			var type = this.type;
			var value = this.value;
			var name = this.name;
			var object = $(this);
			if(type != 'hidden' && type != 'submit' && type != 'radio') {
				if(type == 'checkbox') {
					value = $('#' + form.attr('id') + ' :checkbox[name=' + name + ']:checked').size();
				}
				var data = $.ajax({url: 'frog/plugins/form_creator/lib/Validate.php?form=' + form.attr('id') + '&field=' + name + '&value=' + value, async: false, type: 'GET'}).responseText;					 
				if(data) {
					object.attr('class', 'formooFieldInvalid');
					object.parent().find('span.formooPatrol').html(data);
					formValid  = false;
				} else {
					object.attr('class', 'formooFieldValid');
					object.parent().find('span.formooPatrol').html('');
				}
			}
		});
		return formValid;
		this.blur();
	});
	
})