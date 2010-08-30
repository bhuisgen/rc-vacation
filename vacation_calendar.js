/*
 +-----------------------------------------------------------------------+
 | Vacation Module for RoundCube                                         |
 |                                                                       |
 | Copyright (C) 2009 Boris HUISGEN <bhuisgen@hbis.fr>                   |
 | Licensed under the GNU GPL                                            |
 +-----------------------------------------------------------------------+
 */

$(function() {
	$('#vacationstart').datepicker( {
		dateFormat : calendar_format
	});
	$('#vacationend').datepicker( {
		dateFormat : calendar_format
	});
});