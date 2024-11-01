<?php
namespace Transact\Utils\Config\Parser;

/**
 * Class ConfigParser
 */
class ConfigParser
{
    /**
     * Holds Config Array configuration
     *
     * @var array
     */
    private $config;

    /**
     * Key for API_HOST
     */
    const API_HOST = 'api_host';

    /**
     * key for API_AUTHENTICATION
     */
    const API_AUTHENTICATION = 'api_authentication';

    /**
     * key for API_SUBSCRIPTION
     */
    const API_SUBSCRIPTION = 'api_subscription_auth';

    /**
     * key for API_SUBSCRIPTION_VALIDATE
     */
    const API_SUBSCRIPTION_VALIDATE = 'api_subscription_validate';

    /**
     * Key for JS_LIBRARY
     */
    const JS_LIBRARY = 'js_xsact_library';

    /**
     * Key for PURCHASE_URL
     */
    const PURCHASE_URL = 'xsact_purchase_url';

    /**
     * Key for POSTD_URL
     */
    const POSTD_URL = 'postd_url';

    /**
     * Key for POSTD_REGEX
     */
    const POSTD_REGEX = 'postd_regex';


    /**
     * Base URL for transact server
     */
    const XSACT_BASE_URL = 'xsact_base_url';


    /**
     * Parses config.ini
     */
    public function __construct()
    {
        $this->config = parse_ini_file(CONFIG_PATH);
    }

    /**
     * Returning configuration
     *
     * @return array
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * Returning JS api library url
     *
     * @return string js api library url
     */
    public function getJSLibrary()
    {
        return $this->config[self::JS_LIBRARY];
    }

    /**
     * Returning Transact purchase url
     *
     * @return string transact purchase url
     */
    public function getPurchaseUrl()
    {
        return $this->config[self::PURCHASE_URL];
    }

    /**
     * Returning Postd api url for oembed
     *
     * @return string transact purchase url
     */
    public function getPostdUrl()
    {
        return $this->config[self::POSTD_URL];
    }

    /**
     * Returning Postd api url for oembed
     *
     * @return string transact purchase url
     */
    public function getPostdPostRegex()
    {
        return $this->config[self::POSTD_REGEX];
    }

    /**
     * Retrieves Api authentication url
     *
     * @return string apu authetication url
     */
    public function getValidationUrl()
    {
        return $this->config[self::API_HOST] . $this->config[self::API_AUTHENTICATION];
    }

    public function getOauthURL()
    {
        return $this->config[self::XSACT_BASE_URL] . 'oauth2';
    }

    public function getOauthTokenURL()
    {
        return $this->config[self::XSACT_BASE_URL] . 'api/oauth2/token';
    }
    public function getOauthUserProfileURL()
    {
        return $this->config[self::XSACT_BASE_URL] . 'api/oauth2/user/profile';
    }


    /**
     * Retrieves Api authentication url
     *
     * @return string apu authetication url
     */
    public function checkIfValidUserSubscription()
    {
        return $this->config[self::API_HOST] . $this->config[self::API_SUBSCRIPTION_VALIDATE];
    }

    /**
     * Retrieves Api validation subscription url
     *
     * @return string api subscription validation url
     */
    public function getValidationSubscriptionUrl()
    {
        return $this->config[self::API_HOST] . $this->config[self::API_SUBSCRIPTION];
    }

}

