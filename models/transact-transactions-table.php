<?php
namespace Transact\Models\transactTransactionsTable;

/**
 * Class transactTransactionsModel
 */
class transactTransactionsModel
{
    /**
     * Table constant name
     */
    const TRANSACTIONS_TABLE_NAME = 'transact_transactions';

    /**
     * KEys from the table
     */
    const POST_KEY      = 'post_id';
    const SALES_KEY     = 'sales_id';
    const TIMESTAMP_KEY = 'timestamp';

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
     * Retrieves all rows from a given post id
     *
     * @param $post_id
     * @return array
     */
    public function get_transactions_by_post_id($post_id)
    {
        $sql = $this->connector->prepare("SELECT * FROM $this->table_name WHERE " . self::POST_KEY. " = '%s' AND " . self::TIMESTAMP_KEY. " > 0", $post_id);
        $this->results = $this->connector->get_results( $sql, ARRAY_A );
        return $this->beautify_result($this->results);
    }

    /**
     * @param $sale_id
     * @return array
     */
    public function get_transaction_by_sale_id($sale_id)
    {
        $sql = $this->connector->prepare("SELECT * FROM $this->table_name WHERE  " . self::SALES_KEY. "='%s'", $sale_id);
        $this->results = $this->connector->get_results( $sql, ARRAY_A );
        return $this->beautify_result($this->results);
    }

    /**
     * Create a transaction record on the DB
     *
     * @param $post_id
     * @param $sales_id
     * @param $timestamp
     * @return bool
     */
    public function create_transaction($post_id, $sales_id)
    {
        try {
            $row = $this->connector->insert(
                $this->table_name,
                array(
                    self::POST_KEY      => $post_id,
                    self::SALES_KEY     => $sales_id,
                    self::TIMESTAMP_KEY => 0
                ),
                array(
                    '%d',
                    '%s',
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

    public function validate_transaction(int $post_id, $sales_id, $timestamp) {
        try {
            $result = $this->get_transaction_by_sale_id($sales_id);
            
            if(count($result) === 0) {
                return false;
            }

            if($post_id !== (int)$result[self::POST_KEY]) {
                return false;
            }

            $row = $this->connector->update(
                $this->table_name,
                array(
                    self::TIMESTAMP_KEY => $timestamp
                ),
                array(
                    self::POST_KEY      => $post_id,
                    self::SALES_KEY     => $sales_id
                ),
                array(
                    '%d',
                    '%s',
                    '%d'
                )
            );

            if ($row !== false) {
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
