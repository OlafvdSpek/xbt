CREATE TABLE xbt_announce_log
(
  id int NOT NULL auto_increment,
  ipa int NOT NULL,
  port int NOT NULL,
  event int NOT NULL,
  info_hash blob NOT NULL,
  peer_id blob NOT NULL,
  downloaded bigint NOT NULL,
  left0 bigint NOT NULL,
  uploaded bigint NOT NULL,
  mtime int NOT NULL,
  PRIMARY KEY (id)
);

CREATE TABLE xbt_config
(
  name varchar(255) NOT NULL,
  value varchar(255) NOT NULL,
);

CREATE TABLE xbt_files
(
  fid int NOT NULL auto_increment,
  info_hash blob NOT NULL,
  leechers int NOT NULL,
  seeders int NOT NULL,
  announced int NOT NULL,
  scraped int NOT NULL,
  completed int NOT NULL,
  started int NOT NULL,
  stopped int NOT NULL,
  mtime timestamp NOT NULL,
  ctime timestamp NOT NULL,
  PRIMARY KEY (fid),
  UNIQUE KEY (info_hash(20))
);

CREATE TABLE xbt_scrape_log
(
  id int NOT NULL auto_increment,
  ipa int NOT NULL,
  info_hash blob,
  mtime int NOT NULL,
  PRIMARY KEY (id)
);

CREATE TABLE xbt_users
(
  name char(8) NOT NULL,
  pass blob NOT NULL,
);
