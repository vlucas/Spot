<?php
require_once(dirname(dirname(__FILE__)) . '/Abstract.php');
require_once(dirname(dirname(__FILE__)) . '/Interface.php');
/**
 * Base PDO adapter
 *
 * @package Spot
 * @link http://spot.os.ly
 */
abstract class Spot_Adapter_PDO_Abstract extends Spot_Adapter_Abstract implements Spot_Adapter_Interface
{
	protected $_database;
	
	
	/**
	 * Get database connection
	 * 
	 * @return object PDO
	 */
	public function connection()
	{
		if(!$this->_connection) {
			if($this->_dsn instanceof PDO) {
				$this->_connection = $this->_dsn;
			} else {
				$dsnp = $this->parseDSN($this->_dsn);
				$this->_database = $dsnp['database'];
				
				// Establish connection
				try {
					$dsn = $dsnp['adapter'].':host='.$dsnp['hostspec'].';dbname='.$dsnp['database'];
					$this->_connection = new PDO($dsn, $dsnp['username'], $dsnp['password'], $this->_options);
					// Throw exceptions by default
					$this->_connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
				} catch(Exception $e) {
					throw new Spot_Exception($e->getMessage());
				}
			}
		}
		return $this->_connection;
	}
	
	
	/**
	 * Escape/quote direct user input
	 *
	 * @param string $string
	 */
	public function escape($string)
	{
		return $this->connection()->quote($string);
	}
	
	
	/**
	 * Migrate table structure changes to database
	 * @param String $table Table name
	 * @param Array $fields Fields and their attributes as defined in the mapper
	 */
	public function migrate($table, array $fields)
	{
		// Get current fields for table
		$tableExists = false;
		$tableColumns = $this->getColumnsForTable($table, $this->_database);
		
		if($tableColumns) {
			$tableExists = true;
		}
		if($tableExists) {
			// Update table
			$this->migrateTableUpdate($table, $fields);
		} else {
			// Create table
			$this->migrateTableCreate($table, $fields);
		}
	}
	
	
	/**
	 * Execute a CREATE TABLE command
	 */
	public function migrateTableCreate($table, array $formattedFields)
	{
		/*
			STEPS:
			* Use fields to get column syntax
			* Use column syntax array to get table syntax
			* Run SQL
		*/
		
		// Prepare fields and get syntax for each
		$columnsSyntax = array();
		foreach($formattedFields as $fieldName => $fieldInfo) {
			$columnsSyntax[$fieldName] = $this->migrateSyntaxFieldCreate($fieldName, $fieldInfo);
		}
		
		// Get syntax for table with fields/columns
		$sql = $this->migrateSyntaxTableCreate($table, $formattedFields, $columnsSyntax);
		
		// Add query to log
		Spot_Log::addQuery($this, $sql);
		
		$this->connection()->exec($sql);
		return true;
	}
	
	
	/**
	 * Execute an ALTER/UPDATE TABLE command
	 */
	public function migrateTableUpdate($table, array $formattedFields)
	{
		/*
			STEPS:
			* Use fields to get column syntax
			* Use column syntax array to get table syntax
			* Run SQL
		*/
		
		// Prepare fields and get syntax for each
		$tableColumns = $this->getColumnsForTable($table, $this->_database);
		$updateFormattedFields = array();
		foreach($tableColumns as $fieldName => $columnInfo) {
			if(isset($formattedFields[$fieldName])) {
				// TODO: Need to do a more exact comparison and make this non-mysql specific
				if ( 
						$this->_fieldTypeMap[$formattedFields[$fieldName]['type']] != $columnInfo['DATA_TYPE'] ||
						$formattedFields[$fieldName]['default'] !== $columnInfo['COLUMN_DEFAULT']
					) {
					$updateFormattedFields[$fieldName] = $formattedFields[$fieldName];
				}
				
				unset($formattedFields[$fieldName]);
			}
		}
		
		$columnsSyntax = array();
		// Update fields whose options have changed
		foreach($updateFormattedFields as $fieldName => $fieldInfo) {
			$columnsSyntax[$fieldName] = $this->migrateSyntaxFieldUpdate($fieldName, $fieldInfo, false);
		}
		// Add fields that are missing from current ones
		foreach($formattedFields as $fieldName => $fieldInfo) {
			$columnsSyntax[$fieldName] = $this->migrateSyntaxFieldUpdate($fieldName, $fieldInfo, true);
		}
		
		// Get syntax for table with fields/columns
		if ( !empty($columnsSyntax) ) {
			$sql = $this->migrateSyntaxTableUpdate($table, $formattedFields, $columnsSyntax);
			
			// Add query to log
			Spot_Log::addQuery($this, $sql);
			
			// Run SQL
			$this->connection()->exec($sql);
		}
		return true;
	}
	
	
	/**
	 * Prepare an SQL statement 
	 */
	public function prepare($sql)
	{
		return $this->connection()->prepare($sql);
	}
	
