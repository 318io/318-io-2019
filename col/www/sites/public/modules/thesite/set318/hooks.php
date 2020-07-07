<?php

function set318_theme($existing, $type, $theme, $path) {
    return array(
        'set_list_item' => [
            'variables' => [
                'picture' => NULL,
                'title'   => NULL,
                'content' => NULL,
                'nid'     => NULL,
            ],
        ],
        'set_list' => [
           'variables' => [
               'items' => NULL,
               'pager' => NULL,
           ],
        ],
    );
}


/**
 * Implements hook_field_extra_fields().
 */
function set318_field_extra_fields() {
  $extra['node']['collection']['display']['field_sets'] = array(
    'label' => t('所屬特藏集'),
    'description' => t('所屬特藏集'),
    'weight' => 999,
  );

  return $extra;
}


/**
 * Implements hook_node_view().
 */
function set318_node_view($node, $view_mode, $langcode) {
  $types = ['collection'];

  if ($view_mode === 'full' && in_array($node->type, $types)) {
    $set_ids = query_sets_of_a_collection($node->nid);
    if(!empty($set_ids)){
      $node->content['field_sets'] = array(
        '#markup' => "<strong>所屬特藏集:&nbsp;</strong>". build_set_ids_link($set_ids),
      );
    }
  }
}
