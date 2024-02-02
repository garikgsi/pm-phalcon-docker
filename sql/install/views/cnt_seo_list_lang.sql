CREATE OR REPLACE VIEW cnt_seo_list_lang AS SELECT
res.*,
l.lang_id,
csl.seo_meta_title,
csl.seo_meta_keywords,
csl.seo_meta_description,
csl.seo_ogtc_title,
csl.seo_ogtc_description
FROM
(
  SELECT
    cs.seo_id,
    cs.seo_name,
    ci1.image_id   AS fb_sm_image_id,
    ci1.image_name AS fb_sm_image_name,
    ci1.image_type AS fb_sm_image_type,
    ci2.image_id   AS fb_lr_image_id,
    ci2.image_name AS fb_lr_image_name,
    ci2.image_type AS fb_lr_image_type,
    ci3.image_id   AS tw_sm_image_id,
    ci3.image_name AS tw_sm_image_name,
    ci3.image_type AS tw_sm_image_type,
    ci4.image_id   AS tw_lr_image_id,
    ci4.image_name AS tw_lr_image_name,
    ci4.image_type AS tw_lr_image_type
  FROM
    cnt_seo AS cs,
    cnt_images AS ci1,
    cnt_images AS ci2,
    cnt_images AS ci3,
    cnt_images AS ci4
  WHERE
    ci1.image_id = cs.seo_facebook_small_image_id AND
    ci2.image_id = cs.seo_facebook_large_image_id AND
    ci3.image_id = cs.seo_twitter_small_image_id AND
    ci4.image_id = cs.seo_twitter_large_image_id
) AS res
CROSS JOIN languages AS l
LEFT JOIN cnt_seo_langs AS csl ON csl.lang_id = l.lang_id AND csl.seo_id = res.seo_id;


