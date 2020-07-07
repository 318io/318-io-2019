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
    return $items;
}
