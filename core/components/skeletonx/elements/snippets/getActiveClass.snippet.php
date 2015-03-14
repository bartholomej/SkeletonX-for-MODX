<?php
/**
 * getActiveClass: get html class for navigation (multi-level)
 *
 * PARAMETERS:
 *  &docid: Document ID
 *  &class [optional]: custom html class name (default: "active")
 *  &self [optional]: add class also on self document (default: 1)
 *
 * EXAMPLES:
 *  [[getActiveClass? &docid=`[[+id]]`]]                    // return: class="active"
 *  [[getActiveClass? &docid=`[[+id]]` &class=`clicked`]]   // return: class="clicked"
 *  [[getActiveClass? &docid=`[[+id]]` &self=`0`]]          // return: class="active" (only parents, not self)
 *
 * AUTHOR:
 *  Pepim <https://github.com/pepimpepa>
 *  Bartholomej <https://github.com/bartholomej>
 *
 */

$class = isset($class)? $class : "active";
$self  = isset($self)? $self : 1;
$output = 'class="'. $class .'"';

$currentId = $modx->resource->get('id');

if($currentId == $docid && $self == 1) {
    return $output;
}

$parents = $modx->getParentIds($currentId);

if (in_array($docid, $parents)) {
    return $output;
}