<?php
require_once __DIR__ . '/database.php';

class BaseModel
{
    protected $conn;
    protected $tableName;
    protected $primaryKey = 'id'; // Default primary key

    public function __construct()
    {
        $this->conn = Database::getConnection();
        if ($this->conn->connect_error) {
            die("Connection failed: " . $this->conn->connect_error);
        }
    }

    /**
     * Builds the SELECT clause for a SQL query.
     * @param array $columns Array of column names to select.
     * @param string|null $tablePrefix Optional table name prefix.
     * @return string The generated SELECT clause.
     */
    protected function _buildSelectClause($columns, $tablePrefix = null)
    {
        if (empty($columns)) {
            return $tablePrefix ? "`{$tablePrefix}`.*" : "*";
        }
        
        $prefix = $tablePrefix ? "`{$tablePrefix}`." : "";
        return implode(", ", array_map(function ($col) use ($prefix) {
            return $prefix . "`$col`";
        }, $columns));
    }

    /**
     * Builds the WHERE clause for a SQL query from a filters array.
     * @param array $filters Associative array of filters.
     * @param array &$params Array to bind values to, passed by reference.
     * @param array &$types String to build data types from, passed by reference.
     * @param string|null $tablePrefix Optional table name prefix.
     * @return string The generated WHERE clause.
     */
    protected function _buildWhereClause($filters, &$params, &$types, $tablePrefix = null)
    {
        if (empty($filters)) {
            return "";
        }

        $prefix = $tablePrefix ? "`{$tablePrefix}`." : "";
        $whereClauses = [];
        foreach ($filters as $key => $value) {
            $whereClauses[] = $prefix . "`$key` = ?";
            $params[] = $value;
            $types .= 's'; // Assume string for simplicity
        }
        return " WHERE " . implode(" AND ", $whereClauses);
    }

    /**
     * Builds the ORDER BY clause for a SQL query.
     * @param array $orderBy Associative array for sorting. e.g., ['name' => 'ASC']
     * @param string|null $tablePrefix Optional table name prefix.
     * @return string The generated ORDER BY clause.
     */
    protected function _buildOrderByClause($orderBy, $tablePrefix = null)
    {
        // If not an array or empty, nothing to order
        if (!is_array($orderBy) || empty($orderBy)) {
            return "";
        }

        $prefix = $tablePrefix ? "`{$tablePrefix}`." : "";
        $orderClauses = [];
        foreach ($orderBy as $key => $direction) {
            // sanitize inputs
            $col = trim((string)$key);
            $dir = strtoupper(trim((string)$direction));
            if ($col === '' || ($dir !== 'ASC' && $dir !== 'DESC')) {
                // skip invalid entries
                continue;
            }

            // escape column name and add prefix
            if (strpos($col, '.') !== false) {
                $sub = explode('.', $col);
                $sub = array_map(function($p){ return "`".trim($p,'` ')."`"; }, $sub);
                $colEscaped = implode('.', $sub);
            } else {
                $colEscaped = "`" . trim($col, '` ') . "`";
            }
            if ($prefix) {
                $p = rtrim($prefix, '.') . '.';
                $colEscaped = $p . ltrim($colEscaped, '`');
            }

            $orderClauses[] = $colEscaped . " " . $dir;
        }

        if (count($orderClauses) === 0) {
            return "";
        }

        return ' ORDER BY ' . implode(', ', $orderClauses);
    }

