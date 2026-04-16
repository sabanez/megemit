jQuery(document).ready(function ($) {
	// !Validate each form on the page
	$('.swpm-form-builder').each(function () {
		$(this).validate({
			rules: {
				"swpm-secret": {
					required: true,
					digits: true,
					maxlength: 2
				},
			},
			errorClass: 'swpm-form-builder-error',
			errorPlacement: function (error, element) {
				if (element.is(':radio') || element.is(':checkbox'))
					error.appendTo(element.parent().parent());
				else if (element.is(':password'))
					error.insertAfter(element);
				else
					error.insertAfter(element);
			},
			submitHandler: function (form) {
				var invalid = false;
				$('.swpm-checkbox-required').each(function () {
					var multicheck = $(this);
					if (multicheck.find('input:checked').length < 1) {
						invalid = true;
						if (multicheck.find('label.swpm-error').length === 0) {
							$('<label generated="true" class="swpm-error">' + swpmFbValidation.str.checkOne + '</label>')
								.appendTo(multicheck);
						}
					}
				});
				if (invalid) {
					return false;
				} else {
					form.submit();
					return true;
				}
			}
		});
	});

	// Force bullets to hide, but only if list-style-type isn't set
	$('.swpm-form-builder li:not(.swpm-item-instructions li, .swpm-span li)').filter(function () {
		return $(this).css('list-style-type') !== 'none';
	}).css('list-style', 'none');

	// !Display jQuery UI date picker
	$('.swpm-date-picker').each(function () {
		var swpm_dateFormat = $(this).attr('data-dp-dateFormat') ? $(this).attr('data-dp-dateFormat') : 'mm/dd/yy';

		$(this).datepicker({
			dateFormat: swpm_dateFormat,
			yearRange: "-100:+100",
			changeMonth: true,
			changeYear: true
		});
	});

	// !Custom validation method to check multiple emails
	$.validator.addMethod('phone', function (value, element) {
		// Strip out all spaces, periods, dashes, parentheses, and plus signs
		value = value.replace(/[\+\s\(\)\.\-\ ]/g, '');

		return this.optional(element) || value.length > 9 &&
			value.match(/^((\+)?[1-9]{1,2})?([-\s\.])?((\(\d{1,4}\))|\d{1,4})(([-\s\.])?[0-9]{1,12}){1,2}$/);

	}, $.validator.messages.phone
	);

	// !Custom validation method to check multiple emails
	$.validator.addMethod('nowhitespace', function (value, element) {
		// Strip out all spaces, periods, dashes, parentheses, and plus signs
		return value.indexOf(" ") < 0 && value !== "";
		;
	}, $.validator.messages.nowhitespace
	);
	$.validator.addMethod('strongPass', function (value, element) {
		return /^(?=.*[A-Z])(?=.*[a-z])(?=.*[0-9]).*$/g.test(value);
	}, $.validator.messages.strongPassReq);
	$.validator.addMethod('passmin8', function (value, element) {
		return value.length >= 8;
	}, $.validator.messages.minlength(8));
	$.validator.addMethod('strongPass-edit', function (value, element) {
		if (value.length === 0)
			return true;
		return /^(?=.*[A-Z])(?=.*[a-z])(?=.*[0-9]).*$/g.test(value);
	}, $.validator.messages.strongPassReq);
	$.validator.addMethod('passmin8-edit', function (value, element) {
		if (value.length === 0)
			return true;
		return value.length >= 8;
	}, $.validator.messages.minlength(8));
});