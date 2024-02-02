create table if not exists cnt_seo
(
  seo_id serial not null
    constraint cnt_seo_seo_id_pk
    primary key,
  seo_name varchar(256) not null,
  seo_twitter_small_image_id integer not null
    constraint cnt_seo_tw_small_image_id_fk
    references cnt_images,
  seo_twitter_large_image_id integer not null
    constraint cnt_seo_tw_large_image_id_fk
    references cnt_images,
  seo_facebook_small_image_id integer not null
    constraint cnt_seo_fb_small_image_id_fk
    references cnt_images,
  seo_facebook_large_image_id integer not null
    constraint cnt_seo_fb_large_image_id_fk
    references cnt_images
)
;/*--;--*/

create unique index if not exists cnt_seo_seo_id_uindex
  on cnt_seo (seo_id)
;/*--;--*/

create unique index if not exists cnt_seo_seo_name_uindex
  on cnt_seo (seo_name)
;/*--;--*/



