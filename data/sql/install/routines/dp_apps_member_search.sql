create or replace function dp_apps_member_search(in_app_id integer, in_member_nick varchar(100)) returns table(
  member_id bigint,
  member_gender gender,
  member_nick varchar100
)
  language plpgsql
as $$
begin
  return QUERY SELECT
                 mm.member_id,
                 mm.member_gender,
                 mm.member_nick
               FROM
                 (
                   SELECT m.member_id,
                          m.member_gender,
                          m.member_nick
                   FROM members AS m
                   WHERE m.member_nick_lower LIKE '%' || in_member_nick || '%'
                 ) AS mm
                   LEFT JOIN apps_access_members AS acm ON acm.member_id = mm.member_id AND acm.app_id = in_app_id
               WHERE
                 acm.member_id IS NULL
               ORDER BY mm.member_id
               LIMIT 15;
end;
$$;