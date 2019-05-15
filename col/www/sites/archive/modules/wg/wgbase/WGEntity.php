<?php
namespace Drupal\wg;
use Drupal\wg\WGPHP;

class WGEntity {
  /****** taxonomy ******/
  public static function term_get_children($id) {
    $r = db_query('SELECT tid FROM taxonomy_term_hierarchy where parent=:id', ['id'=>$id])->fetchCol();
    return $r;
  }

  /****** query ******/
  public static function query_field_join(&$query, $alias, $fieldname, $bundle, $field_alias=null, $fieldname_value_type='value', $entity_type='node', $entitytable_idfield = 'n.nid') {
    $table = 'field_data_'.$fieldname;
    $fieldname_value = $fieldname.'_'.$fieldname_value_type;
    if(!$field_alias && $field_alias !==false) $field_alias = $fieldname_value;
    $condition = "$alias.entity_type = '$entity_type' AND $alias.bundle = '$bundle' AND $entitytable_idfield = $alias.entity_id";
    $query->leftjoin($table, $alias, $condition);
    if($field_alias === false) {    }
    else {
      $query->addField($alias, $fieldname_value, $field_alias);
    }
  }

  public static function query_term_join(&$query, $alias, $field_idfield, $term_name_alias, $term_id_alias) {
    $query->leftjoin('taxonomy_term_data', $alias , $field_idfield.' = '.$alias.'.tid');
    $query->addField($alias, 'name', $term_name_alias);
    if($term_id_alias === false) {}
    else {
      $query->addField($alias, 'tid', $term_id_alias);
    }
  }

  public static function query_term_field_join(&$query, $fieldtable_alias, $termtable_alias, $fieldname, $bundle, $field_alias=null, $term_name_alias, $term_id_alias ) {
    self::query_field_join($query, $fieldtable_alias, $fieldname, $bundle, $field_alias, 'tid');
    self::query_term_join($query, $termtable_alias, $fieldtable_alias.'.'.$fieldname.'_tid', $term_name_alias, $term_id_alias);
  }

  public static function query_term_parent_join(&$query, $alias, $parent_alias, $entitytable_idfield = 't.tid') {
    $query->leftjoin('taxonomy_term_hierarchy', $alias, $entitytable_idfield.' = '.$alias.'.tid');
    $query->addField($alias, 'parent', $parent_alias);
  }

  /****** taxonomy ******/
  public static function taxonomy_add_term($t, $voc, $vars = array()) {
    $t = trim($t);
    if(empty($t)) return false;

    $save = false;
    $term = self::taxonomy_get_term($t, $voc);

    if(!$term) {
      $vocabulary = self::taxonomy_get_vocabulary($voc);
      $term = array(
                'vid' => $vocabulary->vid,
                'name' => $t,
                'vocabulary_machine_name' => $vocabulary->machine_name,
              );
      $save = true;
    }
    $term = (object)$term;

    if($par = WGPHP::array_get($vars, 'parent')) {
      $term->parent = $par;
    }

    $fields = WGPHP::array_get($vars, 'fields');
    unset($vars['fields']);
    //self::_entity_add_fields_helper($term, $fields);

    if(is_object($t)) {
      $t = (array)$t;
    }
    if(is_array($t) && (WGPHP::array_get($t, 'name') != $term->name)) {
      $term->name = $t['name'];
      $save = true;
    }
    if($save) {
      if(!is_object($term)) $term = (object)$term;
      if(property_exists($term, 'parent') && !$term->parent) {
        unset($term->parent);
      }
      taxonomy_term_save($term);
    }

    return $term;
  }

  public static function taxonomy_get_term($condition, $vocabularies = array(), $load = true, $reset = false) {
    $term_cache = &drupal_static('term_cache', array());
    $cacheme = false;
    if(!$reset && is_string($condition) && is_string($vocabularies)) {
      $term = WGPHP::array_get($term_cache, array($vocabularies, $condition));
      if($term) return $term;
      $cacheme = true;
    }

    if(is_object($condition) && property_exists($condition, 'tid')) {
      return $condition;
    }
    $terms = self::taxonomy_get_terms($condition, $vocabularies, $load);
    if($terms) {
      $term = current($terms);
      if($cacheme) $term_cache[$vocabularies][$condition] = $term;
      return $term;
    } else {
      return false;
    }
  }

