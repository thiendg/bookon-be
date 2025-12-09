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

        // More accurate validation based on DB schema
        $errors = [];
        if (!isset($data['total_amount'])) {
            $errors['total_amount'] = 'Total amount is required.';
        }
        if (empty($data['customer_name'])) {
            $errors['customer_name'] = 'Customer name is required.';
        }
        if (empty($data['customer_email'])) {
            $errors['customer_email'] = 'Customer email is required.';
        }
        if (empty($data['customer_phone'])) {
            $errors['customer_phone'] = 'Customer phone is required.';
        }
        if (empty($data['shipping_address'])) {
            $errors['shipping_address'] = 'Shipping address is required.';
        }

        if (!empty($errors)) {
            Response::validationError($errors, 'Missing required fields.');
            return;
        }

        // Ensure user_id is null if not provided, allowing guest checkout
        if (empty($data['user_id'])) {
            $data['user_id'] = null;
        }

        // Set timestamps and default status
        $data['created_at'] = time();
        $data['status'] = 'pending';

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
        // $data['updated_at'] = time();

        $affectedRows = $this->orderModel->update($id, $data);

        if ($affectedRows > 0) {
            $updatedOrder = $this->orderModel->find($id);
            Response::success(['order' => $updatedOrder], 'Order status updated successfully.');
        } elseif ($affectedRows === 0) {
            $updatedOrder = $this->orderModel->find($id);
            Response::success(['order' => $updatedOrder], 'Order status was already set. No changes made.');
        } else {
            Response::error('A database error occurred while updating the order.', 500);
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
