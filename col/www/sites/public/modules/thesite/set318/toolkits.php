<?php

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
  $query->condition('field_collections_value', '%' . db_like("＂{$collection_id}＂") . '%', 'LIKE'); // 這邊＂ 是全形和 archive 版本不同
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
