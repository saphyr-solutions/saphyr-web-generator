<?php

namespace SaphyrWebGenerator;

class Config
{
    /**
     * @var int
     */
    public $web_module_id;

    /**
     * @var string
     */
    public $web_unique;

    /**
     * @var string
     */
    public $api_client;

    /**
     * @var string
     */
    public $api_public_key;

    /**
     * @var string
     */
    public $api_private_key;

    /**
     * @var string
     */
    public $api_user_login;

    /**
     * @var string
     */
    public $api_user_secret;

    /**
     * @var string
     */
    public $api_temp_storage;

    /**
     * @var int
     */
    public $api_ttl;

    /**
     * @var bool
     */
    public $api_debug;

    /**
     * @var string
     */
    public $class_root_dir;

    /**
     * @var string
     */
    public $template;

    /**
     * @var bool
     */
    public $use_scssphp;

    public $secured_pages_ttl;

    /**
     * @param int $web_module_id
     * @param string $web_unique
     * @param string $api_client
     * @param string $api_public_key
     * @param string $api_private_key
     * @param string $api_user_login
     * @param string $api_user_secret
     * @param string $api_temp_storage
     */
    public function __construct(int $web_module_id, string $web_unique, string $api_client, string $api_public_key, string $api_private_key, string $api_user_login, string $api_user_secret, string $api_temp_storage)
    {
        $this->web_module_id = $web_module_id;
        $this->web_unique = $web_unique;
        $this->api_client = $api_client;
        $this->api_public_key = $api_public_key;
        $this->api_private_key = $api_private_key;
        $this->api_user_login = $api_user_login;
        $this->api_user_secret = $api_user_secret;
        $this->api_temp_storage = $api_temp_storage;
        $this->api_ttl = 30;
        $this->api_debug = false;
        $this->class_root_dir = './vendor/saphyr-solutions/saphyr-web-generator/';
        $this->use_scssphp = true;
        $this->template = "default";
        $this->secured_pages_ttl = 3600;
    }
}