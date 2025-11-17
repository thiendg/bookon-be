<?php
require_once __DIR__ . '/../models/order_review.php';
require_once __DIR__ . '/../../../utils/response.php';

class OrderReviewController
{
    private $orderReviewModel;

    public function __construct()
    {
        $this->orderReviewModel = new OrderReviewModel();
    }

    /**
     * Handles listing all order reviews with pagination and filtering.
     */
    public function listOrderReviews()
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
        if (isset($_GET['user_id'])) {
            $filters['user_id'] = (int)$_GET['user_id'];
        }
        if (isset($_GET['rating'])) {
            $filters['rating'] = (int)$_GET['rating'];
        }
        // Add more filters as needed

        $orderReviews = $this->orderReviewModel->findAll($filters, $limit, $offset);
        $totalOrderReviews = $this->orderReviewModel->count($filters);

        Response::success([
            'order_reviews' => $orderReviews,
            'total' => $totalOrderReviews,
            'page' => $page,
            'limit' => $limit
        ], 'Order reviews retrieved successfully.');
    }

    /**
     * Handles getting a single order review by ID.
     * @param int $id The order review ID.
     */
    public function getOrderReview($id)
    {
        if (!is_numeric($id)) {
            Response::error('Invalid order review ID', 400);
            return;
        }

        $orderReview = $this->orderReviewModel->find($id);

        if ($orderReview) {
            Response::success($orderReview, 'Order review retrieved successfully.');
        } else {
            Response::notFound('Order review not found.');
        }
    }

    /**
     * Handles creating a new order review.
     */
    public function createOrderReview()
    {
        $data = json_decode(file_get_contents('php://input'), true);

        // Basic validation
        if (empty($data['order_id']) || empty($data['user_id']) || empty($data['rating'])) {
            Response::error('Missing required fields: order_id, user_id, rating', 400);
            return;
        }

        // Set timestamps
        $currentTime = time();
        $data['created_at'] = $currentTime;
        $data['updated_at'] = $currentTime;

        if ($newOrderReviewId = $this->orderReviewModel->create($data)) {
            $newOrderReview = $this->orderReviewModel->find($newOrderReviewId);
            Response::success(['order_review' => $newOrderReview], 'Order review created successfully.', 201);
        } else {
            Response::error('Failed to create order review.', 500);
        }
    }

    /**
     * Handles updating an existing order review.
     * @param int $id The order review ID.
     */
    public function updateOrderReview($id)
    {
        if (!is_numeric($id)) {
            Response::error('Invalid order review ID', 400);
            return;
        }

        $existingOrderReview = $this->orderReviewModel->find($id);
        if (!$existingOrderReview) {
            Response::notFound('Order review not found.');
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data)) {
            Response::error('No data provided for update', 400);
            return;
        }

        // Set updated_at timestamp
        $data['updated_at'] = time();

        if ($this->orderReviewModel->update($id, $data)) {
            $updatedOrderReview = $this->orderReviewModel->find($id);
            Response::success(['order_review' => $updatedOrderReview], 'Order review updated successfully.');
        } else {
            Response::error('Failed to update order review.', 500);
        }
    }

    /**
     * Handles deleting an order review.
     * @param int $id The order review ID.
     */
    public function deleteOrderReview($id)
    {
        if (!is_numeric($id)) {
            Response::error('Invalid order review ID', 400);
            return;
        }

        $existingOrderReview = $this->orderReviewModel->find($id);
        if (!$existingOrderReview) {
            Response::notFound('Order review not found.');
            return;
        }

        if ($this->orderReviewModel->delete($id)) {
            Response::success(null, 'Order review deleted successfully.');
        } else {
            Response::error('Failed to delete order review.', 500);
        }
    }
}
