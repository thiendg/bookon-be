<?php
/**
 * FAQ Delete API
 * Deletes an FAQ by ID.
 */

// This file is included by modules/faqs/api/index.php
// $faqController is already instantiated there.
// $id is also available.

if ($id) {
    $faqController->deleteFaq($id);
} else {
    Response::error('FAQ ID is required for deletion.', 400);
}
