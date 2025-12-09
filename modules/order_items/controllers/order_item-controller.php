<?php
require_once __DIR__ . '/../models/order_item.php';
require_once __DIR__ . '/../../books/models/book.php'; // Import BookModel
require_once __DIR__ . '/../../../utils/response.php';

class OrderItemController
{
    private $orderItemModel;
    private $bookModel; // Add BookModel property

    public function __construct()
    {
        $this->orderItemModel = new OrderItemModel();
        $this->bookModel = new BookModel(); // Instantiate BookModel
    }

    /**
     * Danh sách order items (có phân trang + filter)
     */
    public function listOrderItems()
    {
        // Pagination
        $page  = isset($_GET['page']) ? (int) $_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 10;
        $offset = ($page - 1) * $limit;

        // Filtering
        $filters = [];
        if (isset($_GET['order_id'])) {
            $filters['order_id'] = (int) $_GET['order_id'];
        }
        if (isset($_GET['book_id'])) {
            $filters['book_id'] = (int) $_GET['book_id'];
        }

        $orderItems      = $this->orderItemModel->findAll($filters, $limit, $offset);
        $totalOrderItems = $this->orderItemModel->count($filters);

        Response::success([
            'order_items' => $orderItems,
            'total'       => $totalOrderItems,
            'page'        => $page,
            'limit'       => $limit,
        ], 'Order items retrieved successfully.');
    }

    /**
     * Lấy 1 order item theo id
     */
    public function getOrderItem($id)
    {
        if (!is_numeric($id)) {
            Response::error('Invalid order item ID', 400);
            return;
        }

        $orderItem = $this->orderItemModel->find($id);
        if ($orderItem) {
            Response::success(['order_item' => $orderItem], 'Order item retrieved successfully.');
        } else {
            Response::notFound('Order item not found.');
        }
    }

    /**
     * Tạo mới 1 order item
     * Body JSON:
     * {
     *   "order_id": 1,
     *   "book_id": 2,
     *   "quantity": 3,
     *   "price_at_purchase": 100000   // hoặc "price": 100000 (hỗ trợ backward compatible)
     * }
     */
    public function createOrderItem()
    {
        $data = json_decode(file_get_contents('php://input'), true);

        if (!is_array($data)) {
            Response::error('Invalid JSON payload', 400);
            return;
        }

        // Hỗ trợ cả "price" và "price_at_purchase"
        if (isset($data['price']) && !isset($data['price_at_purchase'])) {
            $data['price_at_purchase'] = $data['price'];
            unset($data['price']);
        }

        // Basic validation
        if (empty($data['order_id']) || empty($data['book_id']) || empty($data['quantity']) || empty($data['price_at_purchase'])) {
            Response::error('Missing required fields: order_id, book_id, quantity, price_at_purchase', 400);
            return;
        }

        // Không set created_at / updated_at vì bảng order_items không có các cột này

        if ($newId = $this->orderItemModel->create($data)) {
            $newOrderItem = $this->orderItemModel->find($newId);

            // Decrement book stock
            $book = $this->bookModel->find($data['book_id']);
            if ($book) {
                $newStock = $book['stock_quantity'] - $data['quantity'];
                // Prevent negative stock. A more robust solution would check stock before order creation.
                if ($newStock < 0) $newStock = 0; 

                $this->bookModel->update($data['book_id'], ['stock_quantity' => $newStock]);
            }

            Response::success(['order_item' => $newOrderItem], 'Order item created successfully.', 201);
        } else {
            Response::error('Failed to create order item.', 500);
        }
    }

    /**
     * Cập nhật order item
     */
    public function updateOrderItem($id)
    {
        if (!is_numeric($id)) {
            Response::error('Invalid order item ID', 400);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        if (!is_array($data) || empty($data)) {
            Response::error('No data provided for update', 400);
            return;
        }

        // Hỗ trợ "price" -> "price_at_purchase"
        if (isset($data['price']) && !isset($data['price_at_purchase'])) {
            $data['price_at_purchase'] = $data['price'];
            unset($data['price']);
        }

        $existing = $this->orderItemModel->find($id);
        if (!$existing) {
            Response::notFound('Order item not found.');
            return;
        }

        if ($this->orderItemModel->update($id, $data)) {
            $updated = $this->orderItemModel->find($id);
            Response::success(['order_item' => $updated], 'Order item updated successfully.');
        } else {
            Response::error('Failed to update order item.', 500);
        }
    }

    /**
     * Xoá order item
     */
    public function deleteOrderItem($id)
    {
        if (!is_numeric($id)) {
            Response::error('Invalid order item ID', 400);
            return;
        }

        $existing = $this->orderItemModel->find($id);
        if (!$existing) {
            Response::notFound('Order item not found.');
            return;
        }

        if ($this->orderItemModel->delete($id)) {
            Response::success(null, 'Order item deleted successfully.');
        } else {
            Response::error('Failed to delete order item.', 500);
        }
    }
}
