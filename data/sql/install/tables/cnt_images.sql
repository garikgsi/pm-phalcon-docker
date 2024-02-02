create table if not exists cnt_images
(
  image_id bigserial not null
    constraint grin_images_pkey
    primary key,
  image_name varchar(512),
  image_type varchar8,
  image_optimized integer default 0 not null,
  image_temp_name varchar(512),
  image_temp_type varchar8,
  image_width integer,
  image_height integer
)
;/*--;--*/

create unique index if not exists grin_images_image_id_uindex
  on cnt_images (image_id)
;