	/**
	 * Create new row object with set properties
	 */
	public function create($datasource, array $data, array $options = array())
	{
		$binds = $this->statementBinds($data);
		// build the statement
		$sql = "INSERT INTO " . $datasource .
			" (" . implode(', ', array_keys($data)) . ")" .
			" VALUES(:" . implode(', :', array_keys($binds)) . ")";
		
		// Add query to log
		Spot_Log::addQuery($this, $sql, $binds);
		
		// Prepare update query
		$stmt = $this->connection()->prepare($sql);
		
		if($stmt) {
			// Execute
			if($stmt->execute($binds)) {
				$result = $this->connection()->lastInsertId();
			} else {
				$result = false;
			}
		} else {
			$result = false;
		}
		
		return $result;
	}
	
	
	/**
	 * Build a select statement in SQL
	 * Can be overridden by adapters for custom syntax
	 *
	 * @todo Add support for JOINs
	 */
	public function read(Spot_Query $query, array $options = array())
	{
		$conditions = $this->statementConditions($query->conditions);
		$binds = $this->statementBinds($query->params());
		$order = array();
		if($query->order) {
			foreach($query->order as $oField => $oSort) {
				$order[] = $oField . " " . $oSort;
			}
		}
		
		$sql = "
			SELECT " . $this->statementFields($query->fields) . "
			FROM " . $query->datasource . "
			" . ($conditions ? 'WHERE ' . $conditions : '') . "
			" . ($query->group ? 'GROUP BY ' . implode(', ', $query->group) : '') . "
			" . ($order ? 'ORDER BY ' . implode(', ', $order) : '') . "
			" . ($query->limit ? 'LIMIT ' . $query->limit : '') . " " . ($query->limit && $query->offset ? 'OFFSET ' . $query->offset: '') . "
			";
		
		// Unset any NULL values in binds (compared as "IS NULL" and "IS NOT NULL" in SQL instead)
		if($binds && count($binds) > 0) {
			foreach($binds as $field => $value) {
				if(null === $value) {
					unset($binds[$field]);
				}
			}
		}
		
		// Add query to log
		Spot_Log::addQuery($this, $sql, $binds);
		
		// Prepare update query
		$stmt = $this->connection()->prepare($sql);
		
		if($stmt) {
			// Execute
			if($stmt->execute($binds)) {
				$result = $this->toCollection($query, $stmt);
			} else {
				$result = false;
			}
		} else {
			$result = false;
		}
		
		return $result;
	}
	
