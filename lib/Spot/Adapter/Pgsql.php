<?php
namespace Spot\Adapter;
use Spot\Config;

/**
 * Pgsql Database Adapter
 *
 * @package Spot
 */
class Pgsql extends PDO\BaseAbstract implements AdapterInterface
{
    // Format for date columns, formatted for PHP's date() function
    protected $_format_date = "Y-m-d";
    protected $_format_time = " H:i:s";
    protected $_format_datetime = "Y-m-d H:i:s";

    // Driver-Specific settings
    protected $_encoding = 'UTF8';

    // Map datamapper field types to actual database adapter types
    // @todo Have to improve this to allow custom types, callbacks, and validation
    protected $_fieldTypeMap = array(
        'string' => array('adapter_type' => 'varchar', 'length' => 255),
        'text' => array('adapter_type' => 'text'),
        'integer' => array('adapter_type' => 'int'),
        'boolean' => array('adapter_type' => 'boolean'),
        'float' => array('adapter_type' => 'real'),
        'double' => array('adapter_type' => 'double precision'),
        'decimal' => array('adapter_type' => 'decimal'),
        'date' => array('adapter_type' => 'date'),
        'datetime' => array('adapter_type' => 'timestamp'),
        'year' => array('adapter_type' => 'year', 'length' => 4),
        'month' => array('adapter_type' => 'month', 'length' => 2),
        'time' => array('adapter_type' => 'time'),
        'timestamp' => array('adapter_type' => 'timestamp', 'length' => 11)
    );

    /**
     * Set database encoding (typically UTF-8)
     */
    public function encoding($encoding = null)
    {
        if(null !== $encoding) {
            $this->_encoding = $encoding;
        }
        return $this->_encoding;
    }

    /**
     * Escape/quote direct user input
     *
     * @param string $string
     */
    public function escapeField($field)
    {
        return $field == '*' ? $field : '"' . $field . '"';
    }

    /**
     * Get columns for current table
     *
     * @param String $table Table name
     * @return Array
     */
    protected function getColumnsForTable($table, $source)
    {
        $tableColumns = array();
        $tblCols = $this->connection()->query("SELECT * FROM information_schema.columns WHERE table_schema = '" . $source . "' AND table_name = '" . $table . "'");

        if($tblCols) {
            while($columnData = $tblCols->fetch(\PDO::FETCH_ASSOC)) {
                $tableColumns[$columnData['COLUMN_NAME']] = $columnData;
            }
            return $tableColumns;
        } else {
            return false;
        }
    }


    /**
     * Ensure migration options are full and have all keys required
     */
    public function formatMigrateOptions(array $options)
    {
        return $options + array(
            'encoding' => $this->_encoding
        );
    }


    /**
     * Syntax for each column in CREATE TABLE command
     *
     * @param string $fieldName Field name
     * @param array $fieldInfo Array of field settings
     * @return string SQL syntax
     */
    public function migrateSyntaxFieldCreate($fieldName, array $fieldInfo)
    {
        // Get adapter options and type from typeHandler
        $typeHandler = Config::typeHandler($fieldInfo['type']);
        $fieldInfo = array_merge($fieldInfo, $typeHandler::adapterOptions());
        $adapterType = $this->_fieldTypeMap[$fieldInfo['type']]['adapter_type'];

        // Postgres SERIAL field
        if($fieldInfo['serial']) {
            $syntax = '"' . $fieldName . '" SERIAL';
        } else {
            $syntax = '"' . $fieldName . '" ' . $adapterType;
            // Column type and length
            $syntax .= ($fieldInfo['length']) ? '(' . $fieldInfo['length'] . ')' : '';
            // Unsigned
            $syntax .= ($fieldInfo['unsigned']) ? ' unsigned' : '';
            // Nullable
            $isNullable = true;
            if($fieldInfo['required'] || !$fieldInfo['null']) {
                $syntax .= ' NOT NULL';
                $isNullable = false;
            }
            // Default value
            if($fieldInfo['default'] === null && $isNullable) {
                $syntax .= " DEFAULT NULL";
            } elseif($fieldInfo['default'] !== null) {
                $default = $fieldInfo['default'];
                // If it's a boolean and $default is boolean then it should be 1 or 0
                if ( is_bool($default) && $fieldInfo['type'] == "boolean" ) {
                    $default = $default ? 1 : 0;
                }

                if(is_scalar($default)) {
                    $syntax .= " DEFAULT '" . $default . "'";
                }
            }
            // Extra
            $syntax .= ($fieldInfo['primary'] && $fieldInfo['serial']) ? ' AUTO_INCREMENT' : '';
        }
        return $syntax;
    }

    /**
     * Syntax for each column in CREATE TABLE command
     *
     * @param string $fieldName Field name
     * @param array $fieldInfo Array of field settings
     * @return string SQL syntax
     */
    public function migrateSyntaxFieldUpdate($fieldName, array $fieldInfo, $add = false)
    {
        return ( $add ? "ADD COLUMN " : "MODIFY " ) . $this->migrateSyntaxFieldCreate($fieldName, $fieldInfo);
    }

