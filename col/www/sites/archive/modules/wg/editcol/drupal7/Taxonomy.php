<?php


trait Taxonomy {

    // 不限 vocabulary
    // $tid == 0, top level children, and so on...
    public static function taxonomy_term_get_children($tid)
    {
      $r = db_query('SELECT tid FROM taxonomy_term_hierarchy where parent=:id', ['id'=>$tid])->fetchCol();
      return $r;
    }

    // 限定 vocabulary
    // $tid == 0, top level children, and so on...
    public static function taxonomy_term_get_children_of_voc($tid, $voc)
    {
        $vid = is_numeric($voc) ? $voc : self::taxonomy_get_vocabulary_by_name($voc)->vid;

        $r = [];
        if(!empty($vid)) {
           $r = db_query('SELECT h.tid FROM taxonomy_term_hierarchy as h, taxonomy_term_data as t where h.tid = t.tid and h.parent=:id and t.vid =:vid', ['id'=>$tid, 'vid' => $vid])->fetchCol();
        }
        return $r;
    }
  
    public static function taxonomy_get_top_tids($voc) {
      return self::taxonomy_term_get_children_of_voc(0, $voc);
    }

    public static function taxonomy_add_vocabulary($name, $machine_name = '', $description='')
    {
      if(!$machine_name) $machine_name = preg_replace('%[^a-zA-Z_]%', '_', $name);
      $edit = array(
                'name' => $name,
                'machine_name' => $machine_name,
                'description' => $description,
                'module' => 'taxonomy',
              );
      $vocabulary = (object) $edit;
      taxonomy_vocabulary_save($vocabulary);
      $vocabulary = taxonomy_vocabulary_machine_name_load($machine_name);
      return $vocabulary;  
    }
  
    public static function taxonomy_vocabulary_delete_by_machinename($n)
    {
      $voc = taxonomy_vocabulary_machine_name_load($n);
      if($voc) {
        taxonomy_vocabulary_delete($voc->vid);
        return true;
      }  
    }

    public static function taxonomy_get_term_name_by_tid($tid)
    {
      $r = db_select('taxonomy_term_data', 't')
           ->fields('t', array('name'))
           ->condition('tid', $tid)
           ->execute()
           ->fetchField();
      return $r;
    }

    public static function is_top_term($tid) {
       $r = db_query('SELECT tid FROM taxonomy_term_hierarchy where parent=0 and tid=:tid', ['tid'=>$tid])->fetchCol();
       return empty($r)? false : true;
    }

    // 傳回 tid 陣列，可能會傳回多值
    public static function taxonomy_get_tid_by_name($name, $voc = null) 
    {
        if(empty($name)) throw new Exception('taxonomy_get_tid_by_name(): empty query string');
        //echo "Querying {$name}\n";
        $vid = null;
        if(isset($voc)) {
          if(is_numeric($voc)) {
            $vid = $voc;
          } else {
            $vocabularies = taxonomy_vocabulary_get_names();
            if (isset($vocabularies[$voc])) {
              $vid = $vocabularies[$voc]->vid;
            }
            else {
              // Return an empty array when filtering by a non-existing vocabulary.
              return array();
            }
          }
        }

        $q = db_select('taxonomy_term_data', 't')
        ->fields('t', array('tid'))
        ->condition('name', $name);

        if(!empty($vid) && is_numeric($vid)) {
            $q = $q->condition('vid', $vid);
        }
        
        $result =  $q->execute();
        $ret = [];
        while($record = $result->fetchAssoc()) {
            //print_r($record);
            $ret[] = $record['tid'];
        }
        //if(empty($ret)) echo "Querying {$name} with empty result\n";
        return $ret;        
    }

    public static function taxonomy_get_top_tid_by_name($name, $voc = null) {
        $tids = self::taxonomy_get_tid_by_name($name, $voc);

        return array_filter($tids, function($tid) {
            return self::is_top_term($tid);
        });
    }
    

    public static function taxonomy_get_nontop_tid_by_name($name, $voc = null) {
      $tids = self::taxonomy_get_tid_by_name($name, $voc);

      return array_filter($tids, function($tid) {
          return !self::is_top_term($tid);
      });
    }

    public static function taxonomy_get_vocabulary_by_name($name)
    {
      $vocabulary = taxonomy_vocabulary_machine_name_load($name);
      return $vocabulary;
    }
  
    public static function taxonomy_get_vocabulary_by_vid($vid)
    {
      $vocabularies = taxonomy_vocabulary_load_multiple(array($vid));
      if($vocabularies) {
        $vocabulary = current($vocabularies);
      }
      return $vocabulary;
    }


    public static function taxonomy_get_terms_of_vocabulary_by_name($name) {
      $voc = taxonomy_vocabulary_machine_name_load($name);
      $tree = taxonomy_get_tree($voc->vid);
      $ret = [];
      foreach ($tree as $term) {
        //echo $term->tid . ' ' . $term->name;
        $ret[$term->tid] = $term->name;
      }
      return $ret;
    }

    /**
      * Create a taxonomy term and return the tid.
     */
    public static function simple_create_taxonomy_term($name, $vid) {
        $term = new stdClass();
        $term->name = $name;
        $term->vid = $vid;
        taxonomy_term_save($term);
        return $term->tid;
    }
  
}