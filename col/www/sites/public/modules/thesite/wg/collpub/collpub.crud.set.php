<?php

// 2019 import set
function _set_crud_process_import($file_path) {
    _dt_switch_to_maintenance('網站維護中');
    watchdog('sync', 'import set start');
    drupal_flush_all_caches();
    cache_clear_all();
    $p = pathinfo($file_path);
    switch($p['extension']) {
        case 'xlsx':
            $fcsv = file_directory_temp().'/'.$p['filename'].'.csv';
            dtf_xlsx2csv($file_path, $fcsv);
            break;
        case 'csv':
            $fcsv = $file_path;
            break;
    }

    if($fcsv) {
        _set_crud_process_import0(
            $fcsv,
            '_set_crub_process_import_csv_row_cb',
            '_set_crud_batch_import_finished',
            'Import', 
            'admin/config/coll'
        );
    }
    return;
}


// copy from coll/coll.admin.inc
function _set_item_save($title, $values) {
    $opts = &drupal_static(__FUNCTION__);
  
    if (!isset($opts)) {
      $vars = _set_ef_variables();
      $vars = DTEF::entity_create_prepare($vars['node']);
      $entities = $vars['entity'];
  
      $opts['nodetype'] = key($entities);
      $opts['entity'] = current($entities);
    }
  
    extract($opts);
    $allowcomment = 0;
    if(array_key_exists('allowcomment', $values) && $values['allowcomment']) {
      $allowcomment = 1;
    }
  
    $variables = array();
    $variables['title'] = $title;
    $variables['fields'] = array();
  
    $nid = $values['field_identifier'];
    if(!$nid) {
      $repository_id = $values['field_repository_id'];
      if($repository_id) {
        $result = db_query("SELECT r.entity_id FROM {field_data_field_repository_id} AS r WHERE r.field_repository_id_value = :repository_id", array(
                             ':repository_id' => $repository_id,
                           ));
        $nid = $result->fetchField();
      }
    }
  
    foreach ($values as $fn => $d) {
      $var = $entity['field'][$fn]['vardef'];
      $cb = array('DTEF', 'add_field_data_'.$var['type']);
      if(is_callable($cb)) {
        if($var['type'] == 'taxon') {
          $variables['fields'][$fn] = call_user_func($cb, $d, $var['cardinality'], $var['vocabulary']);
        } else if($var['type'] == 'longtext') {
          $d =  str_replace(COLL_NEWLINE, "\n", $d);
          $format = DT::array_get($var, 'format', null);
          $variables['fields'][$fn] = call_user_func($cb, $d, $var['cardinality'], $format);
        } else {
          $variables['fields'][$fn] = call_user_func($cb, $d, $var['cardinality']);
        }
      }
    }
  
    $node = DTEF::node_create($nodetype, $variables, $nid);
    if($allowcomment) $node->comment = COMMENT_NODE_OPEN;
  
    node_save($node);
    return $node;
}
  

function _set_crud_process_import0_deletenode_op($nids, &$context) {
    foreach($nids as $nid) {
      $node = node_delete($nid);
    }
    $context['message'] = t('deleting original collections');
}

function _set_crub_process_import_csv_row_cb($row, $fields) {
    $opts = &drupal_static(__FUNCTION__);
    if (!isset($opts)) {
      $vars = _set_ef_variables();
      $vars = DTEF::entity_create_prepare($vars['node']);
      $entities = $vars['entity'];
      $entity = current($entities);
      foreach ($entity['field'] as $key => $v) {
        $f = $v['instance'];
        $fn = $f['field_name'];
        $opts['sslabels'][$f['sslabel']] = $fn;
      }
    }
  
    $values = array();
    foreach ($opts['sslabels'] as $lbl => $fn) {
      $d = DT::array_get($row, $lbl);
      $values[$fn] = $d;
    }
  
    $values['allowcomment'] = false;

    //myddl($row);
    //myddl($values);
    $node = _set_item_save($row['標題'], $values);
    
    return $node->nid;
}
  
  
function _set_crud_process_import0($csv, $cb, $finished, $title, $redirect_url) {
    $rownum = 0;
    $fields = array();
    $fp = fopen($csv, "r");
    if ($fp !== FALSE) {
        fseek($fp, 0);
        $batch = array(
                'operations' => array(),
                'finished' => $finished,
                'title' => $title,
                'init_message' => t('starting...'),
                'progress_message' => t('Processing...'),
                'error_message' => t('error.')
                );
        // delete all sets
        $op = '_set_crud_process_import0_deletenode_op';
        $nids = db_query('SELECT nid FROM {node} WHERE type = :type', array(':type'=>'set'))->fetchCol();
        
        _wg_batch_chunck($nids, $batch, COLL_BATCH_CHUNK_SIZE, $op, array());

        $rows = array();
        while (($data = fgetcsv($fp)) !== FALSE) {
            $rownum++;
            $num = count($data);
            if(!$fields) {
                for ($c=0; $c < $num; $c++) {
                    $d = $data[$c];
                    $d = trim($d);
                    if($d!='' && ($d[0] != '*')) $fields[$c] = $d;
                }
                continue;
            } else {
                $row = array();
                for ($c=0; $c < $num; $c++) {
                    $d = $data[$c];
                    $d = trim($d);

                    if(array_key_exists($c, $fields) && ($fields[$c] != '')) {
                        $row[$fields[$c]] = $d;
                    }
                }
                $rows[] = $row;
                if($rownum % COLL_BATCH_CHUNK_SIZE == 0) {
                    $batch['operations'][] = array('_coll_process_csv_batch_row_op', array($cb, $rows, $fields, $rownum));
                    $rows = array();
                }
            }
        }
        if($rows) {
            $batch['operations'][] = array('_coll_process_csv_batch_row_op', array($cb, $rows, $fields, $rownum));
        }
        fclose($fp);

        batch_set($batch);
        batch_process($redirect_url);
    }
    return true;
}


//----------------------- finish ------------------------
function _set_crud_batch_import_finished($success, $results, $operations) {
    if ($success) {
      $count = count($results['successed']);
      watchdog('sync set', 'build ft');
      _expsearch_build_ft(); //defined at expsearch.admin.inc
      watchdog('sync set', 'built ft done');
      watchdog('sync set', 'done');
      _dt_switch_to_online();
      _set_crud_batch_import_finished2($count);
    } else {
      $error_operation = reset($operations);
      $message = t('An error occurred while processing %error_operation with arguments: @arguments', array(
                     '%error_operation' => $error_operation[0],
                     '@arguments' => print_r($error_operation[1], TRUE)
                   ));
      drupal_set_message($message, 'error');
    }
}
  
function _set_crud_batch_import_finished2($count) {
    //flush styles
    watchdog('import set', 'flush styles');
    $p2 = drupal_realpath('public://styles');
    DT::rrmdir($p2, true);

    drupal_set_message("import complete! $count record imported");
    drupal_set_message("fulltext index rebuild!");
    module_invoke_all('collpubimportfin');
}
  