create table xbt_hashes
(
  hid int not null auto_increment,
  hash blob not null,
  primary key (hid)
);
insert ignore into xbt_hashes (hash) select info_hash from xbt_files;
insert ignore into xbt_hashes (hash) select info_hash from xbt_files_users;
alter table xbt_files add hid int not null;
alter table xbt_files_users add hid int not null;
update xbt_files f inner join xbt_hashes h on info_hash = hash set f.hid = h.hid;
update xbt_files_users fu inner join xbt_hashes h on info_hash = hash set fu.hid = h.hid;
alter table xbt_files drop index info_hash, drop info_hash, add unique (hid);
alter table xbt_files_users drop info_hash, add index (hid, uid);