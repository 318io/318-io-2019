<?php

// used in Drupal7 class

trait EntityField {


    // setter
    private static function _field_set_data($d, $key='value')
    {
      $r = array();
      $r[] = array($key => $d );
      return $r;
    }
  
    public static function field_set_data_text($d)
    {
      return self::_field_set_data($d);
    }
  
    public static function field_set_data_number($d)
    {
      return self::_field_set_data($d);
    }
  
    public static function field_set_data_textlong($d, $format=null)
    {
      $r = array();
      $d = trim($d);
      $r[] = array('value' => $d, 'format' => $format );
      return $r;
    }

    public static function field_set_data_termref_id($d)
    {
      $nid = (int)$d;
      $r = self::_field_set_data($nid, 'tid');
      return $r;
    }

    public static function field_set_data_termref_ids(array $d)
    {
      $r = [];
      foreach($d as $id) {
        $r[] = ['tid' => $id];
      }
      return $r;
    }

  
    public static function field_set_data_entityref_id($d)
    {
      $nid = (int)$d;
      $r = self::_field_set_data($nid, 'target_id');
      return $r;
    }

    public static function field_set_data_entityref_ids(array $d)
    {
      $r = [];
      foreach($d as $id) {
        $r[] = ['target_id' => $id];
      }
      return $r;
    }

