CREATE OR REPLACE FUNCTION cnt_seo_save(in_seo_id integer, in_lang_id integer, in_title varchar(256), in_keywords varchar(256), in_description varchar(512), in_otitle varchar(256), in_odescription varchar(512))
  RETURNS integer
LANGUAGE plpgsql
AS $$
begin
  IF NOT EXISTS(SELECT 1 FROM cnt_seo WHERE seo_id = in_seo_id) THEN
    RETURN 1; -- Проекта не существует
  END IF;


  INSERT INTO cnt_seo_langs (
    seo_id,
    lang_id,
    seo_meta_title,
    seo_meta_keywords,
    seo_meta_description,
    seo_ogtc_title,
    seo_ogtc_description
  )
  VALUES (
    in_seo_id,
    in_lang_id::smallint,
    in_title,
    in_keywords,
    in_description,
    in_otitle,
    in_odescription
  )
  ON CONFLICT (seo_id, lang_id)
    DO UPDATE SET
      seo_meta_title       = EXCLUDED.seo_meta_title,
      seo_meta_keywords    = EXCLUDED.seo_meta_keywords,
      seo_meta_description = EXCLUDED.seo_meta_description,
      seo_ogtc_title       = EXCLUDED.seo_ogtc_title,
      seo_ogtc_description = EXCLUDED.seo_ogtc_description;

  RETURN 0;
end;
$$;