  public static function taxonomy_get_terms($condition, $vocabularies = array(), $load = true) {
    if(empty($condition)) {
      return false;
    }

    if(is_array($condition)) {
      if(WGPHP::array_get($condition, 'tid')) {
        $condition = $condition['tid'];
      } else if(WGPHP::array_get($condition, 'name')) {
        $condition = $condition['name'];
      } else {
        WGPHP::dbug($condition, 'taxonomy_get_terms error');
        die();
      }
    }

    if(is_numeric($condition)) {
      $tids = array($condition);
    } else {
      $args = array();
      $args[':vid'] = array();
      if(is_numeric($vocabularies)) {
        $vocabulary = self::taxonomy_get_vocabulary_by_vid($vocabularies);
        $args[':vid'][] = $vocabulary->vid;
      } else if(is_string($vocabularies)) {
        $vocabulary = taxonomy_vocabulary_machine_name_load($vocabularies);
        $args[':vid'][] = $vocabulary->vid;
      } else if(is_object($vocabularies)) {
        if(property_exists($vocabularies, 'vid')) {
          $args[':vid'][] = $vocabularies->vid;
        }
      } else {
        $args[':vid'] = $vocabularies;
      }
      $args[':name'] = $condition;

      $query = db_select('taxonomy_term_data', 't');
      $query->addField('t', 'tid');

      $query->where('t.name = :name', $args);
      if($args[':vid']) $query->where('t.vid IN (:vid)', $args);
      $tids = $query->execute()->fetchCol();
    }
    if($tids) {
      $tids = array_unique($tids);
      if($load) {
        return taxonomy_term_load_multiple($tids);
      } else {
        return $tids;
      }
    }
    return false;

  }

  public static function taxonomy_get_vocabulary($condition) {
    if(empty($condition)) {
      return false;
    }
    if(is_numeric($condition)) {
      $vocabulary = self::taxonomy_get_vocabulary_by_vid($condition);
    } else if(is_string($condition)) {
      $vocabulary = taxonomy_vocabulary_machine_name_load($condition);
    } else if(is_object($condition)) {
      $vocabulary = $condition;
    } else {
      if($vid = WGPHP::array_get($condition, 'vid')) {
        $vocabulary = self::taxonomy_get_vocabulary_by_vid($condition);
      } else if($machine_name = WGPHP::array_get($condition, 'machine_name')) {
        $vocabulary = taxonomy_vocabulary_machine_name_load($machine_name);
      } else {
        return false;
      }
    }
    return $vocabulary;
  }

  public static function taxonomy_get_vocabulary_by_vid($vid) {
    $vocabularies = taxonomy_vocabulary_load_multiple(array($vid));
    if($vocabularies) {
      $vocabulary = current($vocabularies);
    }
    return $vocabulary;
  }

  /****** field ******/

  /** file **/
  public static function _file_attach($fids, &$entity, $field_name) {
    if(!is_array($fids)) $fids = array($fids);
    if(!$uid) $uid = $entity->uid;
    $filed_valus = array();
    foreach($fids as $fid) {
      $file = file_load($fid);
      if($file) {
        $filed_valus[] = (array)$file;
      }
    }
    if($filed_valus) {
      $entity->$field_name = array('und'=> $filed_valus);
    }
    return $entity;
  }

  public static function _file_migrate_and_attach($fns, $path = 'public://', &$entity, $field_name, $uid=null, $replace = FILE_EXISTS_RENAME) {
    file_prepare_directory($path, FILE_CREATE_DIRECTORY);
    if(!is_array($fns)) $fns = array($fns);
    if(!$uid) $uid = $entity->uid;
    $filed_valus = array();
    foreach($fns as $fn) {
      if(file_exists($fn)) {
        $dest = $path.strtolower(basename($fn));

        $file = file_save_data(file_get_contents($fn), $dest, $replace);
      } else {
        // do nothing;
      }

      if($file) {
        $file->uid = $uid;
        $filed_valus[] = (array)$file;
      }
    }
    if($filed_valus) {
      $entity->$field_name = array('und'=> $filed_valus);
    }
    return $entity;
  }

