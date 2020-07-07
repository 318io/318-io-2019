<?php

require_once "set_add_more.php";
require_once "set_delete_collections.php";

function node_field_leftjoin(&$query, $prefix, $field_name , $bundle) {
   $condition = "$prefix.entity_type = 'node' AND $prefix.bundle = '$bundle' AND $prefix.entity_id = n.nid";
   $query->leftjoin("field_data_$field_name", $prefix, $condition); // do combination only\
}

function _coll_get_set_image($identifier, $style_name = 'large', $linkurl=false, $retboolifnofile = false) {
  $f = false;
  $files = _coll_get_digifiles($identifier, 'public', 'webm');
  if($files) {
    //webm;
    $file0 = array_shift($files);
    $f = _coll_get_video_icon($file0);
  }
  if(!$files) {
    $files = _coll_get_digifiles($identifier, 'public', 'jpg');
    if($files) $f = array_shift($files);
  }
  if($f === false) {
    if($retboolifnofile) return false;
    $r = '<div class="set-list-image nofile"> no file</div>';
  } else {
    $icon = _wg_image_style($style_name, $f, $identifier);
    $r = '<div class="set-list-image">';

    if($linkurl) {
      $r .= '<a href="'.$linkurl.'">'.$icon.'</a>';
    } else {
      $r .= $icon;
    }
    $r .= '</div>';
  }
  return $r;
}

function wg_mb_shortstr($str, $maxlen) {
  return mb_strlen($str) > $maxlen ? mb_substr($str, 0, $maxlen) . "..." : $str;
}

function query_sets_of_a_collection($collection_id) {
  $query = db_select('field_data_field_collections', 'cs');
  $query->fields('cs',array('entity_id', 'bundle', 'field_collections_value'));
  $query->condition('cs.bundle', 'set');
  //$query->condition('n.status', 1,'='); // 如果 set 下架???
  $query->condition('field_collections_value', '%' . db_like("\"{$collection_id}\"") . '%', 'LIKE');
  $query->distinct();
  $result = $query->execute();
  $set_ids = [];
  while($record = $result->fetchAssoc()) {
    $set_ids[] = $record['entity_id'];
  }
  return $set_ids;
}

function build_set_ids_link($set_ids) {
  $links = "";
  foreach($set_ids as $id) {
    $links .= "<a href='/node/{$id}' target='_blank'>{$id}</a>";
  }
  return $links;
}

function api_update_set_order($nid) {

    if(($_SERVER['REQUEST_METHOD']) != 'POST') {
        watchdog('set318', 'api_update_set_order(): %msg', ['%msg' => 'NOT POST REQUEST']);
        return drupal_json_output(0);
    }

    try {
      $raw_data_json_str = file_get_contents('php://input');
      $order = drupal_json_decode($raw_data_json_str, true);
      $node = node_load($nid);
      myddl($order, 'update_order.txt');

      $node->field_collections['und'][0]['value'] = drupal_json_encode($order);
      node_save($node);

      return drupal_json_output(1);

    } catch(Exception $e) {
      return drupal_json_output(0);
    }
    //return drupal_json_output(get_view_count($nid)); // 傳回目前（已經 inc 後)的 view count
    //return drupal_json_output(0);
}

function api_set_add_collections($set_nid) {

  if(($_SERVER['REQUEST_METHOD']) != 'POST') {
      watchdog('set318', 'api_set_add_collections(): %msg', ['%msg' => 'NOT POST REQUEST']);
      return drupal_json_output(0);
  }

  try {
    $raw_data_json_str = file_get_contents('php://input');
    $collections = drupal_json_decode($raw_data_json_str, true);
    $set = node_load($set_nid);
    //myddl($collections, 'add_collections.txt');

    if(isset($set->field_collections) && !empty($set->field_collections)) {
      $pre_collections = drupal_json_decode($set->field_collections['und'][0]['value']);
      $new_collections = array_unique(array_merge($pre_collections,$collections), SORT_REGULAR);
      $set->field_collections['und'][0]['value'] = drupal_json_encode($new_collections);
    } else {
      // empty collections
      $set->field_collections['und'][0]['value'] = drupal_json_encode($collections);
    }
    node_save($set);

    return drupal_json_output(1);

  } catch(Exception $e) {
    return drupal_json_output(0);
  }

}


function api_set_del_collections($set_nid) {
  if(($_SERVER['REQUEST_METHOD']) != 'POST') {
      watchdog('set318', 'api_set_del_collections(): %msg', ['%msg' => 'NOT POST REQUEST']);
      return drupal_json_output(0);
  }

  try {
    $raw_data_json_str = file_get_contents('php://input');
    $to_delete = drupal_json_decode($raw_data_json_str, true);
    $set = node_load($set_nid);
    //myddl($collections, 'add_collections.txt');

    if(isset($set->field_collections) && !empty($set->field_collections)) {
      $pre_collections = drupal_json_decode($set->field_collections['und'][0]['value']);
      $new_collections = [];
      foreach($pre_collections as $cid) {
        if(in_array($cid, $to_delete)) continue;
        $new_collections[] = $cid;
      }
      $set->field_collections['und'][0]['value'] = drupal_json_encode($new_collections);
      node_save($set);
    }

    return drupal_json_output(1);

  } catch(Exception $e) {
    return drupal_json_output(0);
  }
}
