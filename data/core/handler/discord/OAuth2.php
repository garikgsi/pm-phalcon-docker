<?php
namespace Core\Handler\Discord;

class OAuth2
{
    private $config;

    private $accessToken;

    const OAUTH2_URL = 'https://discordapp.com/api/oauth2/';

    public function __construct($config)
    {
        $this->config = $config;
    }

    public function setAccessToken($token) {
        $this->accessToken = $token;
    }

    public function authorizeRedirect() {
        $this->redirectUser(
            'authorize',
            [
                'client_id' => $this->config['client_id'],
                'redirect_uri' => 'https://'.$_SERVER['HTTP_HOST'].$this->config['redirect_token'],
                'response_type' => 'code',
                'scope' => implode(' ', $this->config['scope'])
            ]
        );
    }

    public function revokeRedirect() {
        $this->redirectUser('token/revoke', ['access_token' => $this->accessToken]);
    }

    private function redirectUser($method, $params) {
        header('Location: '.OAuth2::OAUTH2_URL.$method.'?'.http_build_query($params));
        exit;
    }
}
