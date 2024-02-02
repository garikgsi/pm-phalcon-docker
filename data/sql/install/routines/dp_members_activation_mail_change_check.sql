create function dp_members_activation_mail_change_check(in_activation_key character varying)
  returns TABLE(member_id bigint, new_email email, member_email email)
  language plpgsql
as $$
BEGIN
  return QUERY SELECT
                 mk.member_id,
                 mce.new_email,
                 m.member_email
               FROM
                 members AS m,
                 members_keys AS mk,
                 members_change_email AS mce
               WHERE
                   m.member_id    = mk.member_id AND
                   m.member_group = 4 AND
                   mce.member_id = m.member_id AND
                   mk.key_value   = in_activation_key AND
                   mk.key_type    = 'activatechange';
END;
$$;