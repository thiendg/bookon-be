<?php
/**
 * FAQ Get API
 * Returns a single FAQ by ID.
 */

// This file is included by modules/faqs/api/index.php
// $faqController is already instantiated there.
// $id is also available.

if ($id) {
    $faqController->getFaq($id);
} else {
    Response::error('FAQ ID is required.', 400);
}
