create table if not exists rights_relationships
(
  right_id integer not null
    constraint rights_relationships_rights_right_id_fk
      references rights,
  parent_id integer not null
    constraint rights_relationships_rights_right_id_fk_2
      references rights,
  constraint rights_relationships_pk
    primary key (right_id, parent_id)
);/*--;--*/
