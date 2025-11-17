<?php
require_once __DIR__ . '/../../../config/databases/base-model.php';

class BookModel extends BaseModel
{
    protected $tableName = 'books';
    protected $primaryKey = 'id';

    public function create($data)
    {
        $currentTime = time();
        $data['created_at'] = $currentTime;
        $data['updated_at'] = $currentTime;

        return parent::create($data);
    }

    /**
     * Overriding update to handle timestamps.
     * @param int $id The book ID to update.
     * @param array $data Book data.
     * @return bool True on success, false on failure.
     */
    public function update($id, $data)
    {
        $data['updated_at'] = time();
        return parent::update($id, $data);
    }
    /**
     * Fetches a single book by ID including its category name.
     * @param int $id The ID of the book to fetch.
     * @return array|null An associative array representing the book, or null if not found.
     */
    public function findBookWithCategory($id)
    {
        $sql = "SELECT books.*, categories.name AS category_name 
                FROM books 
                LEFT JOIN categories ON books.category_id = categories.id 
                WHERE books.id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    /**
     * Fetches a paginated list of books including their category name.
     * @param int $page The current page number (1-indexed).
     * @param int $pageSize The number of records per page.
     * @param array $filters Associative array of filters.
     * @param array $orderBy Associative array for sorting.
     * @return array An array containing 'data' and 'pagination' info.
     */
    public function getBooksWithCategoryName($page = 1, $pageSize = 10, $filters = [], $orderBy = [])
    {
        $params = [];
        $types = '';
        // Use the parent's protected method with a table prefix
        $whereClause = parent::_buildWhereClause($filters, $params, $types, 'books');

        // Get total records count
        $countSql = "SELECT COUNT(books.id) as total FROM books LEFT JOIN categories ON books.category_id = categories.id" . $whereClause;
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
        $orderByClause = parent::_buildOrderByClause($orderBy, 'books'); // Use parent's method with prefix
        $dataSql = "SELECT books.*, categories.name AS category_name FROM books LEFT JOIN categories ON books.category_id = categories.id" . $whereClause . $orderByClause . " LIMIT ? OFFSET ?";
        
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
