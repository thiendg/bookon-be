<?php
/**
 * Contact Get API
 * Returns a single contact by ID.
 */

// This file is included by modules/contacts/api/index.php
// $contactController is already instantiated there.
// $id is also available.

if ($id) {
    $contactController->getContact($id);
} else {
    Response::error('Contact ID is required.', 400);
}
