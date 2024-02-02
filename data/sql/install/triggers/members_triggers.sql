
create or replace function dp_members_before_nick_add_change() returns trigger
language plpgsql
as $$
BEGIN
  NEW.member_nick_lower = LOWER(NEW.member_nick);

  RETURN NEW;
END;
$$
;
/*--;--*/


create or replace function dp_members_log_email_insert() returns trigger
language plpgsql
as $$
BEGIN
  INSERT INTO members_log_email (member_id, email_new, email_old) VALUES
    (NEW.member_id, NEW.member_email, OLD.member_email);

  RETURN NULL;
END;
$$
;
/*--;--*/



create or replace function dp_members_log_nick_insert() returns trigger
language plpgsql
as $$
BEGIN
  INSERT INTO members_log_nick (member_id, nick_new, nick_old) VALUES
    (NEW.member_id, NEW.member_nick, OLD.member_nick);

  RETURN NULL;
END;
$$
;
/*--;--*/

CREATE TRIGGER members_email_after_update
  AFTER UPDATE
  ON members
  FOR EACH ROW
  WHEN (((old.member_email)::text IS DISTINCT FROM (new.member_email)::text))
EXECUTE PROCEDURE dp_members_log_email_insert();
/*--;--*/


CREATE TRIGGER members_nick_after_update
  AFTER UPDATE
  ON members
  FOR EACH ROW
  WHEN (((old.member_nick)::text IS DISTINCT FROM (new.member_nick)::text))
EXECUTE PROCEDURE dp_members_log_nick_insert();
/*--;--*/


CREATE TRIGGER members_nick_lower_before_insert_or_update
  BEFORE INSERT OR UPDATE
  ON members
  FOR EACH ROW
EXECUTE PROCEDURE dp_members_before_nick_add_change();
/*--;--*/




create or replace function dp_members_log_auth_insert() returns trigger
language plpgsql
as $$
BEGIN
  INSERT INTO members_log_auth (member_id, member_email, auth_salt, auth_hash) VALUES
    (NEW.member_id, (SELECT m.member_email FROM members AS m WHERE m.member_id = NEW.member_id), NEW.auth_salt, NEW.auth_hash);

  RETURN NULL;
END;
$$
;
/*--;--*/


CREATE TRIGGER members_auth_after_insert
  AFTER INSERT
  ON members_auth
  FOR EACH ROW
EXECUTE PROCEDURE dp_members_log_auth_insert();
/*--;--*/


CREATE TRIGGER members_auth_after_update
  AFTER UPDATE
  ON members_auth
  FOR EACH ROW
  WHEN ((((old.auth_salt)::text IS DISTINCT FROM (new.auth_salt)::text) AND ((old.auth_hash)::text IS DISTINCT FROM (new.auth_hash)::text)))
EXECUTE PROCEDURE dp_members_log_auth_insert();
/*--;--*/
