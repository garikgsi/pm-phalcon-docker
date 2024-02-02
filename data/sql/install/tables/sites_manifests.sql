create table if not exists sites_manifests
(
  site_id integer not null
    constraint sites_manifests_pkey
    primary key
    constraint sites_manifests_site_id_fk
    references sites,
  display_id integer default 1
    constraint sites_manifests_display_id_fk
    references sites_manifests_display,
  orientation_id integer default 1
    constraint sites_manifests_orientation_id_fk
    references sites_manifests_orientation,
  manifest_url varchar100,
  manifest_addr_color varchar32,
  manifest_dir varchar8 default 'ltr'::character varying not null,
  manifest_theme_color varchar32,
  manifest_scope varchar100,
  manifest_ios_color_addr varchar32,
  manifest_ios_color_touch varchar32,
  manifest_ms_color_tile varchar32,
  ogtc_og_site varchar100,
  ogtc_twitter_account varchar256,
  ogtc_twitter_mode integer default 0
)
;
/*--;--*/

comment on column sites_manifests.display_id is 'Определяет предпочтительный для разработчика режим отображения приложения.'
;
/*--;--*/

comment on column sites_manifests.orientation_id is 'Определяет ориентацию по умолчанию для всех верхних уровней контекстов браузера приложения.'
;
/*--;--*/

comment on column sites_manifests.manifest_url is 'Определяет URL, который загружается, когда пользователь запустил приложение с устройства. Если относительный URL, базовый URL будет URL манифеста.'
;
/*--;--*/

comment on column sites_manifests.manifest_dir is 'Определяет основное направление текста для свойств name, short_name и description.
Вместе с lang, позволяет корректно отобразить языки, читающиеся справа налево.'
;
/*--;--*/

comment on column sites_manifests.manifest_theme_color is 'Определяет цвет темы по умолчанию для приложения. Иногда влияет на то, как приложение отображается ОС (например, в переключателе задач Android цвет темы окружает приложение).'
;
/*--;--*/

