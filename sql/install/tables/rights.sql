create table if not exists public.rights
(
  right_id serial not null
    constraint rights_pk
      primary key,
  right_order integer default 0 not null,
  right_name varchar(64) not null,
  right_common integer default 1 not null,
  right_in_use integer default 0 not null,
  right_parent_id integer
    constraint rights_rights_right_id_fk
      references rights
);/*--;--*/

comment on column public.rights.right_common is 'Право является общедоступным для всех сайтов и систем
Проставляется из триггеров на таблицах: rights_to_apps, rights_to_sites, rights_to_controllers';/*--;--*/

comment on column public.rights.right_in_use is 'Нейм права заблокирован для изменения если стоит 1.
В то м случае, когда есть ссылки из мемберов или групп, это говорит о том, что право уже имплементированно в коде.';/*--;--*/



create unique index if not exists rights_right_name_uindex
  on public.rights (right_name)
;/*--;--*/
