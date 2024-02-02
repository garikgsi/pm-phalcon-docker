create table if not exists public.members_groups
(
  member_id bigint not null
    constraint members_groups_members_member_id_fk
      references members,
  group_id integer not null
    constraint members_groups_groups_group_id_fk
      references groups,
  constraint members_groups_pk
    primary key (member_id, group_id)
);