    /**
     * Syntax for CREATE TABLE with given fields and column syntax
     *
     * @param string $table Table name
     * @param array $formattedFields Array of fields with all settings
     * @param array $columnsSyntax Array of SQL syntax of columns produced by 'migrateSyntaxFieldCreate' function
     * @param Array $options Options that may affect migrations or how tables are setup
     * @return string SQL syntax
     */
    public function migrateSyntaxTableCreate($table, array $formattedFields, array $columnsSyntax, array $options)
    {
        $options = $this->formatMigrateOptions($options);

        // Begin syntax soup
        $syntax = "CREATE TABLE IF NOT EXISTS " . $table . " (\n";
        // Columns
        $syntax .= implode(",\n", $columnsSyntax);

        // Table keys
        $tableKeys = $this->getGroupedFieldKeys($table, $formattedFields);

        // FULLTEXT
        if(!empty($tableKeys['fulltext'])) {
            throw new \RuntimeException("Fulltext searching for PostgreSQL has not been implemented yet. Sorry. :(");
        }

        // PRIMARY
        if($tableKeys['primary']) {
            $syntax .= "\n, CONSTRAINT \"" . $table . "_pkey\" PRIMARY KEY(\"" . implode('", "', $tableKeys['primary']) . "\")";
        }
        // UNIQUE
        foreach($tableKeys['unique'] as $keyName => $keyFields) {
            $syntax .= "\n, CONSTRAINT \"" . $keyName . "\" UNIQUE (\"" . implode('", "', $keyFields) . "\")";
        }
        // INDEX
        foreach($tableKeys['index'] as $keyName => $keyFields) {
            $syntax .= "\n, INDEX \"" . $keyName . "\" (\"" . implode('", "', $keyFields) . "\")";
        }

        $syntax .= ")";

        return $syntax;
    }

    /**
     * Syntax for ALTER TABLE with given fields and column syntax
     *
     * @param string $table Table name
     * @param array $formattedFields Array of fields with all settings
     * @param array $columnsSyntax Array of SQL syntax of columns produced by 'migrateSyntaxFieldUpdate' function
     * @return string SQL syntax
     */
    public function migrateSyntaxTableUpdate($table, array $formattedFields, array $columnsSyntax, array $options)
    {
        /*
          Example:

            ALTER TABLE `posts`
            CHANGE `title` `title` VARCHAR( 255 ) NOT NULL ,
            CHANGE `status` `status` VARCHAR( 40 ) NULL DEFAULT 'draft'
        */

        $options = $this->formatMigrateOptions($options);

        // Begin syntax soup
        $syntax = 'ALTER TABLE ' . $table . ' \n';

        // Columns
        $syntax .= implode(",\n", $columnsSyntax);

        // Table keys
        $tableKeys = $this->getGroupedFieldKeys($table, $formattedFields);

        // FULLTEXT
        if(!empty($tableKeys['fulltext'])) {
            throw new \RuntimeException("Fulltext searching for PostgreSQL has not been implemented yet. Sorry. :(");
        }

        // PRIMARY
        if($tableKeys['primary']) {
            $syntax .= "\n, CONSTRAINT \"" . $table . "_pkey\" PRIMARY KEY(\"" . implode('", "', $tableKeys['primary']) . "\")";
        }
        // UNIQUE
        foreach($tableKeys['unique'] as $keyName => $keyFields) {
            $syntax .= "\n, CONSTRAINT \"" . $keyName . "\" UNIQUE (\"" . implode('", "', $keyFields) . "\")";
        }
        // INDEX
        foreach($tableKeys['index'] as $keyName => $keyFields) {
            $syntax .= "\n, INDEX \"" . $keyName . "\" (\"" . implode('", "', $keyFields) . "\")";
        }

        return $syntax;
    }

    /**
     * Groups field keys into names arrays of fields with key name as index
     *
     * @param string $table Table name
     * @param array $formattedFields Array of fields with all settings
     * @return array Key-named associative array of field names in that index
     */
    protected function getGroupedFieldKeys($table, array $formattedFields)
    {
        // Keys...
        $ki = 0;
        $tableKeys = array(
            'primary' => array(),
            'unique' => array(),
            'index' => array(),
            'fulltext' => array()
        );
        $usedKeyNames = array();
        foreach($formattedFields as $fieldName => $fieldInfo) {
            // Determine key field name (can't use same key name twice, so we have to append a number)
            $fieldKeyName = $table . '_' . $fieldName;
            while(in_array($fieldKeyName, $usedKeyNames)) {
                $fieldKeyName = $fieldName . '_' . $ki;
            }
            // Key type
            if($fieldInfo['primary']) {
                $tableKeys['primary'][] = $fieldName;
            }
            if($fieldInfo['unique']) {
                if(is_string($fieldInfo['unique'])) {
                    // Named group
                    $fieldKeyName = $table . '_' . $fieldInfo['unique'];
                }
                $tableKeys['unique'][$fieldKeyName][] = $fieldName;
                $usedKeyNames[] = $fieldKeyName;
            }
            if($fieldInfo['index']) {
                if(is_string($fieldInfo['index'])) {
                    // Named group
                    $fieldKeyName = $table . '_' . $fieldInfo['index'];
                }
                $tableKeys['index'][$fieldKeyName][] = $fieldName;
                $usedKeyNames[] = $fieldKeyName;
            }

            if(isset($fieldInfo['fulltext']) && $fieldInfo['fulltext']) {
                $tableKeys['fulltext'][] = $fieldName;
            }
        }

        return $tableKeys;
    }
}