    /**
     * Fetches a single record by its primary key.
     * @param mixed $id The ID of the record to fetch.
     * @param array $selectedColumns Columns to select.
     * @return array|null An associative array representing the record, or null if not found.
     */
    public function find($id, $selectedColumns = [])
    {
        $selectClause = $this->_buildSelectClause($selectedColumns, $this->tableName);
        $sql = "SELECT " . $selectClause . " FROM `{$this->tableName}` WHERE `{$this->primaryKey}` = ?";
        $stmt = $this->conn->prepare($sql);
        
        // Dynamically determine bind_param type
        $pkType = is_int($id) ? 'i' : 's';
        $stmt->bind_param($pkType, $id);
        
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    /**
     * Fetches a single record based on filters.
     * @param array $filters Associative array of filters.
     * @param array $selectedColumns Columns to select.
     * @return array|null An associative array representing the record, or null if not found.
     */
    public function findOne($filters, $selectedColumns = [])
    {
        $params = [];
        $types = '';
        $selectClause = $this->_buildSelectClause($selectedColumns, $this->tableName);
        $whereClause = $this->_buildWhereClause($filters, $params, $types, $this->tableName);

        $sql = "SELECT " . $selectClause . " FROM `{$this->tableName}`" . $whereClause . " LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    /**
     * Fetches all records from the table, with optional filtering and sorting.
     * @param array $filters Associative array of filters.
     * @param array $orderBy Associative array for sorting.
     * @param array $selectedColumns Columns to select.
     * @return array An array of associative arrays representing the records.
     */
    public function findAll($filters = [], $orderBy = [], $selectedColumns = [])
    {
        $params = [];
        $types = '';
        $selectClause = $this->_buildSelectClause($selectedColumns, $this->tableName);
        $whereClause = $this->_buildWhereClause($filters, $params, $types, $this->tableName);
        $orderByClause = $this->_buildOrderByClause($orderBy, $this->tableName);

        $sql = "SELECT " . $selectClause . " FROM `{$this->tableName}`" . $whereClause . $orderByClause;
        // DEBUG: log built SQL and params for troubleshooting (temporary)
        error_log("[DEBUG SQL - findAll] " . $sql . " | params: " . json_encode($params));
        @file_put_contents(__DIR__ . '/sql_debug.log', date('c') . " [findAll] " . $sql . " | params: " . json_encode($params) . PHP_EOL, FILE_APPEND);
        $stmt = $this->conn->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Fetches a paginated list of records with detailed pagination info.
     * @param int $page The current page number (1-indexed).n     * @param int $pageSize The number of records per page.
     * @param array $filters Associative array of filters.
     * @param array $orderBy Associative array for sorting.
     * @param array $selectedColumns Columns to select.
     * @return array An array containing 'data' and 'pagination' info.
     */
    public function findPage($page = 1, $pageSize = 10, $filters = [], $orderBy = [], $selectedColumns = [])
    {
        $params = [];
        $types = '';
        $whereClause = $this->_buildWhereClause($filters, $params, $types, $this->tableName);

        // Get total records count
        $countSql = "SELECT COUNT(*) as total FROM `{$this->tableName}`" . $whereClause;
        $stmt = $this->conn->prepare($countSql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $totalItems = $stmt->get_result()->fetch_assoc()['total'];

        // Calculate pagination details
        $totalPages = ceil($totalItems / $pageSize);
        $offset = ($page - 1) * $pageSize;

        // Get paginated data
        $selectClause = $this->_buildSelectClause($selectedColumns, $this->tableName);
        $orderByClause = $this->_buildOrderByClause($orderBy, $this->tableName);
        $dataSql = "SELECT " . $selectClause . " FROM `{$this->tableName}`" . $whereClause . $orderByClause . " LIMIT ? OFFSET ?";
        
        $dataStmt = $this->conn->prepare($dataSql);
        // We need to rebuild params for the data query
        $dataParams = $params;
        $dataParams[] = $pageSize;
        $dataParams[] = $offset;
        $dataTypes = $types . 'ii'; // Add types for LIMIT and OFFSET
        $dataStmt->bind_param($dataTypes, ...$dataParams);
        $dataStmt->execute();
        $data = $dataStmt->get_result()->fetch_all(MYSQLI_ASSOC);

        return [
            'data' => $data,
            'pagination' => [
                'totalItems' => (int)$totalItems,
                'totalPages' => (int)$totalPages,
                'currentPage' => (int)$page,
                'pageSize' => (int)$pageSize,
                'hasNextPage' => $page < $totalPages,
                'hasPrevPage' => $page > 1
            ]
        ];
    }

    /**
     * Creates a new record in the table.
     * @param array $data An associative array where keys are column names and values are the values to insert.
     * @return bool True on success, false on failure.
     */
    public function create($data)
    {
        $columns = implode(", ", array_map(function ($col) {
            return "`$col`";
        }, array_keys($data)));
        $placeholders = implode(", ", array_fill(0, count($data), '?'));
        $sql = "INSERT INTO `{$this->tableName}` ($columns) VALUES ($placeholders)";

        $stmt = $this->conn->prepare($sql);
        $types = str_repeat('s', count($data));
        $stmt->bind_param($types, ...array_values($data));

        return $stmt->execute();
    }

    /**
     * Updates a record by its primary key.
     * @param mixed $id The ID of the record to update.
     * @param array $data An associative array of columns and their new values.
     * @return bool True on success, false on failure.
     */
    public function update($id, $data)
    {
        return $this->updateWhere([$this->primaryKey => $id], $data);
    }

    /**
     * Updates records based on a filter.
     * @param array $filters An associative array of conditions to select records for update.
     * @param array $data An associative array of columns and their new values.
     * @return bool True on success, false on failure.
     */
    public function updateWhere($filters, $data)
    {
        $setClauses = [];
        $params = [];
        $types = '';

        foreach ($data as $key => $value) {
            $setClauses[] = "`$key` = ?";
            $params[] = $value;
            $types .= 's';
        }
        
        $whereClause = $this->_buildWhereClause($filters, $params, $types, $this->tableName);

        $sql = "UPDATE `{$this->tableName}` SET " . implode(", ", $setClauses) . $whereClause;

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param($types, ...$params);

        return $stmt->execute();
    }

    /**
     * Deletes a record by its primary key.
     * @param mixed $id The ID of the record to delete.
     * @return bool True on success, false on failure.
     */
    public function delete($id)
    {
        return $this->deleteWhere([$this->primaryKey => $id]);
    }

    /**
     * Deletes records based on a filter.
     * @param array $filters An associative array of conditions to select records for deletion.
     * @return bool True on success, false on failure.
     */
    public function deleteWhere($filters)
    {
        $params = [];
        $types = '';
        $whereClause = $this->_buildWhereClause($filters, $params, $types, $this->tableName);

        if (empty($whereClause)) {
            return false; // Safety: Do not allow deleting all records without a WHERE clause
        }

        $sql = "DELETE FROM `{$this->tableName}`" . $whereClause;
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        return $stmt->execute();
    }

    /**
     * Retrieves records formatted for select/dropdown inputs.
     * @param string $valueColumn The column name to use for the 'value' in the frontend.
     * @param string $labelColumn The column name to use for the 'label' in the frontend.
     * @param array $filters Optional filters to apply to the query.
     * @return array An array of associative arrays, each with 'value' and 'label' keys.
     */
    public function getSelectOptions(string $valueColumn = 'id', string $labelColumn = 'name', array $filters = []): array
    {
        $params = [];
        $types = '';
        // Note: _buildWhereClause assumes simple filters directly on the main table for getSelectOptions
        // Need to ensure $this->tableName is correctly passed if filters use qualified names
        $whereClause = $this->_buildWhereClause($filters, $params, $types, $this->tableName);

        $sql = "SELECT `{$valueColumn}`, `{$labelColumn}` FROM `{$this->tableName}`" . $whereClause;
        $stmt = $this->conn->prepare($sql);

        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $options = [];
        while ($row = $result->fetch_assoc()) {
            $options[] = [
                'value' => $row[$valueColumn],
                'label' => $row[$labelColumn]
            ];
        }
        return $options;
    }
}
