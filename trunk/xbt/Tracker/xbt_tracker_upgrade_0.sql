alter table xbt_files
	drop started,
	drop stopped,
	drop announced_http,
	drop announced_http_compact,
	drop announced_http_no_peer_id,
	drop announced_udp,
	drop scraped_http,
	drop scraped_udp;
alter table xbt_files_users add fid int not null;
update xbt_files f inner join xbt_files_users fu using (info_hash) set fu.fid = f.fid;
delete from xbt_files_users where fid = 0;
alter table xbt_files_users drop info_hash, drop index info_hash, add unique (fid, uid);
