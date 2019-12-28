<?php

namespace Service;

class Helper
{
    public static function buildTree(array $elements, $parentId = 0) {
        $branch = array();

        foreach ($elements as $element) {
            if ($element['product_parent'] == $parentId) {
                $children = self::buildTree($elements, $element['product_id']);
                if ($children) {
                    $element['children'] = $children;
                }
                $branch[] = $element;
            }
        }
        return $branch;
    }
}
