create or replace function dp_rights_is_common_update(in_right_id integer) returns void
  language plpgsql
as $$
declare
  tmp_is_common integer;
begin
  tmp_is_common = 1;

  IF (
      EXISTS(SELECT 1 FROM rights_to_sites WHERE right_id = in_right_id) OR
      EXISTS(SELECT 1 FROM rights_to_apps  WHERE right_id = in_right_id) OR
      EXISTS(SELECT 1 FROM rights_to_controllers  WHERE right_id = in_right_id)
    ) THEN
    tmp_is_common = 0;
  END IF;

  UPDATE rights SET right_common = tmp_is_common WHERE right_id = in_right_id;
end;
$$;
/*--;--*/

create or replace function dp_rights_is_used_update(in_right_id integer) returns void
  language plpgsql
as $$
declare
  tmp_is_used integer;
begin
  tmp_is_used = 0;

  IF (
      EXISTS(SELECT 1 FROM groups_rights WHERE right_id = in_right_id) OR
      EXISTS(SELECT 1 FROM members_rights  WHERE right_id = in_right_id)
    ) THEN
    tmp_is_used = 1;
  END IF;

  UPDATE rights SET right_in_use = tmp_is_used WHERE right_id = in_right_id;
end;
$$;
/*--;--*/

create or replace function dp_rights_to_change() returns trigger
  language plpgsql
as $$
begin
  IF (TG_OP = 'DELETE') THEN
    PERFORM dp_rights_is_common_update(OLD.right_id);
    RETURN OLD;
  ELSIF (TG_OP = 'INSERT') THEN
    PERFORM dp_rights_is_common_update(NEW.right_id);
    RETURN NEW;
  END IF;
end;
$$;
/*--;--*/

create or replace function dp_rights_to_change_used() returns trigger
  language plpgsql
as $$
begin
  IF (TG_OP = 'DELETE') THEN
    PERFORM dp_rights_is_used_update(OLD.right_id);
    RETURN OLD;
  ELSIF (TG_OP = 'INSERT') THEN
    PERFORM dp_rights_is_used_update(NEW.right_id);
    RETURN NEW;
  END IF;
end;
$$;
/*--;--*/

CREATE TRIGGER rights_to_apps_after_insert_or_delete
  AFTER INSERT OR DELETE
  ON rights_to_apps
  FOR EACH ROW
EXECUTE PROCEDURE dp_rights_to_change();
/*--;--*/

CREATE TRIGGER rights_to_sites_after_insert_or_delete
  AFTER INSERT OR DELETE
  ON rights_to_sites
  FOR EACH ROW
EXECUTE PROCEDURE dp_rights_to_change();
/*--;--*/

CREATE TRIGGER rights_to_controllers_after_insert_or_delete
  AFTER INSERT OR DELETE
  ON rights_to_controllers
  FOR EACH ROW
EXECUTE PROCEDURE dp_rights_to_change();
/*--;--*/

CREATE TRIGGER members_rights_after_insert_or_delete
  AFTER INSERT OR DELETE
  ON members_rights
  FOR EACH ROW
EXECUTE PROCEDURE dp_rights_to_change_used();
/*--;--*/

CREATE TRIGGER groups_rights_after_insert_or_delete
  AFTER INSERT OR DELETE
  ON groups_rights
  FOR EACH ROW
EXECUTE PROCEDURE dp_rights_to_change_used();
/*--;--*/