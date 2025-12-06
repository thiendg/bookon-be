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

        // Ensure numeric fields are returned as ints for consistency
        if (is_array($data) && !empty($data)) {
            foreach ($data as &$row) {
                if (isset($row['sold_count'])) {
                    $row['sold_count'] = (int) $row['sold_count'];
                }
            }
            unset($row);
        }

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
     * Fetches a paginated list of books including their category name and
     * the number of items sold (sum of order_items.quantity).
     * By default, only orders with status in ('processing','shipped','completed')
     * are counted. Adjust the $countOrderStatuses param if you want different logic.
     *
     * @param int $page
     * @param int $pageSize
     * @param array $filters
     * @param array $orderBy
     * @param array $countOrderStatuses
     * @return array
     */
    public function getBooksWithSales($page = 1, $pageSize = 10, $filters = [], $orderBy = [], $countOrderStatuses = ['shipped','completed'])
    {
        $params = [];
        $types = '';

        // Build where clause using parent's helper (prefix with books)
        $whereClause = parent::_buildWhereClause($filters, $params, $types, 'books');

        // Prepare a statuses list for the JOIN condition (we'll bind none, use literals safely)
        $escapedStatuses = array_map(function($s) {
            return "'" . $this->conn->real_escape_string($s) . "'";
        }, $countOrderStatuses);
        $statusesList = implode(',', $escapedStatuses);

        // Count total distinct books matching filters and having orders with allowed statuses
        $countSql = "SELECT COUNT(DISTINCT books.id) as total FROM books "
              . "LEFT JOIN categories ON books.category_id = categories.id "
              . "INNER JOIN order_items ON order_items.book_id = books.id "
              . "INNER JOIN orders ON order_items.order_id = orders.id AND orders.status IN ($statusesList) "
              . $whereClause;

        $stmt = $this->conn->prepare($countSql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $totalItems = $stmt->get_result()->fetch_assoc()['total'];

        // Pagination calculations
        $totalPages = ceil($totalItems / $pageSize);
        $offset = ($page - 1) * $pageSize;

        // Data query with sold count
        $orderByClause = parent::_buildOrderByClause($orderBy, 'books');
        // Sum only order_items that belong to orders with allowed statuses using CASE WHEN.
           $dataSql = "SELECT books.*, categories.name AS category_name, "
               . "COALESCE(SUM(CASE WHEN orders.status IN ($statusesList) THEN order_items.quantity ELSE 0 END), 0) AS sold_count "
               . "FROM books "
               . "LEFT JOIN categories ON books.category_id = categories.id "
               . "LEFT JOIN order_items ON order_items.book_id = books.id "
               . "LEFT JOIN orders ON order_items.order_id = orders.id "
               . $whereClause
               . " GROUP BY books.id "
               . " HAVING COALESCE(SUM(CASE WHEN orders.status IN ($statusesList) THEN order_items.quantity ELSE 0 END), 0) > 0 "
               . $orderByClause
               . " LIMIT ? OFFSET ?";

        $dataStmt = $this->conn->prepare($dataSql);
        $dataParams = $params;
        $dataParams[] = $pageSize;
        $dataParams[] = $offset;
        $dataTypes = $types . 'ii';
        $dataStmt->bind_param($dataTypes, ...$dataParams);
        $dataStmt->execute();
        $data = $dataStmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // Cast sold_count to integer for consistency
        if (is_array($data) && !empty($data)) {
            foreach ($data as &$row) {
                if (isset($row['sold_count'])) {
                    $row['sold_count'] = (int) $row['sold_count'];
                }
            }
            unset($row);
        }

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
