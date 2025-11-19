<?php
require_once __DIR__ . '/../models/order_item.php';
require_once __DIR__ . '/../../../utils/response.php';

class OrderItemController
{
    private $orderItemModel;

    public function __construct()
    {
        $this->orderItemModel = new OrderItemModel();
    }

    /**
     * Handles listing all order items with pagination and filtering.
     */
    public function listOrderItems()
    {
        // Pagination
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
        $offset = ($page - 1) * $limit;

        // Filtering
        $filters = [];
        if (isset($_GET['order_id'])) {
            $filters['order_id'] = (int)$_GET['order_id'];
        }
        if (isset($_GET['book_id'])) {
            $filters['book_id'] = (int)$_GET['book_id'];
        }
        // Add more filters as needed

        $orderItems = $this->orderItemModel->findAll($filters, $limit, $offset);
        $totalOrderItems = $this->orderItemModel->count($filters);

        Response::success([
            'order_items' => $orderItems,
            'total' => $totalOrderItems,
            'page' => $page,
            'limit' => $limit
        ], 'Order items retrieved successfully.');
    }

    /**
     * Handles getting a single order item by ID.
     * @param int $id The order item ID.
     */
    public function getOrderItem($id)
    {
        if (!is_numeric($id)) {
            Response::error('Invalid order item ID', 400);
            return;
        }

        $orderItem = $this->orderItemModel->find($id);

        if ($orderItem) {
            Response::success($orderItem, 'Order item retrieved successfully.');
        } else {
            Response::notFound('Order item not found.');
        }
    }

    /**
     * Handles creating a new order item.
     */
    public function createOrderItem()
    {
        $data = json_decode(file_get_contents('php://input'), true);

        // Basic validation
        if (empty($data['order_id']) || empty($data['book_id']) || empty($data['quantity']) || empty($data['price'])) {
            Response::error('Missing required fields: order_id, book_id, quantity, price', 400);
            return;
        }

        // Set timestamps
        $currentTime = time();
        $data['created_at'] = $currentTime;
        $data['updated_at'] = $currentTime;

        if ($newOrderItemId = $this->orderItemModel->create($data)) {
            $newOrderItem = $this->orderItemModel->find($newOrderItemId);
            Response::success(['order_item' => $newOrderItem], 'Order item created successfully.', 201);
        } else {
            Response::error('Failed to create order item.', 500);
        }
    }

    /**
     * Handles updating an existing order item.
     * @param int $id The order item ID.
     */
    public function updateOrderItem($id)
    {
        if (!is_numeric($id)) {
            Response::error('Invalid order item ID', 400);
            return;
        }

        $existingOrderItem = $this->orderItemModel->find($id);
        if (!$existingOrderItem) {
            Response::notFound('Order item not found.');
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data)) {
            Response::error('No data provided for update', 400);
            return;
        }

        // Set updated_at timestamp
        $data['updated_at'] = time();

        if ($this->orderItemModel->update($id, $data)) {
            $updatedOrderItem = $this->orderItemModel->find($id);
            Response::success(['order_item' => $updatedOrderItem], 'Order item updated successfully.');
        } else {
            Response::error('Failed to update order item.', 500);
        }
    }

    /**
     * Handles deleting an order item.
     * @param int $id The order item ID.
     */
    public function deleteOrderItem($id)
    {
        if (!is_numeric($id)) {
            Response::error('Invalid order item ID', 400);
            return;
        }

        $existingOrderItem = $this->orderItemModel->find($id);
        if (!$existingOrderItem) {
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
