/*
 +-----------------------------------------------------------------------+
 | Vacation Module for RoundCube                                         |
 |                                                                       |
 | Copyright (C) 2009 Boris HUISGEN <bhuisgen@hbis.fr>                   |
 | Licensed under the GNU GPL                                            |
 +-----------------------------------------------------------------------+
 */

if (window.rcmail) {
	rcmail.addEventListener('init', function(evt) {
		var tab = $('<span>').attr('id', 'settingstabpluginvacation').addClass(
				'tablink');
		var button = $('<a>').attr('href',
				rcmail.env.comm_path + '&_action=plugin.vacation').html(
				rcmail.gettext('vacation', 'vacation')).appendTo(tab);
		button.bind('click', function(e) {
			return rcmail.command('plugin.vacation', this);
		});
		rcmail.add_element(tab, 'tabs');
		rcmail.register_command('plugin.vacation', function() {
			rcmail.goto_url('plugin.vacation')
		}, true);
		rcmail.register_command('plugin.vacation-save', function() {
			rcmail.gui_objects.vacationform.submit();
		}, true);
	})
}
