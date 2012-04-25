jQuery(document).ready(function() {
	var dates = jQuery( "#Start_date, #End_date" ).datepicker({
		defaultDate: "+1w",
		dateFormat: "dd/mm/yy",
		changeMonth: true,
		numberOfMonths: 3,
		onSelect: function( selectedDate ) {
			var option = this.id == "Start_date" ? "minDate" : "maxDate",
				instance = jQuery( this ).data( "datepicker" ),
				date = jQuery.datepicker.parseDate(
					instance.settings.dateFormat ||
					jQuery.datepicker._defaults.dateFormat,
					selectedDate, instance.settings );
			dates.not( this ).datepicker( "option", option, date );
		}
	});

	
	jQuery('#create-event').click(function(){
		jQuery("#event-form").show('fast');
		jQuery("#create-event").hide('fast');
	});

});