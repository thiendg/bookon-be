<?php
require_once __DIR__ . '/../models/category.php';
require_once __DIR__ . '/../../../utils/response.php';

class CategoryController
{
    private $categoryModel;

    public function __construct()
    {
        $this->categoryModel = new CategoryModel();
    }

    /**
     * Handles listing all categories with pagination and filtering.
     */
    public function listCategories()
    {
        // Pagination
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
        $offset = ($page - 1) * $limit;

        // Filtering
        $filters = [];
        if (isset($_GET['search'])) {
            $filters['name LIKE'] = '%' . $_GET['search'] . '%';
        }
        if (isset($_GET['parent_id'])) {
            $filters['parent_id'] = (int)$_GET['parent_id'];
        }
        // Add more filters as needed

        $categories = $this->categoryModel->findAll($filters, $limit, $offset);
        $totalCategories = $this->categoryModel->count($filters);

        Response::success([
            'categories' => $categories,
            'total' => $totalCategories,
            'page' => $page,
            'limit' => $limit
        ], 'Categories retrieved successfully.');
    }

    /**
     * Handles getting a single category by ID.
     * @param int $id The category ID.
     */
    public function getCategory($id)
    {
        if (!is_numeric($id)) {
            Response::error('Invalid category ID', 400);
            return;
        }

        $category = $this->categoryModel->find($id);

        if ($category) {
            Response::success($category, 'Category retrieved successfully.');
        } else {
            Response::notFound('Category not found.');
        }
    }

    /**
     * Handles creating a new category.
     */
    public function createCategory()
    {
        $data = json_decode(file_get_contents('php://input'), true);

        // Basic validation
        if (empty($data['name'])) {
            Response::error('Category name is required.', 400);
            return;
        }

        // Generate slug from name
        $slug = $this->generateSlug($data['name']);
        $data['slug'] = $slug;

        // Set timestamps
        $currentTime = time();
        $data['created_at'] = $currentTime;

        if ($newCategoryId = $this->categoryModel->create($data)) {
            $newCategory = $this->categoryModel->find($newCategoryId);
            Response::success(['category' => $newCategory], 'Category created successfully.', 201);
        } else {
            Response::error('Failed to create category.', 500);
        }
    }

    /**
     * Handles updating an existing category.
     * @param int $id The category ID.
     */
    public function updateCategory($id)
    {
        if (!is_numeric($id)) {
            Response::error('Invalid category ID', 400);
            return;
        }

        $existingCategory = $this->categoryModel->find($id);
        if (!$existingCategory) {
            Response::notFound('Category not found.');
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data)) {
            Response::error('No data provided for update', 400);
            return;
        }

        // If name is updated, regenerate slug
        if (isset($data['name']) && $data['name'] !== $existingCategory['name']) {
            $data['slug'] = $this->generateSlug($data['name']);
        }

        if ($this->categoryModel->update($id, $data)) {
            $updatedCategory = $this->categoryModel->find($id);
            Response::success(['category' => $updatedCategory], 'Category updated successfully.');
        } else {
            Response::error('Failed to update category.', 500);
        }
    }

    /**
     * Handles deleting a category.
     * @param int $id The category ID.
     */
    public function deleteCategory($id)
    {
        if (!is_numeric($id)) {
            Response::error('Invalid category ID', 400);
            return;
        }

        $existingCategory = $this->categoryModel->find($id);
        if (!$existingCategory) {
            Response::notFound('Category not found.');
            return;
        }

        if ($this->categoryModel->delete($id)) {
            Response::success(null, 'Category deleted successfully.');
        } else {
            Response::error('Failed to delete category.', 500);
        }
    }

    /**
     * Generates a URL-friendly slug from a string.
     * @param string $text The input string.
     * @return string The generated slug.
     */
    private function generateSlug(string $text): string
    {
        $text = preg_replace('~[^\pL\d]+~u', '-', $text); // Replace non-alphanumeric with a dash
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text); // Transliterate
        $text = preg_replace('~[^-\w]+~', '', $text); // Remove unwanted characters
        $text = trim($text, '-'); // Trim dashes from beginning and end
        $text = preg_replace('~-+~', '-', $text); // Replace multiple dashes with a single one
        $text = strtolower($text); // Convert to lowercase

        if (empty($text)) {
            return 'n-a';
        }

        // Ensure slug is unique
        $originalSlug = $text;
        $counter = 1;
        while ($this->categoryModel->findOne(['slug' => $text])) {
            $text = $originalSlug . '-' . $counter++;
        }

        return $text;
    }
}
