<?php
require_once __DIR__ . '/../models/review.php';
require_once __DIR__ . '/../../../utils/response.php';

class ReviewController
{
    private $reviewModel;

    public function __construct()
    {
        $this->reviewModel = new ReviewModel();
    }

    /**
     * Handles listing all reviews with pagination and filtering.
     */
    public function listReviews()
    {
        // Pagination
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
        $offset = ($page - 1) * $limit;

        // Filtering
        $filters = [];
        if (isset($_GET['book_id'])) {
            $filters['book_id'] = (int)$_GET['book_id'];
        }
        if (isset($_GET['user_id'])) {
            $filters['user_id'] = (int)$_GET['user_id'];
        }
        if (isset($_GET['rating'])) {
            $filters['rating'] = (int)$_GET['rating'];
        }
        // Add more filters as needed

        $reviews = $this->reviewModel->findAll($filters, $limit, $offset);
        $totalReviews = $this->reviewModel->count($filters);

        Response::success([
            'reviews' => $reviews,
            'total' => $totalReviews,
            'page' => $page,
            'limit' => $limit
        ], 'Reviews retrieved successfully.');
    }

    /**
     * Handles getting a single review by ID.
     * @param int $id The review ID.
     */
    public function getReview($id)
    {
        if (!is_numeric($id)) {
            Response::error('Invalid review ID', 400);
            return;
        }

        $review = $this->reviewModel->find($id);

        if ($review) {
            Response::success($review, 'Review retrieved successfully.');
        } else {
            Response::notFound('Review not found.');
        }
    }

    /**
     * Handles creating a new review.
     */
    public function createReview()
    {
        $data = json_decode(file_get_contents('php://input'), true);

        // Basic validation
        if (empty($data['book_id']) || empty($data['user_id']) || empty($data['rating']) || empty($data['comment'])) {
            Response::error('Missing required fields: book_id, user_id, rating, comment', 400);
            return;
        }
        if (!is_numeric($data['rating']) || $data['rating'] < 1 || $data['rating'] > 5) {
            Response::error('Rating must be between 1 and 5.', 400);
            return;
        }

        // Set timestamps
        $currentTime = time();
        $data['created_at'] = $currentTime;
        $data['updated_at'] = $currentTime;

        if ($newReviewId = $this->reviewModel->create($data)) {
            $newReview = $this->reviewModel->find($newReviewId);
            Response::success(['review' => $newReview], 'Review created successfully.', 201);
        } else {
            Response::error('Failed to create review.', 500);
        }
    }

    /**
     * Handles updating an existing review.
     * @param int $id The review ID.
     */
    public function updateReview($id)
    {
        if (!is_numeric($id)) {
            Response::error('Invalid review ID', 400);
            return;
        }

        $existingReview = $this->reviewModel->find($id);
        if (!$existingReview) {
            Response::notFound('Review not found.');
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data)) {
            Response::error('No data provided for update', 400);
            return;
        }

        // Basic validation for rating if provided
        if (isset($data['rating']) && (!is_numeric($data['rating']) || $data['rating'] < 1 || $data['rating'] > 5)) {
            Response::error('Rating must be between 1 and 5.', 400);
            return;
        }

        // Set updated_at timestamp
        $data['updated_at'] = time();

        if ($this->reviewModel->update($id, $data)) {
            $updatedReview = $this->reviewModel->find($id);
            Response::success(['review' => $updatedReview], 'Review updated successfully.');
        } else {
            Response::error('Failed to update review.', 500);
        }
    }

    /**
     * Handles deleting a review.
     * @param int $id The review ID.
     */
    public function deleteReview($id)
    {
        if (!is_numeric($id)) {
            Response::error('Invalid review ID', 400);
            return;
        }

        $existingReview = $this->reviewModel->find($id);
        if (!$existingReview) {
            Response::notFound('Review not found.');
            return;
        }

        if ($this->reviewModel->delete($id)) {
            Response::success(null, 'Review deleted successfully.');
        } else {
            Response::error('Failed to delete review.', 500);
        }
    }
}