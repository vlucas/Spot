<?php
namespace Spot;

/**
 * Query interface
 *
 * @package Spot
 * @link http://spot.os.ly
 * @link http://github.com/actridge/Spot
 */
interface QueryInterface
{
    /**
     * Constructor
     *
     * @param object $adapter
     * @return string
     */
    public function __construct(\Spot\Mapper $mapper, $entityName);

    /**
     * Called from mapper's select() function
     *
     * @param mixed $fields (optional)
     * @param string $table Table name
     * @return string
     */
    public function select($fields = "*", $table);

    /**
     * From
     *
     * @param string $table Name of the table to perform the SELECT query on
     * @todo Support multiple tables/joins
     */
    public function from($table = null);

    /**
     * WHERE conditions
     *
     * @param array $conditions Array of conditions for this clause
     * @param string $type Keyword that will separate each condition - "AND", "OR"
     * @param string $setType Keyword that will separate the whole set of conditions - "AND", "OR"
     */
    public function where(array $conditions = array(), $type = "AND", $setType = "AND");
    public function orWhere(array $conditions = array(), $type = "AND");
    public function andWhere(array $conditions = array(), $type = "AND");

    /**
     * ORDER BY columns
     */
    public function order($fields = array());

    /**
     * GROUP BY columns
     */
    public function group(array $fields = array());

    /**
     * LIMIT query or result set
     */
    public function limit($limit = 20, $offset = null);

    /**
     * HAVING query or result set
     */
    public function having(array $having = array());
}
