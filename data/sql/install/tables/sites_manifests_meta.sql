create table if not exists sites_manifests_meta
(
  site_id integer not null
    constraint sites_manifests_meta_site_id_fk
    references sites,
  lang_id smallint not null
    constraint sites_manifests_meta_lang_id_fk
    references languages,
  manifest_name varchar100,
  manifest_name_short varchar64,
  manifest_description varchar256,
  ogtc_title varchar150,
  ogtc_description varchar256,
  constraint sites_manifest_meta_pk
  primary key (site_id, lang_id)
)
;
/*--;--*/

comment on column sites_manifests_meta.manifest_name is 'Предоставляет удобочитаемое имя для приложения, предназначенное для отображения для пользователя, например, в списке других приложений или как подпись к иконке.'
;
/*--;--*/

comment on column sites_manifests_meta.manifest_name_short is 'Предоставляет короткое удобочитаемое имя приложения. Предназначено для использования там, где недостаточно места для отображения полного имени приложения.'
;
/*--;--*/

comment on column sites_manifests_meta.manifest_description is 'Обеспечивает общее описание того, что делает приложение.'
;
/*--;--*/

