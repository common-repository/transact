<?php
namespace Transact\Models\transactTransactionsTable;

/**
 * Class transactSubscriptionTransactionsModel
 * todo: refactor together with other model
 */
class transactSubscriptionTransactionsModel
{
    /**
     * Table constant name
     */
    const TRANSACTIONS_TABLE_NAME = 'transact_subscription_transactions';

    /**
     * KEys from the table
     */
    const EXPIRATION_KEY  = 'expiration';
    const SALES_KEY       = 'sales_id';
    const TIMESTAMP_KEY   = 'timestamp';

    /**
     * WP DB connector
     *
     * @var \wpdb
     */
    protected $connector;

    /**
     * Table Name
     *
     * @var string
     */
    protected $table_name;

    /**
     * Array that holds the result
     *
     * @var array
     */
    protected $results;


    function __construct()
    {
        global $wpdb;
        $this->connector = $wpdb;
        $this->table_name = $wpdb->prefix . self::TRANSACTIONS_TABLE_NAME;
    }

    /**
     * @param $sale_id
     * @return array
     */
    public function get_subscription_by_sale_id($sale_id)
    {
        $sql = $this->connector->prepare("SELECT * FROM $this->table_name WHERE  " . self::SALES_KEY. "='%s'", $sale_id);
        $this->results = $this->connector->get_results( $sql, ARRAY_A );
        return $this->beautify_result($this->results);
    }

    /**
     * Create a transaction record on the DB
     *
     * @param $expiration
     * @param $sales_id
     * @param $timestamp
     * @return bool
     */
    public function create_subscription($expiration, $sales_id, $timestamp)
    {
        try {
            $row = $this->connector->replace(
                $this->table_name,
                array(
                    self::SALES_KEY      => $sales_id,
                    self::EXPIRATION_KEY => $expiration,
                    self::TIMESTAMP_KEY  => $timestamp
                ),
                array(
                    '%s',
                    '%d',
                    '%d'
                )
            );
            if ($row) {
                return true;
            } else {
                return false;
            }
        } catch(\Exception $e) {
            return false;
        }
    }

    /**
     * Returns empty, one result or array of results
     * @param $results
     * @return array
     */
    private function beautify_result($results)
    {
        if (empty($results)) {
            return array();
        } else if (count($results) === 1) {
            return current($results);
        } else {
            return $results;
        }
    }
}
