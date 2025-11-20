<?php
require_once __DIR__ . '/../models/book.php';
require_once __DIR__ . '/../../../utils/response.php';
require_once __DIR__ . '/../../../utils/file-uploader.php'; // File Uploader utility

class BookController
{
    private $bookModel;
    private $fileUploader;

    public function __construct()
    {
        $this->bookModel = new BookModel();
        $this->fileUploader = new FileUploader();
    }

    /**
     * Handles listing all books with pagination, filtering, and sorting.
     */
    public function listBooks()
    {
        // Pagination
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $pageSize = isset($_GET['pageSize']) ? (int)$_GET['pageSize'] : 10;

        // Filtering
        $filters = [];
        if (isset($_GET['category_id'])) {
            $filters['books.category_id'] = (int)$_GET['category_id'];
        }
        if (isset($_GET['search'])) {
            $filters['books.title LIKE'] = '%' . $_GET['search'] . '%';
        }
        // Add more filters as needed

        // Ordering
        $orderBy = [];
        if (isset($_GET['sortBy']) && isset($_GET['sortOrder'])) {
            $orderBy[$_GET['sortBy']] = $_GET['sortOrder'];
        }

        $result = $this->bookModel->getBooksWithCategoryName($page, $pageSize, $filters, $orderBy);

        Response::success($result, 'Books retrieved successfully.');
    }

    /**
     * Handles getting a single book by ID.
     * @param int $id The book ID.
     */
    public function getBook($id)
    {
        if (!is_numeric($id)) {
            Response::error('Invalid book ID', 400);
            return;
        }

        $book = $this->bookModel->findBookWithCategory($id);

        if ($book) {
            Response::success($book, 'Book retrieved successfully.');
        } else {
            Response::notFound('Book not found.');
        }
    }

    /**
     * Handles creating a new book.
     */
    public function createBook()
    {
        // Book data from POST fields
        $bookData = $_POST;

        // Basic validation for book data
        $errors = [];
        if (empty($bookData['title'])) {
            $errors['title'] = 'Book title is required.';
        }
        if (empty($bookData['description'])) {
            $errors['description'] = 'Book description is required.';
        }
        if (!isset($bookData['price']) || !is_numeric($bookData['price']) || $bookData['price'] < 0) {
            $errors['price'] = 'Valid price is required.';
        }
        // Add more validation rules as needed

        if (!empty($errors)) {
            Response::validationError($errors);
        }

        $imagePaths = [];
        // Handle multiple image uploads if 'images' field is present in $_FILES
        if (isset($_FILES['images']) && is_array($_FILES['images'])) {
            $uploadedImages = $this->fileUploader->uploadMultiple($_FILES['images'], 'books'); // Upload to 'public/uploads/books'
            if (!empty($this->fileUploader->getErrors())) {
                Response::error('Image upload failed: ' . implode(', ', $this->fileUploader->getErrors()), 400);
            }
            $imagePaths = $uploadedImages;
        }

        // Prepare book data for database
        $bookDbData = [
            'title' => $bookData['title'],
            'description' => $bookData['description'],
            'price' => $bookData['price'],
            'stock_quantity' => $bookData['stock_quantity'] ?? 0,
            'category_id' => $bookData['category_id'] ?? null,
            'author' => $bookData['author'] ?? null,
            'publisher' => $bookData['publisher'] ?? null,
            'publication_year' => $bookData['publication_year'] ?? null,
            'slug' => $bookData['slug'] ?? ''
        ];

        if (!empty($imagePaths)) {
            $bookDbData['cover_image_url'] = $imagePaths[0]; // Use the first image as cover
        } else {
            $bookDbData['cover_image_url'] = 'default_book_cover.png'; // Default image if none uploaded
        }

        if ($newBookId = $this->bookModel->create($bookDbData)) {
            $newBook = $this->bookModel->find($newBookId);
            Response::success(['book' => $newBook], 'Book created successfully.', 201);
        } else {
            Response::error('Failed to create book.', 500);
        }
    }

    /**
     * Handles updating an existing book.
     * @param int $id The book ID.
     */
    public function updateBook($id)
    {
        if (!is_numeric($id)) {
            Response::error('Invalid book ID', 400);
            return;
        }

        $existingBook = $this->bookModel->find($id);
        if (!$existingBook) {
            Response::notFound('Book not found.');
            return;
        }

        // Book data from POST fields (for PUT with form-data)
        $bookData = $_POST;

        // Basic validation for book data
        $errors = [];
        if (empty($bookData['title'])) {
            $errors['title'] = 'Book title is required.';
        }
        if (empty($bookData['description'])) {
            $errors['description'] = 'Book description is required.';
        }
        if (!isset($bookData['price']) || !is_numeric($bookData['price']) || $bookData['price'] < 0) {
            $errors['price'] = 'Valid price is required.';
        }
        // Add more validation rules as needed

        if (!empty($errors)) {
            Response::validationError($errors);
        }

        $imagePaths = [];
        // Handle multiple image uploads if 'images' field is present in $_FILES
        if (isset($_FILES['images']) && is_array($_FILES['images'])) {
            $uploadedImages = $this->fileUploader->uploadMultiple($_FILES['images'], 'books'); // Upload to 'public/uploads/books'
            if (!empty($this->fileUploader->getErrors())) {
                Response::error('Image upload failed: ' . implode(', ', $this->fileUploader->getErrors()), 400);
            }
            $imagePaths = $uploadedImages;
        }

        // Prepare book data for database
        $bookDbData = [
            'title' => $bookData['title'],
            'description' => $bookData['description'],
            'price' => $bookData['price'],
            'stock_quantity' => $bookData['stock_quantity'] ?? $existingBook['stock_quantity'],
            'category_id' => $bookData['category_id'] ?? $existingBook['category_id'],
            'author' => $bookData['author'] ?? $existingBook['author'],
            'publisher' => $bookData['publisher'] ?? $existingBook['publisher'],
            'publication_year' => $bookData['publication_year'] ?? $existingBook['publication_year'],
        ];

        if (!empty($imagePaths)) {
            $bookDbData['cover_image_url'] = $imagePaths[0]; // Use the first uploaded image as cover
        } else {
            $bookDbData['cover_image_url'] = $existingBook['cover_image_url']; // Retain existing if no new image
        }

        if ($this->bookModel->update($id, $bookDbData)) {
            $updatedBook = $this->bookModel->find($id);
            Response::success(['book' => $updatedBook], 'Book updated successfully.');
        } else {
            Response::error('Failed to update book.', 500);
        }
    }

    /**
     * Handles deleting a book.
     * @param int $id The book ID.
     */
    public function deleteBook($id)
    {
        if (!is_numeric($id)) {
            Response::error('Invalid book ID', 400);
            return;
        }

        $existingBook = $this->bookModel->find($id);
        if (!$existingBook) {
            Response::notFound('Book not found.');
            return;
        }

        // Optionally, delete associated image files here
        // For now, just delete the database record

        if ($this->bookModel->delete($id)) {
            Response::success(null, 'Book deleted successfully.');
        } else {
            Response::error('Failed to delete book.', 500);
        }
    }
}
