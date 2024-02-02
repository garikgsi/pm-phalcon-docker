<?php
namespace Core\Controller;

class HttpDomain
{
    private static $instance;

    private $domainName;
    private $domainHost;
    private $domainConfig;
    private $domainsList = [];

    public function __construct() {
        if (self::$instance) {
            return self::$instance;
        }

        $this->domainsList = require_once(DIR_CACHE_SITES.'domains.php');

        self::$instance = $this;
    }

    public function validateDomain() {
        $domainHost = strtolower($_SERVER['HTTP_HOST']);

        $cleanDomain = str_replace('www.', '', $domainHost);

        if (!isset($this->domainsList[$cleanDomain])) {
            throw new \Exception('Invalid Domain name - no such domain in list');
        }

        $this->domainHost = $domainHost;
        $this->domainName = $cleanDomain;
        $this->domainConfig = $this->domainsList[$cleanDomain];
    }

    public function getName() {
        return $this->domainConfig['name'];
    }

    public function getHost() {
        return $this->domainHost;
    }

    public function getLangId() {
        return $this->domainConfig['lang_id'];
    }

    public function getLangName() {
        return $this->domainConfig['lang'];
    }

}