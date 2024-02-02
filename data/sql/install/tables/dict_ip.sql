create table if not exists dict_ip
(
  ip_id bigserial not null
    constraint dict_ip_pkey
    primary key,
  ip_addr inet not null
)
;
/*--;--*/

create unique index if not exists dict_ip_ip_addr_uindex
  on dict_ip (ip_addr)
;
/*--;--*/

