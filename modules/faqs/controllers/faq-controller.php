<?php
require_once __DIR__ . '/../models/faq.php';
require_once __DIR__ . '/../../../utils/response.php';

class FaqController
{
    private $faqModel;

    public function __construct()
    {
        $this->faqModel = new FaqModel();
    }

    /**
     * Handles listing all FAQs with pagination and filtering.
     */
    public function listFaqs()
    {
        // Pagination
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
        $offset = ($page - 1) * $limit;

        // Filtering
        $filters = [];
        if (isset($_GET['search'])) {
            $filters['question LIKE'] = '%' . $_GET['search'] . '%';
        }
        // Add more filters as needed

        $faqs = $this->faqModel->findAll($filters, $limit, $offset);
        $totalFaqs = $this->faqModel->count($filters);

        Response::success([
            'faqs' => $faqs,
            'total' => $totalFaqs,
            'page' => $page,
            'limit' => $limit
        ], 'FAQs retrieved successfully.');
    }

    /**
     * Handles getting a single FAQ by ID.
     * @param int $id The FAQ ID.
     */
    public function getFaq($id)
    {
        if (!is_numeric($id)) {
            Response::error('Invalid FAQ ID', 400);
            return;
        }

        $faq = $this->faqModel->find($id);

        if ($faq) {
            Response::success($faq, 'FAQ retrieved successfully.');
        } else {
            Response::notFound('FAQ not found.');
        }
    }

    /**
     * Handles creating a new FAQ.
     */
    public function createFaq()
    {
        $data = json_decode(file_get_contents('php://input'), true);

        // Basic validation
        if (empty($data['question']) || empty($data['answer'])) {
            Response::error('Missing required fields: question, answer', 400);
            return;
        }

        // Set timestamps
        $currentTime = time();
        $data['created_at'] = $currentTime;
        $data['updated_at'] = $currentTime;

        if ($newFaqId = $this->faqModel->create($data)) {
            $newFaq = $this->faqModel->find($newFaqId);
            Response::success(['faq' => $newFaq], 'FAQ created successfully.', 201);
        } else {
            Response::error('Failed to create FAQ.', 500);
        }
    }

    /**
     * Handles updating an existing FAQ.
     * @param int $id The FAQ ID.
     */
    public function updateFaq($id)
    {
        if (!is_numeric($id)) {
            Response::error('Invalid FAQ ID', 400);
            return;
        }

        $existingFaq = $this->faqModel->find($id);
        if (!$existingFaq) {
            Response::notFound('FAQ not found.');
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data)) {
            Response::error('No data provided for update', 400);
            return;
        }

        // Set updated_at timestamp
        $data['updated_at'] = time();

        if ($this->faqModel->update($id, $data)) {
            $updatedFaq = $this->faqModel->find($id);
            Response::success(['faq' => $updatedFaq], 'FAQ updated successfully.');
        } else {
            Response::error('Failed to update FAQ.', 500);
        }
    }

    /**
     * Handles deleting an FAQ.
     * @param int $id The FAQ ID.
     */
    public function deleteFaq($id)
    {
        if (!is_numeric($id)) {
            Response::error('Invalid FAQ ID', 400);
            return;
        }

        $existingFaq = $this->faqModel->find($id);
        if (!$existingFaq) {
            Response::notFound('FAQ not found.');
            return;
        }

        if ($this->faqModel->delete($id)) {
            Response::success(null, 'FAQ deleted successfully.');
        } else {
            Response::error('Failed to delete FAQ.', 500);
        }
    }
}
