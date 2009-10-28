/* Vacation interface */

if (window.rcmail) {
	rcmail
			.addEventListener(
					'init',
					function(evt) {
						var tab = $('<span>').attr('id',
								'settingstabpluginvacation')
								.addClass('tablink');

						var button = $('<a>')
								.attr(
										'href',
										rcmail.env.comm_path + '&_action=plugin.vacation')
								.html(rcmail.gettext('vacation', 'vacation'))
								.appendTo(tab);
						button.bind('click', function(e) {
							return rcmail.command('plugin.vacation', this);
						});

						// add button and register commands
						rcmail.add_element(tab, 'tabs');
						rcmail.register_command('plugin.vacation', function() {
							rcmail.goto_url('plugin.vacation')
						}, true);
						rcmail
								.register_command(
										'plugin.vacation-save',
										function() {

											var input_vacationsubject = rcube_find_object('_vacationsubject');

											if (input_vacationsubject
													&& input_vacationsubject.value == '') {
												alert(rcmail.gettext(
														'vacationnosubject',
														'vacation'));
												input_vacationsubject.focus();
											} else {
												rcmail.gui_objects.vacationform
														.submit();
											}

										}, true);
					})
}
