create or replace function cnt_seo_create_images() returns trigger
language plpgsql
as $$
BEGIN
  INSERT INTO cnt_images (image_name, image_type) VALUES ('', '');

  NEW.seo_twitter_small_image_id = currval('grin_images_image_id_seq');

  INSERT INTO cnt_images (image_name, image_type) VALUES ('', '');

  NEW.seo_twitter_large_image_id = currval('grin_images_image_id_seq');

  INSERT INTO cnt_images (image_name, image_type) VALUES ('', '');

  NEW.seo_facebook_small_image_id = currval('grin_images_image_id_seq');

  INSERT INTO cnt_images (image_name, image_type) VALUES ('', '');

  NEW.seo_facebook_large_image_id = currval('grin_images_image_id_seq');

  RETURN NEW;
END;
$$
;/*--;--*/

create trigger cnt_seo_before_insert
  before insert
  on cnt_seo
  for each row
execute procedure cnt_seo_create_images()
;
