$(function(){
	$('#realm').val($.store.get('realm'));
	$('#username').val($.store.get('username'));
	$('#password').val($.store.get('password'));
	$('#save').attr('checked', !!$.store.get('password'));
	
	// handle remembering values before submitting the form
	$('form').submit(function() {
		$.store.set('realm', $('#realm').val());
		$.store.set('username', $('#username').val());
		if ($('#save').attr('checked')) {
			$.store.set('password', $('#password').val());
		} else  {
			$.store.remove('password');
		}
	});
});
