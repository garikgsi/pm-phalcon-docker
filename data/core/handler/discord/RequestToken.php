<?php
namespace Core\Handler\Discord;

class RequestToken extends Request
{
    private $authorizeCode = null;

    const OAUTH2_URL = 'https://discordapp.com/api/oauth2/';

    public function setCode($code) {
        $this->authorizeCode = $code;
    }
    public function getCode() {
        return $this->authorizeCode;
    }

    public function requestAccessToken() {
        return $this->sendRequest(
            RequestToken::OAUTH2_URL . 'token',
            [
                'grant_type' => 'authorization_code',
                'client_id' => $this->config['client_id'],
                'client_secret' => $this->config['client_secret'],
                'redirect_uri' => 'https://'.$_SERVER['HTTP_HOST'].$this->config['redirect_token'],
                'code' => $this->authorizeCode
            ]
        );
    }
}
