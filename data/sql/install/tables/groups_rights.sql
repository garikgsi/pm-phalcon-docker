create table if not exists public.groups_rights
(
    group_id                integer           not null
    constraint groups_rights_groups_group_id_fk
    references groups,
    right_id                integer           not null
    constraint groups_rights_rights_right_id_fk
    references rights,
    right_given_to_children integer default 0 not null,
    right_given_to_leader   integer default 0 not null,
    right_is_active         integer default 0 not null,
    constraint groups_rights_pk
    primary key (group_id, right_id)
)
;/*--;--*/

comment on column groups_rights.right_given_to_children is 'Право наследуется всеми потомками';/*--;--*/

comment on column groups_rights.right_given_to_leader is 'Право выдано только лидеру группы';/*--;--*/

comment on column groups_rights.right_is_active is 'Право выданно именно этой группе';/*--;--*/

create index if not exists groups_rights_group_id_index
  on public.groups_rights (group_id)
;/*--;--*/

create index if not exists groups_rights_right_id_index
  on public.groups_rights (right_id)
;/*--;--*/

