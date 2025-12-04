<?php
require_once __DIR__ . '/../models/transaction.php';
require_once __DIR__ . '/../../../utils/response.php';

class TransactionController
{
    private $transactionModel;

    public function __construct()
    {
        $this->transactionModel = new TransactionModel();
    }

    /**
     * Handles listing all transactions with pagination and filtering.
     */
    public function listTransactions()
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
        if (isset($_GET['order_id'])) {
            $filters['order_id'] = (int)$_GET['order_id'];
        }
        if (isset($_GET['status'])) {
            $filters['status'] = $_GET['status'];
        }
        // Add more filters as needed

        $paginationResult = $this->transactionModel->findPage($page, $limit, $filters);

        Response::success([
            'posts' => $paginationResult['data'],
            'total' => $paginationResult['pagination']['totalItems'],
            'page' => $paginationResult['pagination']['currentPage'],
            'limit' => $paginationResult['pagination']['pageSize']
        ], 'Transactions retrieved successfully.');
    }

    /**
     * Handles getting a single transaction by ID.
     * @param int $id The transaction ID.
     */
    public function getTransaction($id)
    {
        if (!is_numeric($id)) {
            Response::error('Invalid transaction ID', 400);
            return;
        }

        $transaction = $this->transactionModel->find($id);

        if ($transaction) {
            Response::success($transaction, 'Transaction retrieved successfully.');
        } else {
            Response::notFound('Transaction not found.');
        }
    }

    /**
     * Handles creating a new transaction.
     */
    public function createTransaction()
    {
        $data = json_decode(file_get_contents('php://input'), true);

        // Basic validation
        if (empty($data['user_id']) || empty($data['order_id']) || empty($data['amount']) || empty($data['currency'])) {
            Response::error('Missing required fields: user_id, order_id, amount, currency', 400);
            return;
        }

        // Set timestamps
        $currentTime = time();
        $data['created_at'] = $currentTime;
        $data['updated_at'] = $currentTime;
        $data['status'] = 'pending'; // Default status for new transactions

        if ($newTransactionId = $this->transactionModel->create($data)) {
            $newTransaction = $this->transactionModel->find($newTransactionId);
            Response::success(['transaction' => $newTransaction], 'Transaction created successfully.', 201);
        } else {
            Response::error('Failed to create transaction.', 500);
        }
    }

    /**
     * Handles updating an existing transaction.
     * @param int $id The transaction ID.
     */
    public function updateTransaction($id)
    {
        if (!is_numeric($id)) {
            Response::error('Invalid transaction ID', 400);
            return;
        }

        $existingTransaction = $this->transactionModel->find($id);
        if (!$existingTransaction) {
            Response::notFound('Transaction not found.');
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data)) {
            Response::error('No data provided for update', 400);
            return;
        }

        // Set updated_at timestamp
        $data['updated_at'] = time();

        if ($this->transactionModel->update($id, $data)) {
            $updatedTransaction = $this->transactionModel->find($id);
            Response::success(['transaction' => $updatedTransaction], 'Transaction updated successfully.');
        } else {
            Response::error('Failed to update transaction.', 500);
        }
    }

    /**
     * Handles deleting a transaction.
     * @param int $id The transaction ID.
     */
    public function deleteTransaction($id)
    {
        if (!is_numeric($id)) {
            Response::error('Invalid transaction ID', 400);
            return;
        }

        $existingTransaction = $this->transactionModel->find($id);
        if (!$existingTransaction) {
            Response::notFound('Transaction not found.');
            return;
        }

        if ($this->transactionModel->delete($id)) {
            Response::success(null, 'Transaction deleted successfully.');
        } else {
            Response::error('Failed to delete transaction.', 500);
        }
    }
}
