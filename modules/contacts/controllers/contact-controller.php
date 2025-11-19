<?php
require_once __DIR__ . '/../models/contact.php';
require_once __DIR__ . '/../../../utils/response.php';

class ContactController
{
    private $contactModel;

    public function __construct()
    {
        $this->contactModel = new ContactModel();
    }

    /**
     * Handles listing all contacts with pagination and filtering.
     */
    public function listContacts()
    {
        // Pagination
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
        $offset = ($page - 1) * $limit;

        // Filtering
        $filters = [];
        if (isset($_GET['status'])) {
            $filters['status'] = $_GET['status'];
        }
        // Add more filters as needed

        $contacts = $this->contactModel->findAll($filters, $limit, $offset);
        $totalContacts = $this->contactModel->count($filters);

        Response::success([
            'contacts' => $contacts,
            'total' => $totalContacts,
            'page' => $page,
            'limit' => $limit
        ], 'Contacts retrieved successfully.');
    }

    /**
     * Handles getting a single contact by ID.
     * @param int $id The contact ID.
     */
    public function getContact($id)
    {
        if (!is_numeric($id)) {
            Response::error('Invalid contact ID', 400);
            return;
        }

        $contact = $this->contactModel->find($id);

        if ($contact) {
            Response::success($contact, 'Contact retrieved successfully.');
        } else {
            Response::notFound('Contact not found.');
        }
    }

    /**
     * Handles creating a new contact.
     */
    public function createContact()
    {
        $data = json_decode(file_get_contents('php://input'), true);

        // Basic validation
        if (empty($data['name']) || empty($data['email']) || empty($data['message'])) {
            Response::error('Missing required fields: name, email, message', 400);
            return;
        }

        // Set timestamps
        $currentTime = time();
        $data['created_at'] = $currentTime;
        $data['updated_at'] = $currentTime;
        $data['status'] = 'pending'; // Default status for new contacts

        if ($newContactId = $this->contactModel->create($data)) {
            $newContact = $this->contactModel->find($newContactId);
            Response::success(['contact' => $newContact], 'Contact created successfully.', 201);
        } else {
            Response::error('Failed to create contact.', 500);
        }
    }

    /**
     * Handles updating an existing contact.
     * @param int $id The contact ID.
     */
    public function updateContact($id)
    {
        if (!is_numeric($id)) {
            Response::error('Invalid contact ID', 400);
            return;
        }

        $existingContact = $this->contactModel->find($id);
        if (!$existingContact) {
            Response::notFound('Contact not found.');
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data)) {
            Response::error('No data provided for update', 400);
            return;
        }

        // Set updated_at timestamp
        $data['updated_at'] = time();

        if ($this->contactModel->update($id, $data)) {
            $updatedContact = $this->contactModel->find($id);
            Response::success(['contact' => $updatedContact], 'Contact updated successfully.');
        } else {
            Response::error('Failed to update contact.', 500);
        }
    }

    /**
     * Handles deleting a contact.
     * @param int $id The contact ID.
     */
    public function deleteContact($id)
    {
        if (!is_numeric($id)) {
            Response::error('Invalid contact ID', 400);
            return;
        }

        $existingContact = $this->contactModel->find($id);
        if (!$existingContact) {
            Response::notFound('Contact not found.');
            return;
        }

        if ($this->contactModel->delete($id)) {
            Response::success(null, 'Contact deleted successfully.');
        } else {
            Response::error('Failed to delete contact.', 500);
        }
    }
}