  /** setter **/
  private static function _field_set_data($d, $key='value') {
    $r = array();
    $r[] = array($key => $d );
    return $r;
  }

  public static function field_set_data_text($d) {
    return self::_field_set_data($d);
  }

  public static function field_set_data_number($d) {
    return self::_field_set_data($d);
  }

  public static function field_set_data_textlong($d, $format=null) {
    $r = array();
    $d = trim($d);
    $r[] = array('value' => $d, 'format' => $format );
    return $r;
  }

  public static function field_set_data_entityref($d, $opts) {
    extract($opts);
    if($isnid) {
      $nid = (int)$d;
    } else {
      // etype
      $nid = db_query('SELECT nid FROM node WHERE title = :title AND type = :type',
                      array(':title' => $d, ':type' => $etype))->fetchField();
    }

    $r = array();
    if($nid) {
      $r = self::_field_set_data($nid, 'target_id');
    }
    return $r;
  }

  public static function field_set_data_datestamp($d, $timezone = 'Asia/Taipei', $timezone_db = 'UTC') {
    $date_type = 'datestamp';
    $r = array();
    if(is_string($d)) $d = strtotime($d);
    if(is_numeric($d)) {
      $r[] = array(
               'value' => $d,
               'timezone' => $timezone,
               'timezone_db' => $timezone_db,
               'date_type' => $date_type,
             );
    }
    return $r;
  }

  public static function field_set_data_link($url) {
    $r = array();
    $r =
      array (
        array (
          'url' => $url,
          'title' => NULL,
          'attributes' => array (),
        ),
      );

    return $r;
  }

  public static function field_set_data_taxon($taxonstr, $voc, $vars = array()) {
    $r = array();
    if($taxonstr == '') return $r;
    $taxonstr = str_replace('、', ';', $taxonstr);
    $ar2 = explode(';', $taxonstr);
    foreach($ar2 as $s) {
      $s = trim($s);
      $s = preg_replace('%  +%', ' ', $s);
      $term = self::taxonomy_add_term($s, $voc);
      if($vars) {
        if($vars['fields']) {
          foreach($vars['fields'] as $field_name=>$field_value) {
            $term->$field_name = $field_value;
          }
          taxonomy_term_save($term);
        }
      }
      if($term) {
        $term = (array)$term;
        $r[] = $term;
      }

    }
    return $r;
  }

  public static function field_set_data_term_id($d) {
    return self::_field_set_data($d, 'tid');
  }

  public static function field_set_data_geolocation($d) {
    $r = array();
    if(is_array($d)) {
      $r[] = $d;
    }
    return $r;
  }

  /** getter **/
  private static function _entity_get_field($entity, $field_name) {
    if(is_object($entity)) $fd = $entity->$field_name;
    elseif(is_array($entity)) $fd = $entity[$field_name];
    return $fd;
  }

  public static function get_field($entity, $field_name) {
    $fd = self::_entity_get_field($entity, $field_name);
    return $fd;
  }

  public static function field_get_value_all($entity, $field_name, $lang='und') {
    $fd = self::_entity_get_field($entity, $field_name);
    if(!$fd) return false;
    if($value = WGPHP::array_get($fd, array($lang))) {
      return $value;
    }
    return false;
  }

  public static function field_get_value($entity, $field_name, $lang='und', $index= 0) {
    $fd = self::_entity_get_field($entity, $field_name);
    if(!$fd) return false;
    if($value = WGPHP::array_get($fd, array($lang, $index))) {
      return $value;
    }
    return false;
  }

  public static function field_get_tid($entity, $field_name, $lang='und', $index= 0) {
    $fd = self::_entity_get_field($entity, $field_name);
    if(!$fd) return false;
    if($tid = WGPHP::array_get($fd, array($lang, $index, 'tid'))) {
      return $tid;
    }
    return false;
  }

  public static function field_get_term($entity, $field_name, $lang='und', $index= 0) {
    $fd = self::_entity_get_field($entity, $field_name);
    if(!$fd) return false;
    if($tid = WGPHP::array_get($fd, array($lang, $index, 'tid'))) {
      $term = taxonomy_term_load($tid);
      return $term;
    }
    return false;
  }

