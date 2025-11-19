<?php
/**
 * Contact Delete API
 * Deletes a contact by ID.
 */

// This file is included by modules/contacts/api/index.php
// $contactController is already instantiated there.
// $id is also available.

if ($id) {
    $contactController->deleteContact($id);
} else {
    Response::error('Contact ID is required for deletion.', 400);
}
