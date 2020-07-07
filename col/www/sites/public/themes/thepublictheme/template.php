<?php
function thepublictheme_preprocess_html(&$variables) {
  drupal_add_css(path_to_theme() . '/css/ie8.css', array('group' => CSS_THEME, 'browsers' => array('IE' => '(lt IE 9)', '!IE' => FALSE), 'preprocess' => FALSE));
  drupal_add_css(path_to_theme() . '/bootstrap/css/bootstrap.css');
  drupal_add_js( path_to_theme() . '/js/animate-plus.js');
  drupal_add_css(path_to_theme() . '/css/animate.css');
}

function thepublictheme_preprocess_node(&$vars) {
  if($vars['type'] == 'collection') {
    $vars['thenav'] = $vars['node']->thenav;
  }

  $node = $vars['node'];
  // Create preprocess functions per content type.
  $function = __FUNCTION__ . '_' . $node->type;
    if (function_exists($function)) {
      $function($vars);
  }
}

function thepublictheme_breadcrumb($vars) {
  $breadcrumbs = $vars['breadcrumb'];
  $hometext = _wg_bt_icon('home');
  $home = array_shift($breadcrumbs);
  $home = l($hometext, '<front>', array('html' =>true, 'attributes'=>array('class'=>array('home') ) ));
  array_unshift($breadcrumbs, $home);
  $out = _wgtheme_theme_breadcrumb($breadcrumbs, ' '._wg_bt_icon('chevron-right').' ');
  return $out;
}

// 2019.04.09 cooly
function thepublictheme_preprocess_field(&$variables, $hook) {
  $element = $variables['element'];

  if(empty(variable_get('collection_fields_info'))) set_collection_field_infos();
  $infos = variable_get('collection_fields_info');

  if($element['#bundle'] == 'collection') {
    $field_name = $element['#field_name'];
    if(array_key_exists($field_name, $infos)) {

      $info = $infos[$field_name];
      $variables['label'] = $info['label'] . ' ' . $info['name'];
    }
  }

}

function empty_value_field($node, $field_name) {
  if(empty($node->{$field_name}) || empty($node->{$field_name}['und'][0]['value'])) return true;
  else return false;
}

function empty_tid_field($node, $field_name) {
  if(empty($node->{$field_name}) || empty($node->{$field_name}['und'][0]['tid'])) return true;
  else return false;
}

function thepublictheme_preprocess_node_collection(&$variables) {
   $node = $variables['node'];
   $set_ids = query_sets_of_a_collection($node->nid);
   if(!empty($set_ids)){
     $variables['set_ids'] = build_set_ids_link($set_ids);
   }
}

function thepublictheme_preprocess_node_set(&$variables) {
  drupal_add_css(drupal_get_path('theme', 'thepublictheme') . "/css/set.css");
  $node = $variables['node'];

  $collections = [];
  if(!empty_value_field($node, 'field_collections')) {
    $collection_raw = str_replace( '＂','"', $node->field_collections['und'][0]['value']); // 因為 DT::fputcsv 會把 " 換成 ＂ (全形)
    $collections = drupal_json_decode($collection_raw);
  }

  // meta datas
  // l($qs, 'search', array('query'=> array('qs'=>$qs)))

  $meta = [];
  // 識別號
  if(!empty_value_field($node, 'field_identifier')) $meta[] = ['label' => '識別號 identifier', 'value' => $node->field_identifier['und'][0]['value']];

  // 發布日期
  if(!empty_value_field($node, 'field_release_date')) {
    $date = date('Y/m/d', $node->field_release_date['und'][0]['value']);
    $meta[] = ['label' => '發布日期 release_date', 'value' => $date];
  }

  // 發布者
  if(!empty_value_field($node, 'field_publisher')) $meta[] = ['label' => '發布者 publisher', 'value' => $node->field_publisher['und'][0]['value']];

  // 姓名標示值
  if(!empty_value_field($node, 'field_license_note')) $meta[] = ['label' => '姓名標示值 license_note', 'value' => $node->field_license_note['und'][0]['value']];

  // 授權條款
  if(!empty_tid_field($node, 'field_license')) {
    $tid = $node->field_license['und'][0]['tid'];
    $term = taxonomy_term_load($tid);
    $term_html = l($term->name, 'search', array('query'=> array('qs'=>$term->name))); // ex. <a href="/search?qs=hello">hello</a>
    $meta[] = ['label' => '授權條款 license', 'value' => $term_html];
  }

  // 地點
  if(!empty_value_field($node, 'field_location')) $meta[] = ['label' => '地點 location', 'value' => $node->field_location['und'][0]['value']];

  // 關鍵字
  if(!empty_tid_field($node, 'field_keyword')) {
    $keywords = "";
    foreach($node->field_keyword['und'] as $k) {
      $tid = $k['tid'];
      $term = taxonomy_term_load($tid);
      $term_html = l($term->name, 'search', array('query'=> array('qs'=>$term->name)));
      $keywords .= $term_html;
    }
    $meta[] = ['label' => '關鍵字 keyword' , 'value' => $keywords];
  }

  $variables['description'] = $node->field_description['und'][0]['value'];

  $variables['set_meta'] = $meta;

  $images = [];
  foreach($collections as $collection_id) {
    $image_link = _coll_get_feature_image($collection_id, 'medium', url("node/{$collection_id}"), true);
    if(!$image_link) continue; // 檔案不存在
    $images[] = $image_link;
  }

  $variables['collections'] = $images;
}