  public static function field_get_term_name($entity, $field_name, $lang='und', $index= 0) {
    $fd = self::_entity_get_field($entity, $field_name);
    if(!$fd) return false;
    if($tid = WGPHP::array_get($fd, array($lang, $index, 'tid'))) {
      $term = taxonomy_term_load($tid);
      return $term->name;
    }
    return false;
  }

  public static function field_get_all_term_name($entity, $field_name, $lang='und') {
    $values = self:: field_get_value_all($entity, $field_name, $lang);
    if(!$values) return false;
    $r = [];
    foreach($values as $v){
      $tid = $v['tid'];
      $term = taxonomy_term_load($tid);
      $r[]= $term->name;
    }
    return $r;
  }

  public static function field_get_number($entity, $field_name, $lang='und', $index= 0) {
    $fd = self::_entity_get_field($entity, $field_name);
    if(!$fd) return false;
    if($value = WGPHP::array_get($fd, array($lang, $index, 'value'))) {
      return $value;
    }
    return false;
  }

  public static function field_get_timestamp($entity, $field_name, $format='U', $lang='und', $index= 0) {
    $fd = self::_entity_get_field($entity, $field_name);
    if(!$fd) return false;
    if($value = WGPHP::array_get($fd, array($lang, $index, 'value'))) {
      $d = $value;
      if($format == 'U') {
        $r = $d;
      } else {
        $r = date($format, $d);
      }
      return $r;
    }
    return false;
  }

  public static function field_get_date($entity, $field_name, $format='U', $lang='und', $index= 0) {
    $fd = self::_entity_get_field($entity, $field_name);
    if(!$fd) return false;
    if($value = WGPHP::array_get($fd, array($lang, $index, 'value'))) {
      $d = strtotime($value);
      if($format == 'U') {
        $r = $d;
      } else {
        $r = date($format, $d);
      }
      return $r;
    }
    return false;
  }

  public static function field_get_boolean($entity, $field_name, $lang='und', $index= 0) {
    $fd = self::_entity_get_field($entity, $field_name);
    if(!$fd) return false;
    if($value = WGPHP::array_get($fd, array($lang, $index, 'value'))) {
      return $value;
    }
    return false;
  }

  public static function field_get_file($entity, $field_name, $lang='und', $index= 0) {
    $fd = self::_entity_get_field($entity, $field_name);
    if(!$fd) return false;
    if($f = WGPHP::array_get($fd, array($lang, $index))) {
      return $f;
    }
    return false;
  }

  public static function field_get_image_uri($entity, $field_name, $lang='und', $index= 0) {
    $fd = self::_entity_get_field($entity, $field_name);
    if(!$fd) return false;
    if($f = WGPHP::array_get($fd, array($lang, $index, 'uri'))) {
      return $f;
    }
    return false;
  }

  public static function field_get_entity_ref($entity, $field_name, $lang='und', $index= 0) {
    $fd = self::_entity_get_field($entity, $field_name);
    if(!$fd) return false;
    if($id = WGPHP::array_get($fd, array($lang, $index, 'target_id'))) {
      return $id;
    }
    return false;
  }

  public static function field_get_text($entity, $field_name, $lang='und', $index= 0) {
    $fd = self::_entity_get_field($entity, $field_name);
    if(!$fd) return false;
    if($value = WGPHP::array_get($fd, array($lang, $index, 'value'))) {
      return $value;
    }
    return false;
  }

  public static function field_get_textlong($entity, $field_name, $lang='und', $index= 0) {
    $fd = self::_entity_get_field($entity, $field_name);
    if(!$fd) return false;
    if($value = WGPHP::array_get($fd, array($lang, $index, 'value'))) {
      return $value;
    }
    return false;
  }

  public static function field_get_textlist_displayvalue($entity, $field_name, $lang='und', $index= 0) {
    $code = self::field_get_text($entity, $field_name, $lang, $index);
    $r =  self::field_get_textlist_options($code, $field_name);
    return $r;
  }

  public static function field_get_textlist_options($code, $field_name) {
    $options = &drupal_static(__FUNCTION__, array());
    if(!WGPHP::array_get($options, $field_name)) {
      $sql = "select * from field_config where field_name = :fieldname";
      $row0 = db_query($sql, array(':fieldname' => $field_name))->fetch();
      $data0 = unserialize($row0->data);
      $options[$field_name] = $data0['settings']["allowed_values"];
    }
    return $options[$field_name][$code];
  }

