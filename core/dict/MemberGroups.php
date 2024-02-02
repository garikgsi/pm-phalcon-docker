<?php
namespace Core\Dict;

/**
 * Class MemberGroups
 * Словарь айдишников основных групп.
 */
final class MemberGroups {
    const ADMINISTRATOR  = 1;
    const MEMBERS        = 2;
    const GUESTS         = 3;
    const WAITING        = 4;
    const BANNED         = 5;
    const SUPERMODERATOR = 6;
    const MODERATOR      = 7;

    const NOT_ACTIVATED = [
        3 => true,
        4 => true
    ];

    const BANNED_GROUPS = [
        5 => true
    ];
}