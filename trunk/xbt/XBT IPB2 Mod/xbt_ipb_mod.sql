alter table ibf_posts add bt_info_hash blob;
alter table ibf_posts add bt_name varchar(255);
alter table ibf_posts add bt_size bigint;
alter table ibf_posts add bt_tracker varchar(255);
alter table ibf_topics add bt_info_hash blob;
insert into ibf_attachments_type (atype_extension, atype_mimetype, atype_img) values ('torrent', 'application/x-bittorrent', 'folder_mime_types/zip.gif');
