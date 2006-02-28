alter table xbt_files add mtime0 int not null, add ctime0 int not null;
update xbt_files set mtime0 = unix_timestamp(mtime), ctime0 = unix_timestamp(ctime);
alter table xbt_files drop mtime, drop ctime, change mtime0 mtime int not null, change ctime0 ctime int not null;
alter table xbt_files_users add mtime0 int not null;
update xbt_files_users set mtime0 = unix_timestamp(mtime);
alter table xbt_files_users drop mtime, change mtime0 mtime int not null;
