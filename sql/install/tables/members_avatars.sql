create table if not exists members_avatars
(
  member_id bigint not null
    constraint members_avatars_pkey
    primary key
    constraint members_avatars_member_id_fk
    references members,
  data_image json not null,
  data_canvas json not null,
  data_crop json not null
)
;
/*--;--*/

