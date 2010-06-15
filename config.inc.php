<?php

/*
 +-----------------------------------------------------------------------+
 | Configuration file for vacation module                                |
 |                                                                       |
 | Copyright (C) 2009 Boris HUISGEN <bhuisgen@hbis.fr>                   |
 | Licensed under the GNU GPL                                            |
 +-----------------------------------------------------------------------+
*/

$rcmail_config = array();

// allow vacation subject
$rcmail_config['vacation_gui_vacationsubject'] = TRUE;

// default vacation subject
$rcmail_config['vacation_gui_vacationsubject_default'] = "Out of office";

// allow HTML for vacation message 
$rcmail_config['vacation_gui_vacationmessage_html'] = TRUE;

// default vacation message
$rcmail_config['vacation_gui_vacationmessage_default'] = "I'm currently out of office.";

// allow vacation forwarder
$rcmail_config['vacation_gui_vacationforwarder'] = FALSE;

// driver used for backend storage
$rcmail_config['vacation_driver'] = 'sql';

/*
 * SQL driver
 */

// database DSN
$rcmail_config['vacation_sql_dsn'] =
	'mysql://user:password@sql.my.domain/vacation';

// read data queries
$rcmail_config['vacation_sql_read'] =
	array("SELECT subject AS vacation_subject, body AS vacation_message, " .
	          "active AS vacation_enable FROM vacation " .
	      "WHERE email=%username AND domain=%email_domain;"
	     );

// write data queries
$rcmail_config['vacation_sql_write'] =
	array("DELETE FROM vacation WHERE email=%email AND " .
	         "domain=%email_domain;",
          "DELETE from vacation_notification WHERE on_vacation=%email;",
          "DELETE FROM alias WHERE address=%email AND " .
	         "domain=%email_domain;",
	      "INSERT INTO vacation (email,domain,subject,body,created," .
	         "active) " .
	         "SELECT %email,%email_domain,%vacation_subject," .
	            "%vacation_message,NOW(),1 FROM mailbox " .
	         "WHERE username=%email AND domain=%email_domain AND " .
	            "%vacation_enable=1;",
          "INSERT INTO alias (address,goto,domain,created,modified," .
	         "active) " .
             "SELECT %email,CONCAT(%email_local,'#',%email_domain,'@'," .
                "'autoreply.my.domain'),%email_domain,NOW(),NOW(),1 " .
             "FROM mailbox WHERE username=%email AND " .
                "domain=%email_domain AND %vacation_enable=1;"
    );

/*
 * LDAP driver
 */

// Server hostname
$rcmail_config['vacation_ldap_host'] = '127.0.0.1';

// Server port
$rcmail_config['vacation_ldap_port'] = 389;

// Use TLS flag
$rcmail_config['vacation_ldap_starttls'] = false;

// Protocol version
$rcmail_config['vacation_ldap_version'] = 3;

// Base DN
$rcmail_config['vacation_ldap_basedn'] = 'dc=ldap,dc=my,dc=domain';

// Bind DN
$rcmail_config['vacation_ldap_binddn'] = 'cn=user,dc=ldap,dc=my,dc=domain';

// Bind password
$rcmail_config['vacation_ldap_bindpw'] = 'pa$$w0rd';

// Attribute name to map email address
$rcmail_config['vacation_ldap_attr_email'] = null;

// Attribute name to map email local part
$rcmail_config['vacation_ldap_attr_emaillocal'] = null;

// Attribute name to map email domain
$rcmail_config['vacation_ldap_attr_emaildomain'] = null;

// Attribute name to map vacation flag
$rcmail_config['vacation_ldap_attr_vacationenable'] = 'vacationActive';

// Attribute value for enabled vacation flag
$rcmail_config['vacation_ldap_attr_vacationenable_value_enabled'] = 'TRUE';

// Attribute value for disabled vacation flag
$rcmail_config['vacation_ldap_attr_vacationenable_value_disabled'] = 'FALSE';

// Attribute name to map vacation subject
$rcmail_config['vacation_ldap_attr_vacationsubject'] = null;

// Attribute name to map vacation message
$rcmail_config['vacation_ldap_attr_vacationmessage'] =
 'vacationInfo';

// Attribute name to map vacation forwarder
$rcmail_config['vacation_ldap_attr_vacationforwarder'] =
 'vacationForward';

// Search base to read data
$rcmail_config['vacation_ldap_search_base'] =
 'cn=%email_local,ou=Mailboxes,dc=%email_domain,ou=MailServer,dc=ldap,' .
 'dc=my,dc=domain';

// Search filter to read data
$rcmail_config['vacation_ldap_search_filter'] = '(objectClass=mailAccount)';

// Search attributes to read data
$rcmail_config['vacation_ldap_search_attrs'] = array ('vacationActive', 'vacationInfo');

// array of DN to use for modify operations required to write data.
$rcmail_config['vacation_ldap_modify_dns'] = array (
 'cn=%email_local,ou=Mailboxes,dc=%email_domain,ou=MailServer,dc=ldap,dc=my,dc=domain'
);

// array of operations required to write data.
$rcmail_config['vacation_ldap_modify_ops'] = array(
	array (
		'replace' => array(
			$rcmail_config['vacation_ldap_attr_vacationenable'] => '%vacation_enable',
 			$rcmail_config['vacation_ldap_attr_vacationmessage'] => '%vacation_message',
 			$rcmail_config['vacation_ldap_attr_vacationforwarder'] => '%vacation_forwarder'
 			)
 		)
);

// end vacation config file
?>
