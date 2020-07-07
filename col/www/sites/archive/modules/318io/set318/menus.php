<?php

require_once "toolkits.php";
require_once "page_callback.php";


function set318_menu() {

    $items['sets'] = array(
      'title' => t('特藏集'),
      'page callback' => 'build_set_list',
      //'page arguments' => array(1),
      //'page arguments' => array('form_add_suggest'),
      'access callback' => TRUE,
      //'access callback' => 'user_is_logged_in',
      //'access arguments' => array('add_suggest'),
      'type' => MENU_CALLBACK,
    );

    $items['set/%/more'] = array(
      'title' => t('特藏集新增圖片'),
      'page callback' => 'set_add_more',
      'page arguments' => array(1),
      //'access callback' => TRUE,
      'access arguments' => array('access content'),
      //'access callback' => 'user_is_logged_in',
      //'access arguments' => array('add_suggest'),
      'type' => MENU_CALLBACK,
    );

    $items['set/%/less'] = array(
      'title' => t('特藏集刪除圖片'),
      'page callback' => 'set_delete_collections',
      'page arguments' => array(1),
      //'access callback' => TRUE,
      'access arguments' => array('access content'),
      //'access callback' => 'user_is_logged_in',
      //'access arguments' => array('add_suggest'),
      'type' => MENU_CALLBACK,
    );

    $items['api/update_order/%'] = array(
      'title' => t('Update order of set collections.'),
      'page callback' => 'api_update_set_order',
      'page arguments' => array(2),
      //'access callback' => TRUE,
      'access arguments' => array('access content'),
      'type' => MENU_CALLBACK,
    );

    $items['api/add_collection/%'] = array(
      'title' => t('add more collections to set'),
      'page callback' => 'api_set_add_collections',
      'page arguments' => array(2),
      //'access callback' => TRUE,
      'access arguments' => array('access content'),
      'type' => MENU_CALLBACK,
    );

    $items['api/del_collection/%'] = array(
      'title' => t('delete collections from set'),
      'page callback' => 'api_set_del_collections',
      'page arguments' => array(2),
      //'access callback' => TRUE,
      'access arguments' => array('access content'),
      'type' => MENU_CALLBACK,
    );


    $items['test'] = array(
      'title' => 'example',
      'page callback' => 'drupal_get_form',
      'page arguments' => array('set318_datetime_form'),
      //'access arguments' => array('access content'),
      'access callback' => TRUE,
    );
    return $items;
}
