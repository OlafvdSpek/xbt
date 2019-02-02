-- create table if not exists xbt_announce_log
-- (
	-- id int not null auto_increment,
	-- ipa int unsigned not null,
	-- port int not null,
	-- event int not null,
	-- info_hash binary(20) not null,
	-- peer_id binary(20) not null,
	-- downloaded bigint unsigned not null,
	-- left0 bigint unsigned not null,
	-- uploaded bigint unsigned not null,
	-- uid int not null,
	-- mtime int not null,
	-- primary key (id)
-- ) engine = myisam;

create table if not exists xbt_config
(
	name varchar(255) not null,
	value varchar(255) not null
);

-- create table if not exists xbt_deny_from_clients
-- (
	-- peer_id char(20) not null
-- );

-- create table if not exists xbt_deny_from_hosts
-- (
	-- begin int unsigned not null,
	-- end int unsigned not null
-- );

create table if not exists xbt_files
(
	fid int not null auto_increment,
	info_hash binary(20) not null,
	leechers int not null default 0,
	seeders int not null default 0,
	completed int not null default 0,
	flags int not null default 0,
	mtime int not null,
	ctime int not null,
	primary key (fid),
	unique key (info_hash)
);

create table if not exists xbt_files_users
(
	fid int not null,
	uid int not null,
	active tinyint not null,
	completed int not null,
	downloaded bigint unsigned not null,
	`left` bigint unsigned not null,
	uploaded bigint unsigned not null,
	mtime int not null,
	unique key (fid, uid),
	key (uid)
);

-- create table if not exists xbt_scrape_log
-- (
	-- id int not null auto_increment,
	-- ipa int unsigned not null,
	-- uid int not null,
	-- mtime int not null,
	-- primary key (id)
-- ) engine = myisam;

create table if not exists xbt_users
(
	uid int not null auto_increment,
	-- can_leech tinyint not null default 1,
	-- wait_time int not null default 0,
	-- peers_limit int not null default 0,
	-- torrent_pass char(32) not null,
	torrent_pass_version int not null default 0,
	downloaded bigint unsigned not null default 0,
	uploaded bigint unsigned not null default 0,
	primary key (uid)
);

-- alter table xbt_files_users add down_rate int unsigned not null;
-- alter table xbt_files_users add up_rate int unsigned not null;

-- alter table xbt_files_users add foreign key (fid) references xbt_files (fid) on delete cascade;
-- alter table xbt_files_users add foreign key (uid) references xbt_users (uid) on delete cascade;
