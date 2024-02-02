create function dp_members_get_by_cookie(in_cookie_key character varying) returns bigint
  language plpgsql
as
$$
DECLARE
  tmp_member_id bigint;
BEGIN
  SELECT member_id FROM members_cookies WHERE cookie_key = in_cookie_key INTO tmp_member_id;

  IF (tmp_member_id IS NULL) THEN
    return 0;
  END IF;

  UPDATE members_cookies SET cookie_date = NOW() WHERE cookie_key = in_cookie_key;

  RETURN tmp_member_id;
END;
$$;