alter table ibf_posts add bt_info_hash blob;
alter table ibf_posts add bt_size bigint;
alter table ibf_posts add bt_tracker varchar(255);
alter table ibf_topics add bt_info_hash blob;