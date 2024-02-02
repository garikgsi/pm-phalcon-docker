create table if not exists groups
(
  group_id serial not null
    constraint groups_pkey
      primary key,
  group_name varchar64,
  group_parent_id integer
    constraint groups_groups_group_id_fk
      references public.groups,
  group_system integer default 0 not null,
  group_leader_id bigint
    /*constraint groups_members_member_id_fk
      references public.members*/,
  group_assignable integer default 1 not null,
  group_primary_only integer default 0 not null
);

;/*--;--*/
