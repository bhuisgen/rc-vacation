-- SQL custom table sample

CREATE TABLE vacation (
  email varchar(255) NOT NULL PRIMARY KEY,
  domain varchar(255) NOT NULL,
  startdate int NOT NULL,
  enddate int NOT NULL,
  subject varchar(255) NOT NULL,
  message text character NOT NULL,
  forward text character NOT NULL,
  created datetime NOT NULL default '0000-00-00 00:00:00',
  active tinyint NOT NULL default '1'
);
