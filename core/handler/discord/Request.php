<?php
namespace Core\Handler\Discord;

class Request
{
    protected $config;
    protected $accessToken = null;

    const API_URL = 'https://discord.com/api/';

    public function __construct($config) {
        $this->config = $config;
    }

    public function setAccessToken($token) {
        $this->accessToken = $token;
    }

    public function requestSend($apiMethod) {
        return $this->sendRequest(Request::API_URL . $apiMethod);
    }

    protected function sendRequest($url, $postFields = null) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

        if($postFields)
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postFields));

        $headers[] = 'Accept: application/json';

        if($this->accessToken)
            $headers[] = 'Authorization: Bearer ' . $this->accessToken;

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($ch);
        return json_decode($response,true);
    }

}
