CREATE FUNCTION emails_add_log(in_member_id bigint, in_email_id integer, in_data json)
  RETURNS smallint
LANGUAGE plpgsql
AS $$
BEGIN
  INSERT INTO emails_log (email_id, member_id, email_data) VALUES (in_email_id, in_member_id, in_data);
  RETURN 0;
END;
$$;