  public static function field_get_field_collection($entity, $field_name, $lang='und', $index= 0) {
    $fd = self::_entity_get_field($entity, $field_name);
    if(!$fd) return false;
    if($value = WGPHP::array_get($fd, array($lang, $index, 'value'))) {
      return $value;
    }
    return false;
  }

  public static function field_get_field_collections_load($entity, $field_name, $lang='und') {
    $fd = self::_entity_get_field($entity, $field_name);
    if(!$fd) return false;
    $r = [];
    $items = $fd[$lang];
    if($items){
      $fcids = [];
      foreach($items as $item){
        $fcids[] = $item['value'];
      }
      $r = entity_load('field_collection_item', $fcids);
    }
    return $r;
  }

  public static function node_attach_field_collection($node, $fc_field, $values) {
    self::entity_attach_field_collection('node', $node, $fc_field, $values);
  }

  public static function entity_attach_field_collection($hostentitytype, $hostentity, $fc_field, $values) {
    module_load_include('inc', 'entity', 'includes/entity.controller');
    $fc_values = array(
                   'field_name' => $fc_field,
                 );
    foreach($values as $n=>$v) {
      $fc_values[$n] = array(LANGUAGE_NONE => $v);
    }
    $entity = entity_create('field_collection_item', $fc_values);
    $entity->setHostEntity($hostentitytype, $hostentity);
    $entity->save();
  }

  /****** node ******/
  public static function get_nodes_view_count($args) {
    $args_default = array(
                      'type' => '',
                      'types' => array(),
                      'alias' => array(),
                      'query_cb' => null,
                      'query_cb_arg' => null,
                    );
    $args = WGPHP::array_merge($args_default, $args);
    extract($args);

    $nids = array();
    if($alias) {
      $nids = self::get_nid_by_alias($alias);
      $num_rows = count($nids);
    } else {
      if($type && !$types) {
        $types = array($type);
      }
      $r = array();

      $query = db_select('node', 'n');
      $query->condition('n.status', 1, '>=')
      ->condition('n.type', $types, 'IN');

      if($query_cb && is_callable($query_cb)) {
        $query_cb($query, $args, $query_cb_arg);
      }

      $count_query = $query->countQuery();
      $num_rows = $query->countQuery()->execute()->fetchField();
    }

    return $num_rows;
  }

  public static function get_nodes_view($args) {
    $args_default = array(
                      'viewmode' => 'full',
                      'prenode' => '',
                      'postnode' => '',
                      'preblock' => '',
                      'postblock' => '',
                      'type' => '',
                      'types' => array(),
                      'alias' => array(),
                      'orders' => array(),
                      'limit' => 0,
                      'offset' => 0,
                      'perpage' => 0,
                      'page' => 0,
                      'pagina_goto' => true,
                      'ajaxpagina' => false,
                      'ajax_scroll_auto_load' => false,
                      'query_cb' => null,
                      'query_cb_arg' => null,
                    );
    $args = WGPHP::array_merge($args_default, $args);
    extract($args);

    $pagination = '';

    $nids = array();
    if($alias) {
      $nids = self::get_nid_by_alias($alias);
    } else {
      if($type && !$types) {
        $types = array($type);
      }
      $r = array();

      $query = db_select('node', 'n');
      $query->condition('n.status', 1, '>=')
      ->condition('n.type', $types, 'IN');

      $query->fields('n', array('nid'));

      if($query_cb && is_callable($query_cb)) {
        $query_cb($query, $args, $query_cb_arg);
      }

      if($orders) {
        foreach($orders as $order) {
          $f = 'n.'.$order[0];
          $d = $order[1];
          $query->orderBy($f, $d);
        }
      }

      $query->orderBy('n.nid', 'ASC');

      $args['num_rows'] = $query->countQuery()->execute()->fetchField();

      if($limit) {
        $query->range($offset, $limit);
      } else if($perpage) {
        $pagination = self::_get_nodes_view_pagination($query, $args);
      }
      $result = $query->execute();
      $nids = $result->fetchCol();
    }

    $r = self::_get_nodes_view_view($nids, $args);

    $r= array('#markup' => $preblock. render($r) .  $pagination.$postblock);
    if(array_key_exists('ajax', $_GET)) {
      $r = render($r);
      print $r;
      exit();
      // only support ajax = 'html' now
    }
    return $r;
  }

