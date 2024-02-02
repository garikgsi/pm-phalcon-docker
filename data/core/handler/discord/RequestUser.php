<?php
namespace Core\Handler\Discord;

class RequestUser extends Request
{
    public function getMe() {
        return $this->requestSend('users/@me');
    }

    public function getUser($userId) {
        return $this->requestSend('users/'.$userId);
    }
}
