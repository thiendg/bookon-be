<?php
require_once __DIR__ . '/../models/order.php';
require_once __DIR__ . '/../../../utils/response.php';

class OrderController
{
    private $orderModel;

    public function __construct()
    {
        $this->orderModel = new OrderModel();
    }

    /**
     * Handles listing all orders with pagination and filtering.
     */
    public function listOrders()
    {
        // Pagination
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
        $offset = ($page - 1) * $limit;

        // Filtering
        $filters = [];
        if (isset($_GET['user_id'])) {
            $filters['user_id'] = (int)$_GET['user_id'];
        }
        if (isset($_GET['status'])) {
            $filters['status'] = $_GET['status'];
        }
        // Add more filters as needed

        $paginationResult = $this->orderModel->findPage($page, $limit, $filters);

        Response::success([
            'posts' => $paginationResult['data'],
            'total' => $paginationResult['pagination']['totalItems'],
            'page' => $paginationResult['pagination']['currentPage'],
            'limit' => $paginationResult['pagination']['pageSize']
        ], 'Orders retrieved successfully.');
    }

    /**
     * Handles getting a single order by ID.
     * @param int $id The order ID.
     */
    public function getOrder($id)
    {
        if (!is_numeric($id)) {
            Response::error('Invalid order ID', 400);
            return;
        }

        $order = $this->orderModel->find($id);

        if ($order) {
            Response::success($order, 'Order retrieved successfully.');
        } else {
            Response::notFound('Order not found.');
        }
    }

    /**
     * Handles creating a new order.
     */
    public function createOrder()
    {
        $data = json_decode(file_get_contents('php://input'), true);

        // Basic validation
        if (empty($data['user_id']) || empty($data['total_amount'])) {
            Response::error('Missing required fields: user_id, total_amount', 400);
            return;
        }

        // Set timestamps
        $currentTime = time();
        $data['created_at'] = $currentTime;
        $data['updated_at'] = $currentTime;
        $data['status'] = 'pending'; // Default status for new orders

        if ($newOrderId = $this->orderModel->create($data)) {
            $newOrder = $this->orderModel->find($newOrderId);
            Response::success(['order' => $newOrder], 'Order created successfully.', 201);
        } else {
            Response::error('Failed to create order.', 500);
        }
    }

    /**
     * Handles updating an existing order.
     * @param int $id The order ID.
     */
    public function updateOrder($id)
    {
        if (!is_numeric($id)) {
            Response::error('Invalid order ID', 400);
            return;
        }

        $existingOrder = $this->orderModel->find($id);
        if (!$existingOrder) {
            Response::notFound('Order not found.');
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data)) {
            Response::error('No data provided for update', 400);
            return;
        }

        // Set updated_at timestamp
        $data['updated_at'] = time();

        if ($this->orderModel->update($id, $data)) {
            $updatedOrder = $this->orderModel->find($id);
            Response::success(['order' => $updatedOrder], 'Order updated successfully.');
        } else {
            Response::error('Failed to update order.', 500);
        }
    }

    /**
     * Handles deleting an order.
     * @param int $id The order ID.
     */
    public function deleteOrder($id)
    {
        if (!is_numeric($id)) {
            Response::error('Invalid order ID', 400);
            return;
        }

        $existingOrder = $this->orderModel->find($id);
        if (!$existingOrder) {
            Response::notFound('Order not found.');
            return;
            return;
        }

        if ($this->orderModel->delete($id)) {
            Response::success(null, 'Order deleted successfully.');
        } else {
            Response::error('Failed to delete order.', 500);
        }
    }
}
