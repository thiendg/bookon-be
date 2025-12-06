<?php
require_once __DIR__ . '/../models/post_comment.php';
require_once __DIR__ . '/../../../utils/response.php';

class PostCommentController
{
    private $postCommentModel;

    public function __construct()
    {
        $this->postCommentModel = new PostCommentModel();
    }

    /**
     * Handles listing all post comments with pagination and filtering.
     */
    public function listPostComments()
    {
        // Pagination
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
        $offset = ($page - 1) * $limit;

        // Filtering
        $filters = [];
        if (isset($_GET['post_id'])) {
            $filters['post_id'] = (int)$_GET['post_id'];
        }
        if (isset($_GET['user_id'])) {
            $filters['user_id'] = (int)$_GET['user_id'];
        }
        // Add more filters as needed

        $paginationResult = $this->postCommentModel->findPage($page, $limit, $filters);

        Response::success([
            'posts' => $paginationResult['data'],
            'total' => $paginationResult['pagination']['totalItems'],
            'page' => $paginationResult['pagination']['currentPage'],
            'limit' => $paginationResult['pagination']['pageSize']
        ], 'PostComment retrieved successfully.');
    }

    /**
     * Handles getting a single post comment by ID.
     * @param int $id The post comment ID.
     */
    public function getPostComment($id)
    {
        if (!is_numeric($id)) {
            Response::error('Invalid post comment ID', 400);
            return;
        }

        $postComment = $this->postCommentModel->find($id);

        if ($postComment) {
            Response::success($postComment, 'Post comment retrieved successfully.');
        } else {
            Response::notFound('Post comment not found.');
        }
    }

    /**
     * Handles creating a new post comment.
     */
    public function createPostComment()
    {
        $data = json_decode(file_get_contents('php://input'), true);
    @file_put_contents(__DIR__ . '/comments.log', date('c') . 'Comment_data: ' . 'post_id: ' . $data['post_id'] . ' user_id: ' . $data['user_id'] . ' content: ' . $data['content'] . PHP_EOL, FILE_APPEND);
        // Basic validation
        if (empty($data['post_id']) || empty($data['user_id']) || empty($data['content'])) {
            Response::error('Missing required fields: post_id, user_id, content', 400);
            return;
        }

        // Set timestamps
        $currentTime = time();
        $data['created_at'] = $currentTime;

        if ($newPostCommentId = $this->postCommentModel->create($data)) {
            $newPostComment = $this->postCommentModel->find($newPostCommentId);
            Response::success(['post_comment' => $newPostComment], 'Post comment created successfully.', 201);
        } else {
            Response::error('Failed to create post comment.', 500);
        }
    }

    /**
     * Handles updating an existing post comment.
     * @param int $id The post comment ID.
     */
    public function updatePostComment($id)
    {
        if (!is_numeric($id)) {
            Response::error('Invalid post comment ID', 400);
            return;
        }

        $existingPostComment = $this->postCommentModel->find($id);
        if (!$existingPostComment) {
            Response::notFound('Post comment not found.');
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data)) {
            Response::error('No data provided for update', 400);
            return;
        }

        // Set updated_at timestamp
        $data['updated_at'] = time();

        if ($this->postCommentModel->update($id, $data)) {
            $updatedPostComment = $this->postCommentModel->find($id);
            Response::success(['post_comment' => $updatedPostComment], 'Post comment updated successfully.');
        } else {
            Response::error('Failed to update post comment.', 500);
        }
    }

    /**
     * Handles deleting a post comment.
     * @param int $id The post comment ID.
     */
    public function deletePostComment($id)
    {
        if (!is_numeric($id)) {
            Response::error('Invalid post comment ID', 400);
            return;
        }

        $existingPostComment = $this->postCommentModel->find($id);
        if (!$existingPostComment) {
            Response::notFound('Post comment not found.');
            return;
        }

        if ($this->postCommentModel->delete($id)) {
            Response::success(null, 'Post comment deleted successfully.');
        } else {
            Response::error('Failed to delete post comment.', 500);
        }
    }
}
