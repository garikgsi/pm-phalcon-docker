create table if not exists public.members_rights
(
  member_id bigint not null
    constraint members_rights_members_member_id_fk
      references members,
  right_id integer not null
    constraint members_rights_rights_right_id_fk
      references rights,
  constraint members_rights_pk
    primary key (member_id, right_id)
)
;/*--;--*/

create index if not exists members_rights_member_id_index
  on public.members_rights (member_id)
;/*--;--*/

create index if not exists members_rights_right_id_index
  on public.members_rights (right_id)
;/*--;--*/