    public static function field_set_data_datestamp($d, $timezone = 'Asia/Taipei', $timezone_db = 'UTC')
    {
        $date_type = 'datestamp';
        $r = array();
        if(!is_numeric($d) && is_string($d)) $d = strtotime($d);
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

    // evirt add
    public static function field_set_data_datetime($d, $timezone = 'Asia/Taipei', $timezone_db = 'Asia/Taipei')
    {
        $date_type = 'datetime';
        $r = array();
        //if(!is_numeric($d) && is_string($d)) $d = strtotime($d);
        //if(is_numeric($d)) {
        $r[] = array(
                'value' => $d,
                'timezone' => $timezone,
                'timezone_db' => $timezone_db,
                'date_type' => $date_type,
            );
        //}
        return $r;
    }

    // evirt add
    public static function field_set_data_datetime_w_end($d, $d2, $timezone = 'Asia/Taipei', $timezone_db = 'Asia/Taipei')
    {
        $date_type = 'datetime';
        $r = array();
        //if(!is_numeric($d) && is_string($d)) $d = strtotime($d);
        //if(is_numeric($d)) {
        $r[] = array(
                'value' => $d,
                'value2' => $d2,
                'timezone' => $timezone,
                'timezone_db' => $timezone_db,
                'date_type' => $date_type,
            );
        //}
        return $r;
    }


    public static function field_set_data_datestamp_w_end($d, $d2, $timezone = 'Asia/Taipei', $timezone_db = 'UTC')
    {
        $date_type = 'datestamp';
        $r = array();
        if(!is_numeric($d) && is_string($d)) $d = strtotime($d);
        if(is_numeric($d)) {
        $r[] = array(
                'value' => $d,
                'value2' => $d2,
                'timezone' => $timezone,
                'timezone_db' => $timezone_db,
                'date_type' => $date_type,
                );
        }
        return $r;
    }

    public static function field_set_data_link($url, $title = null)
    {
        $r = array();
        $r =
        array (
            array (
            'url' => $url,
            'title' => $title,
            'attributes' => array (),
            ),
        );

        return $r;
    }

    public static function field_set_data_links($vs)
    {
        $r = [];
        foreach($vs as $v) {
        $item = [];
        if(is_array($v)) {
            $r[] = [
                    'url' => $v['url'],
                    'title' => $v['title'],
                    'attributes' => [],
                ];
        } else if(is_string($v)) {
            $r[] = [
                    'url' => $v,
                    'title' => '',
                    'attributes' => [],
                ];
        }
        }
        return $r;
    }

    public static function field_set_data_term_id($d)
    {
      return self::_field_set_data($d, 'tid');
    }

    public static function field_set_data_email($d)
    {
      return self::_field_set_data($d, 'email');
    }

    // copy from video_embed_field.field.inc(line 228) of Video Embed Field module
    // Can be used only for Video Embed Field(https://www.drupal.org/project/video_embed_field) is installed.
    public static function embed_field_field_presave(&$items) {
      foreach ($items as $delta => $item) {
        // Trim whitespace from the video URL.
        $items[$delta]['video_url'] = trim($item['video_url']);
    
        // Try to load thumbnail URL.
        $info = video_embed_field_thumbnail_url($item['video_url']);
        if (isset($info['url']) && $info['url']) {
          $thumb_url = $info['url'];
          $thumb_extension = pathinfo($thumb_url, PATHINFO_EXTENSION);
          $local_path = "public://video_embed_field_thumbnails/{$info['handler']}/{$info['id']}.$thumb_extension";
    
          $dirname = drupal_dirname($local_path);
          file_prepare_directory($dirname, FILE_CREATE_DIRECTORY | FILE_MODIFY_PERMISSIONS);
    
          $response = drupal_http_request($thumb_url);
          if (!isset($response->error)) {
            file_save_data($response->data, $local_path, FILE_EXISTS_REPLACE);
          }
          else {
            @copy($thumb_url, $local_path);
          }
    
          $items[$delta]['thumbnail_path'] = $local_path;
          // Delete any image derivatives at the original image path.
          image_path_flush($local_path);
        }
        // Couldn't get the thumbnail for whatever reason.
        else {
          $items[$delta]['thumbnail_path'] = '';
        }
    
        // Try to load video data.
        $data = video_embed_field_get_video_data($item['video_url']);
        if (is_array($data) && !empty($data)) {
          $items[$delta]['video_data'] = serialize($data);
        }
        else {
          $items[$delta]['video_data'] = NULL;
        }
    
      }
    
    }

    // Can be used only for Video Embed Field(https://www.drupal.org/project/video_embed_field) is installed.
    public static function field_set_data_video_embed($url) {
      // check is url correct ?
      $items[] = [ 'video_url' => $url ];
      self::embed_field_field_presave($items);
      return $items;
    }
      
    // getter
    private static function _entity_get_field($entity, $field_name)
    {
      $fd = false;
      if(is_object($entity) && property_exists($entity, $field_name)) $fd = $entity->$field_name;
      elseif(is_array($entity)) $fd = $entity[$field_name];
      return $fd;
    }
  
    public static function get_field($entity, $field_name)
    {
      $fd = self::_entity_get_field($entity, $field_name);
      return $fd;
    }
  
    public static function field_get_value_all($entity, $field_name, $lang='und')
    {
      $fd = self::_entity_get_field($entity, $field_name);
      if(!$fd) return false;
      if($value = array_get($fd, array($lang))) {
        return $value;
      }
      return false;
    }
  
    public static function field_get_value($entity, $field_name, $lang='und', $index= 0)
    {
      $fd = self::_entity_get_field($entity, $field_name);
      if(!$fd) return false;
      if($value = array_get($fd, array($lang, $index))) {
        return $value;
      }
      return false;
    }
  
    public static function field_get_tid($entity, $field_name, $lang='und', $index= 0)
    {
      $fd = self::_entity_get_field($entity, $field_name);
      if(!$fd) return false;
      if($tid = array_get($fd, array($lang, $index, 'target_id'))) {
        return $tid;
      }
      return false;
    }
  
    public static function field_get_term($entity, $field_name, $lang='und', $index= 0)
    {
      $fd = self::_entity_get_field($entity, $field_name);
      if(!$fd) return false;
      if($tid = array_get($fd, array($lang, $index, 'target_id'))) {
        $term = taxonomy_term_load($tid);
        return $term;
      }
      return false;
    }
  
    public static function field_get_term_name($entity, $field_name, $lang='und', $index= 0)
    {
      $fd = self::_entity_get_field($entity, $field_name);
      if(!$fd) return false;
      if($tid = array_get($fd, array($lang, $index, 'target_id'))) {
        $term = taxonomy_term_load($tid);
        return $term->name;
      }
      return false;
    }

    public static function field_get_term_name_en($entity, $field_name, $lang='und', $index= 0)
    {
      $fd = self::_entity_get_field($entity, $field_name);
      if(!$fd) return false;
      if($tid = array_get($fd, array($lang, $index, 'target_id'))) {
        $term = taxonomy_term_load($tid);
        return isset($term->field_name_en['und'][0]['value']) ? 
               $term->field_name_en['und'][0]['value'] :
               "";
      }
      return false;
    }

  
    public static function field_get_term_names($entity, $field_name, $opts=[])
    {
      $lang = 'und';
      extract($opts);
      $fd = self::_entity_get_field($entity, $field_name);
      if(!$fd) return false;
      $ar = [];
      $max = 9999;
      for($index =0; $index <= $max; $index++) {
        if($tid = array_get($fd, array($lang, $index, 'target_id'))) {
          $term = taxonomy_term_load($tid);
          if($link) {
            $ar[] = l($term->name, 'taxonomy/term/'.$term->tid);
          } else {
            $ar[] = $term->name;
          }
        } else {
          break;
        }
      }
      return implode(', ', $ar);
    }
  
    public static function field_get_number($entity, $field_name, $lang='und', $index= 0)
    {
      $fd = self::_entity_get_field($entity, $field_name);
      if(!$fd) return false;
      if($value = array_get($fd, array($lang, $index, 'value'))) {
        return $value;
      }
      return false;
    }
  
    public static function field_get_timestamp($entity, $field_name, $format='U', $lang='und', $index= 0)
    {
      $fd = self::_entity_get_field($entity, $field_name);
      if(!$fd) return false;
      if($value = array_get($fd, array($lang, $index, 'value'))) {
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
  
    public static function field_get_date($entity, $field_name, $format='U', $lang='und', $index= 0)
    {
      $fd = self::_entity_get_field($entity, $field_name);
      if(!$fd) return false;
      if($value = array_get($fd, array($lang, $index, 'value'))) {
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
  
    public static function field_get_dateiso($entity, $field_name, $lang='und', $index= 0)
    {
      $fd = self::_entity_get_field($entity, $field_name);
      if(!$fd) return false;
      if($value = array_get($fd, array($lang, $index, 'value'))) {
        return $value;
      }
      return false;
    }
  
    public static function field_get_boolean($entity, $field_name, $lang='und', $index= 0)
    {
      $fd = self::_entity_get_field($entity, $field_name);
      if(!$fd) return false;
      if($value = array_get($fd, array($lang, $index, 'value'))) {
        return $value;
      }
      return false;
    }
  
    public static function field_get_file($entity, $field_name, $lang='und', $index= 0)
    {
      $fd = self::_entity_get_field($entity, $field_name);
      if(!$fd) return false;
      if($f = array_get($fd, array($lang, $index))) {
        return $f;
      }
      return false;
    }
  
    public static function field_get_image_uri($entity, $field_name, $lang='und', $index= 0)
    {
      $fd = self::_entity_get_field($entity, $field_name);
      if(!$fd) return false;
      if($f = array_get($fd, array($lang, $index, 'uri'))) {
        return $f;
      }
      return false;
    }
  
    public static function field_get_url($entity, $field_name, $lang='und', $index= 0)
    {
      $fd = self::_entity_get_field($entity, $field_name);
      if(!$fd) return false;
      if($f = array_get($fd, array($lang, $index, 'url'))) {
        return $f;
      }
      return false;
    }
  
    public static function field_get_entity_ref($entity, $field_name, $lang='und', $index= 0)
    {
      $fd = self::_entity_get_field($entity, $field_name);
      if(!$fd) return false;
      if($id = array_get($fd, array($lang, $index, 'target_id'))) {
        return $id;
      }
      return false;
    }
  
    public static function field_get_entity_ref_term($entity, $field_name, $lang='und', $index= 0)
    {
      $tid = self::field_get_entity_ref($entity, $field_name, $lang, $index);
      if($tid) {
        $term = taxonomy_term_load($tid);
        if($term) {
          return $term;
        }
      }
      return false;
    }
  
    public static function field_get_entity_ref_term_name($entity, $field_name, $lang='und', $index= 0)
    {
      $term = self::field_get_entity_ref_term($entity, $field_name, $lang, $index);
      if($term) {
        return $term->name;
      }
      return false;
    }
  
    public static function field_get_entity_ref_term_names($entity, $field_name, $opts=[])
    {
      $lang = 'und';
      extract($opts);
      $fd = self::_entity_get_field($entity, $field_name);
      if(!$fd) return false;
      $ar = [];
      $max = 9999;
      for($index =0; $index <= $max; $index++) {
        if($tid = array_get($fd, array($lang, $index, 'target_id'))) {
          $term = taxonomy_term_load($tid);
          if($link) {
            $ar[] = l($term->name, 'taxonomy/term/'.$term->tid);
          } else {
            $ar[] = $term->name;
          }
        } else {
          break;
        }
      }
      return implode(', ', $ar);
    }
  
    public static function field_get_text($entity, $field_name, $lang='und', $index= 0)
    {
      $fd = self::_entity_get_field($entity, $field_name);
      if(!$fd) return false;
      $value = array_get($fd, array($lang, $index, 'value'));
      return $value;
    }
  
    public static function field_get_textlong($entity, $field_name, $lang='und', $index= 0)
    {
      $fd = self::_entity_get_field($entity, $field_name);
      if(!$fd) return false;
      if($value = array_get($fd, array($lang, $index, 'value'))) {
        return $value;
      }
      return false;
    }
  
    public static function field_get_textlongsafe($entity, $field_name, $lang='und', $index= 0)
    {
      $fd = self::_entity_get_field($entity, $field_name);
      if(!$fd) return false;
      $r = array_get($fd, array($lang, $index, 'safe_value'));
      return $r;
    }
  
    public static function field_get_textlist_displayvalue($entity, $field_name, $lang='und', $index= 0)
    {
      $code = self::field_get_text($entity, $field_name, $lang, $index);
      $r =  self::field_get_textlist_options($code, $field_name);
      return $r;
    }
  
    public static function field_get_textlist_options_list($field_name)
    {
      $options = &drupal_static(__FUNCTION__, array());
      if(!array_get($options, $field_name)) {
        $sql = "select * from field_config where field_name = :fieldname";
        $row0 = db_query($sql, array(':fieldname' => $field_name))->fetch();
        $data0 = unserialize($row0->data);
        $options[$field_name] = $data0['settings']["allowed_values"];
      }
      return $options[$field_name];
    }
  
    public static function field_get_textlist_options($code, $field_name)
    {
      $options = self::field_get_textlist_options_list($field_name);
      return $options[$code];
    }
  
    public static function field_get_textlist_key($value, $field_name)
    {
      $maps = &drupal_static(__FUNCTION__, array());
      if(!array_get($maps, $field_name)) {
        $options = self::field_get_textlist_options_list($field_name);
        foreach($options as $key=>$value) {
          $maps[$field_name][$value] = $key;
        }
  
      }
      return $maps[$field_name][$value];
    }
  
    public static function field_get_field_collection($entity, $field_name, $lang='und', $index= 0)
    {
      $fd = self::_entity_get_field($entity, $field_name);
      if(!$fd) return false;
      if($value = array_get($fd, array($lang, $index, 'value'))) {
        return $value;
      }
      return false;
    }
  
    public static function field_get_field_collections($entity, $field_name, $lang='und')
    {
      $fd = self::_entity_get_field($entity, $field_name);
      if(!$fd) return false;
      $fcids = [];
      $fd = $fd[$lang];
      foreach($fd as $i) {
        $fcids[] = $i['value'];
      }
      return $fcids;
    }
  
    public static function field_get_field_collections_load($entity, $field_name, $lang='und')
    {
      $fd = self::_entity_get_field($entity, $field_name);
      if(!$fd) return false;
      $r = [];
      $items = $fd[$lang];
      if($items) {
        $fcids = [];
        foreach($items as $item) {
          $fcids[] = $item['value'];
        }
        $r = entity_load('field_collection_item', $fcids);
      }
      return $r;
    }
  
  

    public static function node_create($nodetype, $vars = array(), $nid = false)
    {
      global $user;
      $args_defaults = array(
                         'language' => 'und',
                         'fields' => array(),
                         'format' => 'plain_text',
                         'title' => 'notitle',
                         'sticky' => 0,
                         'uid' => $user->uid,
                         //'is_new' => TRUE,
                       );
      $vars = array_merge($args_defaults, $vars);
  
      if($nid) $vars['nid' ] =$nid;
  
      if(empty($vars['title'])) $vars['title'] = 'notitle';
      $fields = $vars['fields'];
      unset($vars['fields']);
      $format = $vars['format'];
      unset($vars['format']);
      $language = $vars['language'];
  
      $node = null;
  
      if($nid = array_get($vars, 'nid')) {
        if(self::is_nid_exist($nid)) {
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
          if(!$field_name) continue;
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
  
    public static function node_save_with_changed($node, $changed=null)
    {
      if(isset($node->nid)) {
        db_query('DELETE FROM url_alias WHERE source=:source', [':source' => 'node/'.$node->nid]);
      }
      node_save($node);
      if($changed) db_query('UPDATE node SET changed='.$changed.' WHERE nid='.$node->nid);
    }
  

    public static function entity_attach_file(&$entity, $field_name, $extra_fields, $sources, $target_path = 'public://', $uid=null, $replace = FILE_EXISTS_RENAME)
    {
      file_prepare_directory($target_path, FILE_CREATE_DIRECTORY | FILE_MODIFY_PERMISSIONS);
      if(!is_array($sources)) $sources = array($sources);
      if(!$uid) $uid = $entity->uid;
      $field_values = array();
      $file = null;
      foreach($sources as $fn) {
        if(file_exists($fn)) {
          $source_langcode = NULL;
          //$dest = $target_path.transliteration_get(basename($fn), '', $source_langcode);
          //$md5sum = md5(file_get_contents($fn));
          //$dest = $target_path . $md5sum . '-' . drupal_basename($fn);
          $dest = $target_path . drupal_basename($fn);
          $file = file_save_data(file_get_contents($fn), $dest, $replace);
        } else {
          // do nothing;
        }
        if($file) {
          $file->uid = $uid;
          $field_values[] = (array)$file;
        }
      }
      if($field_values) {
        //$entity->$field_name = array('und'=> $field_values);

        // some modules, like file_field_path will move the uri, so let's just store fid
        $field_values2 = [];
        foreach($field_values as $ff) {
          $field_values2[] = array_merge(['fid'=>$ff['fid']], $extra_fields);
        }
        //$entity->$field_name = array('und'=> $field_values2);
        $entity->$field_name['und'] = $field_values2; // 為多值欄位修改
      }
  
      return $entity;
    }
  
}