<?php
require_once __DIR__ . '/../../../config/databases/base-model.php';

class UserModel extends BaseModel
{
    protected $tableName = 'users';

    /**
     * Overriding create to handle password hashing and timestamps.
     * @param array $data User data. 'password' will be hashed.
     * @return bool True on success, false on failure.
     */
    public function create($data)
    {
        // Hash the password if it is provided
        if (isset($data['password'])) {
            $data['password_hash'] = password_hash($data['password'], PASSWORD_DEFAULT);
            unset($data['password']); // Don't store plain password
        }

        // Set timestamps
        $currentTime = time();
        $data['created_at'] = $currentTime;
        $data['updated_at'] = $currentTime;

        return parent::create($data);
    }

    /**
     * Overriding update to handle password hashing and timestamps.
     * @param int $id The user ID to update.
     * @param array $data User data. 'password' will be hashed if provided.
     * @return bool True on success, false on failure.
     */
    public function update($id, $data)
    {
        // Hash the password if it is provided
        if (isset($data['password'])) {
            $data['password_hash'] = password_hash($data['password'], PASSWORD_DEFAULT);
            unset($data['password']);
        }

        // Set timestamp
        $data['updated_at'] = time();

        return parent::update($id, $data);
    }

    /**
     * Fetches a single user by ID including their role name.
     * @param int $id The ID of the user to fetch.
     * @return array|null An associative array representing the user, or null if not found.
     */
    public function findUserWithRole($id)
    {
        $sql = "SELECT users.*, roles.name AS role_name 
                FROM users 
                LEFT JOIN roles ON users.role_id = roles.id 
                WHERE users.id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    /**
     * Fetches a paginated list of users including their role name.
     * @param int $page The current page number (1-indexed).
     * @param int $pageSize The number of records per page.
     * @param array $filters Associative array of filters.
     * @param array $orderBy Associative array for sorting.
     * @return array An array containing 'data' and 'pagination' info.
     */
    public function getUsersWithRoleName($page = 1, $pageSize = 10, $filters = [], $orderBy = [])
    {
        $params = [];
        $types = '';
        // Use the parent's protected method with a table prefix
        $whereClause = parent::_buildWhereClause($filters, $params, $types, 'users');

        // Get total records count
        $countSql = "SELECT COUNT(users.id) as total FROM users LEFT JOIN roles ON users.role_id = roles.id" . $whereClause;
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
        $orderByClause = parent::_buildOrderByClause($orderBy, 'users'); // Use parent's method with prefix
        $dataSql = "SELECT users.*, roles.name AS role_name FROM users LEFT JOIN roles ON users.role_id = roles.id" . $whereClause . $orderByClause . " LIMIT ? OFFSET ?";
        
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
}
