CREATE TABLE xbt_config
(
  name varchar(255) NOT NULL,
  value varchar(255),
  PRIMARY KEY  (name)
);

CREATE TABLE xbt_files
(
  fid int NOT NULL auto_increment,
  info_hash blob NOT NULL,
  leechers int NOT NULL,
  seeders int NOT NULL,
  completed int NOT NULL,
  started int NOT NULL,
  stopped int NOT NULL,
  mtime timestamp NOT NULL,
  ctime timestamp NOT NULL,
  PRIMARY KEY (fid),
  UNIQUE KEY (info_hash(20))
);