	/**
	 * Update entity
	 */
	public function update($datasource, array $data, array $where = array(), array $options = array())
	{
		$dataBinds = $this->statementBinds($data);
        $whereBinds = $this->statementBinds($where, count($dataBinds));
        $binds = array_merge($dataBinds, $whereBinds);
        
        $placeholders = array();
        $dataFields = array_combine(array_keys($data), array_keys($dataBinds));
        // Placeholders and passed data
		foreach($dataFields as $field => $bindField) {
            $placeholders[] = $field . " = :" . $bindField . "";
		}
        
        $conditions = $this->statementConditions($where);
		
		// Ensure there are actually updated values on THIS table
		if(count($binds) > 0) {
			// Build the query
			$sql = "UPDATE " . $datasource .
				" SET " . implode(', ', $placeholders) .
				" WHERE " . $conditions;
			
			// Add query to log
			Spot_Log::addQuery($this, $sql, $binds);
            
			// Prepare update query
			$stmt = $this->connection()->prepare($sql);
			
			if($stmt) {
				// Execute
				if($stmt->execute($binds)) {
					$result = true;
				} else {
					$result = false;
				}
			} else {
				$result = false;
			}
		} else {
			$result = false;
		}
		
		return $result;
	}
	
	
	/**
	 * Delete entities matching given conditions
	 *
	 * @param string $source Name of data source
	 * @param array $conditions Array of conditions in column => value pairs
	 */
	public function delete($datasource, array $data, array $options = array())
	{
		$binds = $this->statementBinds($data);
		$conditions = $this->statementConditions($data);
		
		$sql = "DELETE FROM " . $datasource . "";
		$sql .= ($conditions ? ' WHERE ' . $conditions : '');
		
		// Add query to log
		Spot_Log::addQuery($this, $sql, $binds);
		
		$stmt = $this->connection()->prepare($sql);
		if($stmt) {
			// Execute
			if($stmt->execute($binds)) {
				$result = true;
			} else {
				$result = false;
			}
		} else {
			$result = false;
		}
		return $result;
	}
	
	
	/**
	 * Truncate a database table
	 * Should delete all rows and reset serial/auto_increment keys to 0
	 */
	public function truncateDatasource($source) {
		$sql = "TRUNCATE TABLE " . $source;
		
		// Add query to log
		Spot_Log::addQuery($this, $sql);
		
		return $this->connection()->exec($sql);
	}
	
	
	/**
	 * Drop a database table
	 * Destructive and dangerous - drops entire table and all data
	 */
	public function dropDatasource($source) {
		$sql = "DROP TABLE " . $source;
		
		// Add query to log
		Spot_Log::addQuery($this, $sql);
		
		return $this->connection()->exec($sql);
	}
	
	
	/**
	 * Create a database
 	 * Will throw errors if user does not have proper permissions
	 */
	public function createDatabase($database) {
		$sql = "CREATE DATABASE " . $database;
		
		// Add query to log
		Spot_Log::addQuery($this, $sql);
		
		return $this->connection()->exec($sql);
	}
	
	
	/**
	 * Drop a database table
	 * Destructive and dangerous - drops entire table and all data
	 * Will throw errors if user does not have proper permissions
	 */
	public function dropDatabase($database) {
		$sql = "DROP DATABASE " . $database;
		
		// Add query to log
		Spot_Log::addQuery($this, $sql);
		
		return $this->connection()->exec($sql);
	}
	
	
	/**
	 * Return fields as a string for a query statement
	 */
	public function statementFields(array $fields = array())
	{
		return count($fields) > 0 ? implode(', ', $fields) : "*";
	}
	
	
	/**
	 * Builds an SQL string given conditions
	 */
	public function statementConditions(array $conditions = array())
	{
		if(count($conditions) == 0) { return; }
		
		$sqlStatement = "";
		$defaultColOperators = array(0 => '', 1 => '=');
		$ci = 0;
		$loopOnce = false;
		foreach($conditions as $condition) {
			if(is_array($condition) && isset($condition['conditions'])) {
				$subConditions = $condition['conditions'];
			} else {
				$subConditions = $conditions;
				$loopOnce = true;
			}
			$sqlWhere = array();
			foreach($subConditions as $column => $value) {
				$whereClause = '';
				
				// Column name with comparison operator
				$colData = explode(' ', $column);
				$operator = isset($colData[1]) ? $colData[1] : '=';
				if(count($colData) > 2) {
					$operator = array_pop($colData);
					$colData = array( implode(' ', $colData), $operator);
				}
				$col = $colData[0];
				
				// Determine which operator to use based on custom and standard syntax
				switch($operator) {
					case '<':
					case ':lt':
						$operator = '<';
					break;
					case '<=':
					case ':lte':
						$operator = '<=';
					break;
					case '>':
					case ':gt':
						$operator = '>';
					break;
					case '>=':
					case ':gte':
						$operator = '>=';
					break;
					// ALL - Find ALL values in a set - Kind of like IN(), but seeking *all* the values
					case ':all':
						throw new phpDataMapper_Exception("SQL adapters do not currently support the ':all' operator");
					break;
					// Not equal
					case '<>':
					case '!=':
					case ':ne':
					case ':not':
						$operator = '!=';
						if(is_array($value)) {
							$operator = "NOT IN";
						} elseif(is_null($value)) {
							$operator = "IS NOT NULL";
						}
					break;
					// Equals
					case '=':
					case ':eq':
					default:
						$operator = '=';
						if(is_array($value)) {
							$operator = "IN";
						} elseif(is_null($value)) {
							$operator = "IS NULL";
						}
					break;
				}
				
				// If WHERE clause not already set by the code above...
				if(is_array($value)) {
					$valueIn = "";
					foreach($value as $val) {
						$valueIn .= $this->escape($val) . ",";
					}
					$value = "(" . trim($valueIn, ',') . ")";
					$whereClause = $col . " " . $operator . " " . $value;
				} elseif(is_null($value)) {
					$whereClause = $col . " " . $operator;
				}
				
				if(empty($whereClause)) {
					// Add to binds array and add to WHERE clause
					$colParam = preg_replace('/\W+/', '_', $col) . $ci;
					$sqlWhere[] = $col . " " . $operator . " :" . $colParam . "";
				} else {
					$sqlWhere[] = $whereClause;
				}
				
				// Increment ensures column name distinction
				$ci++;
			}
			if ( $sqlStatement != "" ) {
				$sqlStatement .= " " . (isset($condition['setType']) ? $condition['setType'] : 'AND') . " ";
			}
			$sqlStatement .= implode(" " . (isset($condition['type']) ? $condition['type'] : 'AND') . " ", $sqlWhere );
			
			if($loopOnce) { break; }
		}
		
		return $sqlStatement;
	}
	
	
	/**
	 * Returns array of binds to pass to query function
	 */
	public function statementBinds(array $conditions = array(), $ci = 0)
	{
		if(count($conditions) == 0) { return; }
		
		$binds = array();
		$loopOnce = false;
		foreach($conditions as $condition) {
			if(is_array($condition) && isset($condition['conditions'])) {
				$subConditions = $condition['conditions'];
			} else {
				$subConditions = $conditions;
				$loopOnce = true;
			}
			foreach($subConditions as $column => $value) {
				
				$bindValue = false;
				
				// Handle binding depending on type
				if(is_object($value)) {
					if($value instanceof DateTime) {
						// @todo Need to take into account column type for date formatting
						$bindValue = (string) $value->format($this->dateTimeFormat());
					} else {
						$bindValue = (string) $value; // Attempt cast of object to string (calls object's __toString method)
					}
				} elseif(!is_array($value)) {
					$bindValue = $value;
				}
				
				// Bind given value
				if($bindValue !== false) {
					// Column name with comparison operator
					$colData = explode(' ', $column);
					$operator = '=';
					if (count($colData) > 2) {
						$operator = array_pop($colData);
						$colData = array(implode(' ', $colData), $operator);
					}
					$col = $colData[0];
					$colParam = preg_replace('/\W+/', '_', $col) . $ci;
					
					// Add to binds array and add to WHERE clause
					$binds[$colParam] = $bindValue;
				}
				
				// Increment ensures column name distinction
				$ci++;
			}
			if($loopOnce) { break; }
		}
		return $binds;
	}
	
	
	/**
	 * Return result set for current query
	 */
	public function toCollection(Spot_Query $query, $stmt)
	{
		$mapper = $query->mapper();
		$entityClass = $query->entityName();
		if($stmt instanceof PDOStatement) {
			$results = array();
			$resultsIdentities = array();
			
			// Set object to fetch results into
			$stmt->setFetchMode(PDO::FETCH_CLASS, $entityClass);
			
			// Fetch all results into new collection class
			while($entity = $stmt->fetch(PDO::FETCH_CLASS)) {
				
				// Store in array for ResultSet
				$results[] = $entity;
				
				// Store primary key of each unique record in set
				$pk = $mapper->primaryKey($entity);
				if(!in_array($pk, $resultsIdentities) && !empty($pk)) {
					$resultsIdentities[] = $pk;
				}
                
                // Load relations
                $mapper->loadRelations($entity);
			}
			// Ensure set is closed
			$stmt->closeCursor();
			
			$collectionClass = $mapper->collectionClass();
			return new $collectionClass($results, $resultsIdentities);
			
		} else {
			$mapper->addError(__METHOD__ . " - Unable to execute query " . implode(' | ', $this->adapterRead()->errorInfo()));
			return array();
		}
	}
	
	
	/**
	 * Bind array of field/value data to given statement
	 *
	 * @param PDOStatement $stmt
	 * @param array $binds
	 */
	protected function bindValues($stmt, array $binds)
	{
		// Bind each value to the given prepared statement
		foreach($binds as $field => $value) {
			$stmt->bindValue($field, $value);
		}
		return true;
	}
}
