<?php
/**
 * Contact Update API
 * Updates an existing contact.
 */

// This file is included by modules/contacts/api/index.php
// $contactController is already instantiated there.
// $id is also available.

if ($id) {
    $contactController->updateContact($id);
} else {
    Response::error('Contact ID is required for update.', 400);
}
