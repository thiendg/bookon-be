<?php
/**
 * FAQ Update API
 * Updates an existing FAQ.
 */

// This file is included by modules/faqs/api/index.php
// $faqController is already instantiated there.
// $id is also available.

if ($id) {
    $faqController->updateFaq($id);
} else {
    Response::error('FAQ ID is required for update.', 400);
}