  private static function _get_nodes_view_view($nids, $args) {
    $args_default = array(
                      'viewmode' => 'teaser',
                      'prenode' => '',
                      'postnode' => '',
                    );
    $args = WGPHP::array_merge($args_default, $args);

    extract($args);

    if(array_key_exists('page', $_GET)) {
      $page = (int)$_GET['page'];
    } else {
      $page = 0;
    }

    $rowindex = 0;
    foreach($nids as $nid) {
      $rowindex ++;
      $node = node_load($nid);
      $node->rowindex = $rowindex;
      $node->index = $page*$perpage + $rowindex;
      $n = node_view($node, $viewmode);
      $n['#prefix'] = $prenode;
      $n['#suffix'] = $postnode;
      $r[] = $n;
    }
    return $r;
  }

  private static function _get_nodes_view_pagination(&$query, $args) {
    extract($args);
    $pagination = '';
    if(array_key_exists('page', $_GET)) {
      $page = (int)$_GET['page'];
    } else {
      $page = 0;
    }

    $pages = ceil($num_rows / $perpage);
    if($page >= ($pages-1)) $page = ($pages-1);
    if($page < 0) $page = 0;

    $offset = $page * $perpage;
    $query->range($offset, $perpage);

    if($pages) {
      if(array_key_exists('pagination_base', $args)) {
        $path_base = $pagination_base;
      } else {
        $path_base = current_path();
      }
      if($ajaxpagina) {
        if($page >= ($pages-1)) {
        } else {
          $pagination .= '<div id="ajax_next">';
          $options = array(
                       'html'=>true,
                       'query'=>array('page' => ($page+1)),
                       'attributes' => array('data-ajaxtarget'=>'#ajax_next', 'class' => array('more-articles', 'ajaxload'),),
                     );
          if($ajax_scroll_auto_load) {
            $options['attributes']['class'][] = 'ajax_scroll_auto_load';
          }
          if(array_key_exists('ajax', $_GET)) {
            $options['query']['ajax'] = 'html';
          }
          $text = '載入更多文章...';
          $pagination .= l($text, $path_base, $options);
          $pagination .= '</div>';
        }
      } else {
        if($pages > 1) {
          $path_base = current_path();

          $pagination .= '<nav style="clear:both"><ul class="pagination">';
          $classes ='prev';
          if($page <= 0) {
            $classes .= ' disabled';
          }
          $pagination .= '<li class="'.$classes.'">';
          $opts = array('page' => ($page-1));
          $options = array('html'=>true,'query'=>$opts);
          $text = '<span aria-hidden="TRUE">&laquo;</span><span class="sr-only">Previous</span>';
          $pagination .= l($text, $path_base, $options);
          $pagination .= '</li>';

          $numpage = 10;

          $page_start = $page-floor($numpage/2);
          if($page_start < 0) $page_start = 0;
          $page_end = $page_start+$numpage;
          if($page_end > $pages) {
            $page_end = $pages;
            $page_start = $page_end - $numpage;
          }

          if($page_start < 0) $page_start = 0;

          for($i=$page_start; $i<$page_end; $i++) {
            $pn = $i+1;
            $class = 'pagination-item';
            if($i == $page) {
              $class .= ' active';
            }
            $pagination .= '<li class="'.$class .'">';
            $opts = array('page' => ($i));
            $args['row'] = ($i * $rowsperpage);
            $options = array('html'=>true,'query'=>$opts);
            $text = $pn;
            $pagination .= l($text, $path_base, $options);
            $pagination .= '</li>';
          }

          if($pagina_goto &&($pages>$numpage)) {
            $pagination .= '<form id="form-pagina_goto"><input class="btn btn-default" type="text" size="3" id="pagina_goto" name="page" placeholder="到"></form>';
          }

          if($page >= ($pages-1)) {
            $pagination .= '<li class="disabled">';
          } else {
            $pagination .= '<li>';
          }
          $opts = array('page' => ($page+1));
          $options = array('html'=>true,'query'=>$opts);
          $text = '<span aria-hidden="TRUE">&raquo;</span><span class="sr-only">Next</span></a>';
          $pagination .= l($text, $path_base, $options);
          $pagination .= '</li>';
          $url = url($path_base, array(
                       'query'=>array('page' => $page+1)
                     ));
          $pagination .= '</ul></nav>';

        }
      }
    }
    return $pagination;
  }

