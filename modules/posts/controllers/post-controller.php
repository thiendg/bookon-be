<?php
require_once __DIR__ . '/../models/post.php';
require_once __DIR__ . '/../../../utils/response.php';

class PostController
{
    private $postModel;

    public function __construct()
    {
        $this->postModel = new PostModel();
    }

    /**
     * Handles listing all posts with pagination and filtering.
     */
    public function listPosts()
    {
        // Pagination
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
        $offset = ($page - 1) * $limit;

        // Filtering
        $filters = [];
        if (isset($_GET['search'])) {
            $filters['title LIKE'] = '%' . $_GET['search'] . '%';
        }
        if (isset($_GET['category_id'])) {
            $filters['category_id'] = (int)$_GET['category_id'];
        }
        if (isset($_GET['user_id'])) {
            $filters['user_id'] = (int)$_GET['user_id'];
        }
        // Add more filters as needed

        $posts = $this->postModel->findAll($filters, $limit, $offset);
        $totalPosts = $this->postModel->count($filters);

        Response::success([
            'posts' => $posts,
            'total' => $totalPosts,
            'page' => $page,
            'limit' => $limit
        ], 'Posts retrieved successfully.');
    }

    /**
     * Handles getting a single post by ID.
     * @param int $id The post ID.
     */
    public function getPost($id)
    {
        if (!is_numeric($id)) {
            Response::error('Invalid post ID', 400);
            return;
        }

        $post = $this->postModel->find($id);

        if ($post) {
            Response::success($post, 'Post retrieved successfully.');
        } else {
            Response::notFound('Post not found.');
        }
    }

    /**
     * Handles creating a new post.
     */
    public function createPost()
    {
        $data = json_decode(file_get_contents('php://input'), true);

        // Basic validation
        if (empty($data['title']) || empty($data['content']) || empty($data['user_id'])) {
            Response::error('Missing required fields: title, content, user_id', 400);
            return;
        }

        // Set timestamps
        $currentTime = time();
        $data['created_at'] = $currentTime;
        $data['updated_at'] = $currentTime;

        if ($newPostId = $this->postModel->create($data)) {
            $newPost = $this->postModel->find($newPostId);
            Response::success(['post' => $newPost], 'Post created successfully.', 201);
        } else {
            Response::error('Failed to create post.', 500);
        }
    }

    /**
     * Handles updating an existing post.
     * @param int $id The post ID.
     */
    public function updatePost($id)
    {
        if (!is_numeric($id)) {
            Response::error('Invalid post ID', 400);
            return;
        }

        $existingPost = $this->postModel->find($id);
        if (!$existingPost) {
            Response::notFound('Post not found.');
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data)) {
            Response::error('No data provided for update', 400);
            return;
        }

        // Set updated_at timestamp
        $data['updated_at'] = time();

        if ($this->postModel->update($id, $data)) {
            $updatedPost = $this->postModel->find($id);
            Response::success(['post' => $updatedPost], 'Post updated successfully.');
        } else {
            Response::error('Failed to update post.', 500);
        }
    }

    /**
     * Handles deleting a post.
     * @param int $id The post ID.
     */
    public function deletePost($id)
    {
        if (!is_numeric($id)) {
            Response::error('Invalid post ID', 400);
            return;
        }

        $existingPost = $this->postModel->find($id);
        if (!$existingPost) {
            Response::notFound('Post not found.');
            return;
        }

        if ($this->postModel->delete($id)) {
            Response::success(null, 'Post deleted successfully.');
        } else {
            Response::error('Failed to delete post.', 500);
        }
    }
}
