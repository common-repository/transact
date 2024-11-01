<?php
namespace Transact\Admin\Api;
/**
 * Class TransactApi
 */
class TransactApi
{
    /**
     * Account ID key used on config.ini
     */
    const ACCOUNT_ID_KEY = '{{account_id}}';

    /**
     * Account ID key used on config.ini
     */
    const DIGEST_ID_KEY = '{{digest}}';


    /**
     * Validates publisher settings against transact.io service
     *
     * @param $validate_url
     * @param $account_id
     * @param $secret
     * @return bool
     */
    public function validates($validate_url, $account_id, $secret)
    {
        $search = array (
            self::ACCOUNT_ID_KEY,
            self::DIGEST_ID_KEY
        );

        $replace = array (
            $account_id,
            $this->digest($secret)
        );

        $url = str_replace($search, $replace, $validate_url);

        // for now don't want to add more dependencies so ignore.
        //phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.wp_remote_get_wp_remote_get
        $ret = wp_remote_get($url);

        if (empty($ret) || is_wp_error(($ret))) {
            return false;
        }

        if ($ret['response']['code'] === 200) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Validate subscription against transact.io
     *
     * @param $validate_url
     * @param $account_id
     * @return bool
     */
    public function subscriptionValidates($validate_url, $account_id)
    {
        $url = str_replace(self::ACCOUNT_ID_KEY, $account_id, $validate_url);

        // for now don't want to add more dependencies so ignore.
        //phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.wp_remote_get_wp_remote_get
        $ret = wp_remote_get($url);

        if (empty($ret) || $ret['response']['code'] !== 200) {
            return false;
        }

        $body = json_decode($ret['body']);
        if (empty($body)) {
            return false;
        }

        /**
         * query show an array of two elements (monthly and annual subscription)
         */
        if (count($body) > 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * It creates the digest to validate
     * todo: right now second parameter is hardcoded to test
     *
     * @param $secret
     * @return string
     */
    public function digest($secret)
    {
        return hash_hmac('sha256', 'test', $secret);
    }


}

