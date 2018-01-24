<?php
// This function comes from :
// https://stackoverflow.com/questions/8587341/recursive-function-to-generate-multidimensional-array-from-database-result
// Code by :
// https://stackoverflow.com/users/476/deceze

// Modified to adjust to use in project

function buildTree(array $elements, $parentId = 0) {
    $branch = array();

    foreach ($elements as $element) {
        if ($element['product_parent'] == $parentId) {
            $children = buildTree($elements, $element['product_id']);
            if ($children) {
                $element['children'] = $children;
            }
            $branch[] = $element;
        }
    }
    return $branch;
}
