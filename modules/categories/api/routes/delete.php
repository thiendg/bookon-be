<?php
/**
 * Category Delete API
 * Deletes a category by ID.
 */

// This file is included by modules/categories/api/index.php
// $categoryController is already instantiated there.
// $id is also available.

if ($id) {
    $categoryController->deleteCategory($id);
} else {
    Response::error('Category ID is required for deletion.', 400);
}