  public static function get_node_prev_next($node, $op='prev', $types=array()) {
    if(!$types) $types = array($node->type);

    $query = db_select('node', 'n');
    $query->fields('n', array('nid','title'));

    $query->condition('n.status', 1, '>=');
    $query->condition('n.type', $types, 'IN');
    $query->range(0, 1);
    if($op == 'prev') {
      $query->condition('n.created', $node->created, '<');
      $query->orderBy('n.created', 'DESC');
    } else {
      $query->condition('n.created', $node->created, '>');
      $query->orderBy('n.created', 'ASC');
    }
    $r = $query->execute()->fetch();
    return $r;
  }

  public static function get_nid_by_alias($alias) {
    if(is_array($alias)) {
      $r = array();
      foreach($alias as $a) {
        $r[] = self::_get_nid_by_alias($a);
      }
    } else {
      $r = self::_get_nid_by_alias($alias);
    }
    return $r;
  }

  static private function _get_nid_by_alias($alias) {
    $path = drupal_lookup_path("source", $alias);
    $ar = explode('/',$path);
    $nid = array_pop($ar);
    return $nid;
  }

  public static function node_exists($nid) {
    $c = db_query('SELECT COUNT(*) FROM {node} WHERE nid = :nid', array(':nid'=>$nid))->fetchField();
    return $c;
  }

  public static function node_create($nodetype, $vars = array(), $nid = false) {
    global $user;
    $default = array(
                 'language' => 'und',
                 'fields' => array(),
                 'format' => 'plaintext',
                 'title' => 'notitle',
                 'sticky' => 0,
                 'uid' => $user->uid,
                 //'is_new' => TRUE,
               );
    $vars = array_merge($default, $vars);

    if($nid) $vars['nid' ] =$nid;

    if(empty($vars['title'])) $vars['title'] = 'notitle';
    $fields = $vars['fields'];
    unset($vars['fields']);
    $format = $vars['format'];
    unset($vars['format']);
    $language = $vars['language'];

    $node = null;

    if($nid = WGPHP::array_get($vars, 'nid')) {
      if(self::node_exists($nid)) {
        $node = node_load($nid, null, true);
      }
    }
    if($node) {
      $node->is_new = FALSE;
    } else {
      $node = new \stdClass();
      $node->is_new = TRUE;
    }

    if($fields) {// todo: use entity_add_fields
      foreach($fields as $field_name=>$field_values) {
        $node->$field_name = array($language=>$field_values);
      }
    }
    unset($vars['fields']);
    foreach($vars as $k=>$v) {
      $node->$k = $v;
    }
    $node->type = $nodetype;

    return $node;
  }

  public static function node_view_render($node, $view_mode) {
    $v = node_view($node, $view_mode);
    $r = render($v);
    return $r;
  }

  public static function node_get_title($nid, $link=false) {
    $title = db_query("SELECT title FROM {node} WHERE nid = :nid", array(':nid'=>$nid))->fetchField();
    if($title) {
      if($link) $r = l($title, 'node/'.$nid);
      else $r = $title;
    }
    return $r;
  }

  public static function nodepath_get_link($path) {
    $l = false;
    $p2 = drupal_lookup_path('source', $path);
    if(preg_match('%^node/([0-9]+)$%', $p2, $m)) {
      $n = node_load($m[1]);
      $l = l($n->title, $p2);
    }
    return $l;
  }
  /****** MISC ******/
  public static function menu_get_term() {
    $term = menu_get_object('taxonomy_term', 2);
    if (!isset($term->tid)) {
      if (arg(0) == 'taxonomy' && arg(1) == 'term' && arg(2)) {
        $term = taxonomy_term_load(arg(2));
      }
    }
    return $term;
  }

}
