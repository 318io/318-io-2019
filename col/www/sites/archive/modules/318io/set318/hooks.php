<?php

function set318_theme($existing, $type, $theme, $path) {
    return array(
        'set_selection_grid' => [
          'variables' => [
             'collections' => NULL,
             'qs' => NULL,
             'nid' => NULL,
             'type' => NULL,
          ],
        ],
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


function set318_field_widget_info() {
  return array(
    'btk_datepicker' => array(
      'label' => t('btk unix timestamp datepicker'),
      'field types' => array('text', 'number_integer'),
      //'settings' => array(
      //  'add_new_text' => 'Add new customer...',
      //),
    ),
  );
}

function set318_field_widget_form(&$form, &$form_state, $field, $instance, $langcode, $items, $delta, $element) {
  $item =& $items[$delta];
  $value = isset($item['value']) ? $item['value'] : time();

  $element['value'] = array(
    '#type' => 'date_popup',
    '#date_format' => 'Y-m-d',
    '#title' => t('發布日期'),
    '#default_value' => date('Y-m-d', $value),
    //'#default_value' => isset($item['value']) ? $item['value'] : '',
  );
  return $element;
}

function set318_node_submit($node, $form, &$form_state) {

  if($node->type == 'set') {
    $timestamp = strtotime($form_state['values']['field_release_date']['und'][0]['value']);
    $form_state['values']['field_release_date']['und'][0]['value'] = $timestamp;
    $node->field_release_date['und'][0]['value'] = $timestamp;
  }
}


function set318_form_set_node_form_alter(&$form, $form_state) {
  global $user ;

  // 要區分編輯還是新增
  $is_new = false;
  $node = $form_state['node'];
  if (!isset($node->nid) || isset($node->is_new)) {
    //drupal_set_message('new');
    $is_new = true;
  }

  $form['field_collections']['#access'] = FALSE;
  $form['field_identifier']['#access'] = FALSE;

  if($user->uid != 1) {
    $form['additional_settings']['#access'] = FALSE;
  }

  if($is_new) {
    $form['field_title_image']['#access'] = FALSE;
  }

  //https://gist.github.com/juampynr/660219418bde29b6d107
  $form['actions']['submit']['#submit'][] = 'set318_node_submit_post_actions';   // append a handler


  $form['#attached']['css'][] = array(
    'type' => 'file',
    'data' => drupal_get_path('module', 'set318') . '/css/alter.css',
  );
}

function set318_node_submit_post_actions($form, &$form_state){
  $node = $form_state['node'];
  //myddl($node, 'xxx.txt');
  if(isset($node->nid)) {
    if(empty($node->field_identifier) || empty($node->field_identifier['und']) ) {
      drupal_set_message('set identifier');
      $node->field_identifier['und'][0]['value'] = $node->nid;
      node_save($node);
    }
  }
}

function set318_menu_alter(&$items) {

    //myddl($items, 'menu_items.txt');
    $items['add/set'] = $items['node/add/set'];
    $items['add/set']['title'] = "新增特藏集";
    //$items['dash/dataset/bl_carousel/add']['access callback'] = 'user_access_or';
    //$items['dash/dataset/bl_carousel/add']['access arguments'] = ['provider'];
    //$items['dash/dataset/bl_carousel/add']['title'] = "新增";
    //unset($items['node/add/human-resource']);

    $items['set/%node/edit'] = $items['node/%node/edit'];
    $items['set/%node/edit']['page arguments'] = array(1);
    //$items['dash/dataset/bl_carousel/edit/%node']['access callback'] = 'user_access_or';
    //$items['dash/dataset/bl_carousel/edit/%node']['access arguments'] = ['provider'];
    $items['set/%node/edit']['title'] = "編輯特藏集";

    //$items['set/%node/edit']['access arguments'] = ['update collection', 'update', 1];
    //myddl($items['set/%node/edit'], 'edit.txt');
}
