<?php

/* Drupal 7 專用的函式庫 */
require_once "drupal7/EntityField.php";
require_once "drupal7/Taxonomy.php";

class Drupal7 {

    use EntityField;
    use Taxonomy;

    // reference : https://www.drupal.org/node/1377614
    public static function create_node_by_nid($nid, $bundle_type, $title)
    {
      $entity = entity_create('node', array('type' => $bundle_type));
      $entity->nid = $nid;
      $entity->title = $title;
      entity_save('node', $entity);
    }
    
    public static function is_nid_exist($nid) {
      $query = db_select('node', 'n')->fields('n', array('nid'));
      $query->condition('n.nid', $nid);
      //print_r($query->__toString()."\n");
      $result = $query->execute();
      $get = $result->fetchCol();
      return (count($get) > 0) ? true : false;
    }
    
    
    public static function is_nid_of_bundle($nid, $bundle) {
      $query = db_select('node', 'n')->fields('n', array('nid', 'type'));
      $query->condition('n.nid', $nid);
      $query->condition('n.type', $bundle);
      //print_r($query->__toString()."\n");
      $result = $query->execute();
      $get = $result->fetchCol();
      return (count($get) > 0) ? true : false;
    }
    
    
    public static function get_comments_of_a_node($nid) 
    {
        $query = db_select('comment')
                 ->fields('comment', array('cid','nid', 'pid', 'uid', 'name','subject', 'created'))
                 ->condition('nid', $nid, '=');
        $result = $query->execute();
        $comments = [];
        while($record = $result->fetchAssoc()) { $comments[] = $record; }
    
        return $comments;
    }
    
    public static function get_comments_of_a_comment($cid) 
    {
        $query = db_select('comment')
                 ->fields('comment', array('cid','nid', 'pid', 'uid', 'name','subject', 'created'))
                 ->condition('pid', $cid, '=');
        $result = $query->execute();
        $comments = [];
        while($record = $result->fetchAssoc()) { $comments[] = $record; }
    
        // 由小到大排列
        usort($comments, function($a, $b) {
          return $a['created'] < $b['created']? -1 : 1;
        });		
    
        return $comments;
    }

    public static function get_bundle_name_by_machine_name($m_name) {
      $query = db_select('node_type', 'nt')->fields('nt', array('name'));
      $query->condition('nt.type', $m_name);
      $result = $query->execute()->fetchCol();

      if(!empty($result)) return $result[0];
      else                return false;
    }

    public static function get_bundle_type_name_table() {
      $query = db_select('node_type', 'nt')->fields('nt', array('type', 'name'));
      $result = $query->execute();
      $map = [];
      while($record = $result->fetchAssoc()) {         
        $map[$record['type']] = $record['name'];
      }  
      return $map;
    }
    
}