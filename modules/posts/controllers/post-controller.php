<?php
require_once __DIR__ . '/../models/post.php';
require_once __DIR__ . '/../../../utils/response.php';
require_once __DIR__ . '/../../../utils/file-uploader.php';

class PostController
{
    private $postModel;
    private $fileUploader;

    public function __construct()
    {
        $this->postModel = new PostModel();
        $this->fileUploader = new FileUploader();
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

        $paginationResult = $this->postModel->findPage($page, $limit, $filters);
        
        Response::success([
            'posts' => $paginationResult['data'],
            'total' => $paginationResult['pagination']['totalItems'],
            'page' => $paginationResult['pagination']['currentPage'],
            'limit' => $paginationResult['pagination']['pageSize']
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
        $data = $_POST;

        // Basic validation
        $errors = [];
        if (empty($data['title'])) {
            $errors['title'] = 'Title is required.';
        }
        if (empty($data['content'])) {
            $errors['content'] = 'Content is required.';
        }
        if (empty($data['user_id']) || !is_numeric($data['user_id'])) {
            $errors['user_id'] = 'Valid user_id is required.';
        }

        if (!empty($errors)) {
            Response::validationError($errors);
            return;
        }

        // Handle thumbnail upload
        $imagePath = null;
        if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] === UPLOAD_ERR_OK) {
            $uploaded = $this->fileUploader->upload($_FILES['thumbnail'], 'posts');
            if ($uploaded === false) {
                Response::error('Thumbnail upload failed: ' . implode(', ', $this->fileUploader->getErrors()), 400);
                return;
            }
            $imagePath = $uploaded;
        }

        if (isset($_FILES['thumbnail_image']) && $_FILES['thumbnail_image']['error'] === UPLOAD_ERR_OK) {
            $uploaded = $this->fileUploader->upload($_FILES['thumbnail_image'], 'posts');
            if ($uploaded === false) {
                Response::error('Thumbnail upload failed: ' . implode(', ', $this->fileUploader->getErrors()), 400);
                return;
            }
            $imagePath = $uploaded;
        }

        // Timestamps
        $currentTime = time();
        $data['created_at'] = $currentTime;
        $data['updated_at'] = $currentTime;

        // Assign thumbnail URL if uploaded
        if ($imagePath !== null) {
            $data['thumbnail_image_url'] = $imagePath;
        } else {
            $data['thumbnail_image_url'] = null; // hoặc default placeholder
        }

        // Optional associations
        $data['slug'] = $data['slug'] ?? '';
        $data['meta_title'] = $data['meta_title'] ?? null;
        $data['meta_description'] = $data['meta_description'] ?? null;
        $data['status'] = $data['status'] ?? 'draft'; // or published

        // Insert to DB
        if ($newId = $this->postModel->create($data)) {
            $newPost = $this->postModel->find($newId);

            Response::success(
                ['post' => $newPost],
                'Post created successfully.',
                201
            );
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
