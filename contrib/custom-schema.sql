-- SQL custom schema by Matthieu PATOU <mat@matws.net>
--
-- Requests to set in config.inc.php:
--
-- $rcmail_config['vacation_sql_read'] =
--   array("SELECT startdate*86400 AS vacation_start, enddate*86400 AS vacation_end, " .
--           "subject AS vacation_subject, message AS vacation_message, " .
--           "active AS vacation_enable FROM vacation " .
--           "WHERE email=%email_local AND domain=%email_domain;"
--   );
--
-- $rcmail_config['vacation_sql_write'] =
--   array("DELETE FROM vacation WHERE email=%email_local AND " .
--           "domain=%email_domain;",
--         "INSERT INTO vacation (email,domain,startdate,enddate,subject,message," .
--           "created,active) " .
--           "VALUES (%email_local,%email_domain,%vacation_start/86400," .
--           "%vacation_end/86400,%vacation_subject,%vacation_message," .
--           "NOW(),1);"
--   );
--

CREATE TABLE vacation (
  email varchar(255) NOT NULL PRIMARY KEY,
  domain varchar(255) NOT NULL,
  startdate int NOT NULL,
  enddate int NOT NULL,
  subject varchar(255) NOT NULL,
  message text character NOT NULL,
  created datetime NOT NULL default '0000-00-00 00:00:00',
  active tinyint NOT NULL default '1'
);
