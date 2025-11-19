<?php
/**
 * Category Update API
 * Updates an existing category.
 */

// This file is included by modules/categories/api/index.php
// $categoryController is already instantiated there.
// $id is also available.

if ($id) {
    $categoryController->updateCategory($id);
} else {
    Response::error('Category ID is required for update.', 400);
}
