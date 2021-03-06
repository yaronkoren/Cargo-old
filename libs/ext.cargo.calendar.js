$(document).ready(function() {

	// page is now ready, initialize the calendar...
	$('.cargoCalendar').each( function() {
		var dataURL = decodeURI( $(this).attr('dataurl') );
		var startDate = moment( $(this).attr('startdate') );
		$(this).fullCalendar({
			// put your options and callbacks here
			events: dataURL,
			header: {
				left: 'today prev,next',
				center: 'title',
				right: 'month,basicWeek,basicDay'
			},
			defaultDate: startDate,
		});
	});

});
