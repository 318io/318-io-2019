<?php

function build_set_list() {
  drupal_add_css(drupal_get_path('theme', 'thearchivetheme') . "/css/set.css");

  $query = db_select('node', 'n')
         ->extend('PagerDefault');

  node_field_leftjoin($query, 'd', 'field_description', 'set');
  node_field_leftjoin($query, 'c', 'field_collections', 'set');
  node_field_leftjoin($query, 't', 'field_title_image', 'set');

  $query->fields('n', array('nid', 'title', 'changed', 'created'))
        ->fields('d', array('field_description_value'))
        ->fields('c', array('field_collections_value'))
        ->fields('t', array('field_title_image_value'))
        ->limit(5);

  $query->condition('n.status', 1);
  $query->condition('n.type', 'set');
  //$query->orderBy('w.field_weight_value', 'ASC');
  //$query->orderBy('n.nid', 'DESC');
  $query->orderBy('n.nid', 'DESC');

  $markup = '';
  $result = $query->execute();
  while($record = $result->fetchAssoc()) {
    //print_r($record);
    $collections = $record['field_collections_value'];
    $collections_a = !empty($collections)? drupal_json_decode($collections) : [];
    $_collection_a = array_reverse($collections_a);
    $first_collection = array_pop($_collection_a); // get first element

    $title_collection = $record['field_title_image_value'];
    if(!empty($title_collection)) $first_collection = $title_collection;

    $build =[];
    $build['#nid'] = $record['nid'];
    $build['#picture'] = _coll_get_set_image($first_collection, 'large', url("node/{$first_collection}"), true);
    $build['#title'] = $record['title'];
    $build['#content'] = wg_mb_shortstr($record['field_description_value'], 200);
    $build['#theme'] = 'set_list_item';
    //echo drupal_render($build) . "\n";
    $markup .= drupal_render($build);
  }

  $list_build = [];
  $list_build['#items'] = ['#markup' => $markup ];
  $list_build['#pager'] = theme('pager', ['quantity'=>3]);
  $list_build['#theme'] = 'set_list';
  return drupal_render($list_build);
}
