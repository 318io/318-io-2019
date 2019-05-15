<?php
namespace Drupal\wg;

class WGPHP {
  public static function remove_emoji($text) {
    return preg_replace('/([0-9|#][\x{20E3}])|[\x{00ae}|\x{00a9}|\x{203C}|\x{2047}|\x{2048}|\x{2049}|\x{3030}|\x{303D}|\x{2139}|\x{2122}|\x{3297}|\x{3299}][\x{FE00}-\x{FEFF}]?|[\x{2190}-\x{21FF}][\x{FE00}-\x{FEFF}]?|[\x{2300}-\x{23FF}][\x{FE00}-\x{FEFF}]?|[\x{2460}-\x{24FF}][\x{FE00}-\x{FEFF}]?|[\x{25A0}-\x{25FF}][\x{FE00}-\x{FEFF}]?|[\x{2600}-\x{27BF}][\x{FE00}-\x{FEFF}]?|[\x{2900}-\x{297F}][\x{FE00}-\x{FEFF}]?|[\x{2B00}-\x{2BF0}][\x{FE00}-\x{FEFF}]?|[\x{1F000}-\x{1F6FF}][\x{FE00}-\x{FEFF}]?/u', '', $text);
  }

  public static function result_wrap($results, $request_url, $request_params = [], $status = '') {
    $ret = [];
    $ret['request']['url'] = $request_url;
    $ret['request']['params'] = $request_params;
    if($results) {
      $ret['status'] = 'OK';
      $ret['results'] = $results;
    } else {
      $ret['status'] = 'FAIL';
      $ret['results'] = [];
    }
    if($status) $ret['status'] = $status;
    return $ret;
  }

  public static function result_wrap_format_out($ret, $format='json') {
    // $format = 'html';
    switch($format) {
      case 'html':
        $html = '<!DOCTYPE html><html><head><meta charset="utf-8"><meta http-equiv="Content-Type" content="text/html; charset=utf-8" /></head><body>';
        $xml = '';
        $xml .= WGXML::startTag('roadkilltw');
        $xml .= WGXML::startTag('request');
        $xml .= WGXML::array2xml($ret['request']);
        $xml .= WGXML::endTag();
        $xml .= WGXML::tag('status', $ret['status']);
        $xml .= WGXML::startTag('results');
        foreach($ret['results'] as $row) {
          $xml .= WGXML::startTag('record');
          $xml .= WGXML::array2xml($row);
          $xml .= WGXML::endTag();
        }
        $xml .= WGXML::endTag();
        $xml .= WGXML::endTag();

        $html .= WGPHP::str_replace_array($xml, ['<'=>'&lt;']);
        $html .= '</body></html>';
        echo $html;
        drupal_exit();
        break;
      case 'xml':
        $xml = '';
        $xml .= WGXML::startTag('roadkilltw');
        $xml .= WGXML::startTag('request');
        $xml .= WGXML::array2xml($ret['request']);
        $xml .= WGXML::endTag();
        $xml .= WGXML::tag('status', $ret['status']);
        $xml .= WGXML::startTag('results');
        foreach($ret['results'] as $row) {
          $xml .= WGXML::startTag('record');
          $xml .= WGXML::array2xml($row);
          $xml .= WGXML::endTag();
        }
        $xml .= WGXML::endTag();
        $xml .= WGXML::endTag();
        echo $xml;
        drupal_exit();
        break;
      case 'json':
      default:
        drupal_json_output($ret);
        drupal_exit();
        break;
    }
  }

  public static function time_to_mysql_datetime($t) {
    $t = date("Y-m-d H:i:s", $t);
    return $t;
  }

  public static function str_to_mysql_datetime($s) {
    $t = date("Y-m-d H:i:s", strtotime($s));
    return $t;
  }

  public static function to_utf8($str) {
    $str = preg_replace('/[\x00-\x08\x10\x0B\x0C\x0E-\x19\x7F]|(?<=^|[\x00-\x7F])[\x80-\xBF]+|([\xC0\xC1]|[\xF0-\xFF])[\x80-\xBF]*|[\xC2-\xDF]((?![\x80-\xBF])|[\x80-\xBF]{2,})|[\xE0-\xEF](([\x80-\xBF](?![\x80-\xBF]))|(?![\x80-\xBF]{2})|[\x80-\xBF]{3,})/',
                        '�', $str );
    $str = preg_replace('/\xE0[\x80-\x9F][\x80-\xBF]|\xED[\xA0-\xBF][\x80-\xBF]/S','?', $str );
    //$str = @iconv("UTF-8", "UTF-8//IGNORE", $str );
    return $str;
  }

  public static function sanitize($string) {
    $strip = array("~", "`", "!", "@", "#", "$", "%", "^", "&", "*", "(", ")", "_", "=", "+", "[", "{", "]",
                   "}", "\\", "|", ";", ":", "\"", "'", "&#8216;", "&#8217;", "&#8220;", "&#8221;", "&#8211;", "&#8212;",
                   "â€”", "â€“", ",", "<", ".", ">", "/", "?");
    $clean = trim(str_replace($strip, "_", strip_tags($string)));
    $clean = preg_replace('/\s+/', "-", $clean);
    $clean = mb_strtolower($clean, 'UTF-8');
    return $clean;
  }

  public static function explode_get_first($del, $str) {
    $ar = explode($del, $str);
    $f = array_shift($ar);
    return $f;
  }
  /*
   * empty, false, 0 and null coalesce
   */
  public static function ec($var, $default = '') {
    if(!isset($var)) return $default;
    if(!$var) return $default;
    if(empty($var)) return $default;
    return $var;
  }

  /**
   * recursive rm dir
   */
  public static function rrmdir($dir, $include_self = true) {
    if(!file_exists($dir)) return;
    $files = array_diff(scandir($dir), array('.','..'));
    foreach ($files as $file) {
      $fullname = $dir.'/'.$file;
      if(is_dir($fullname)) {
        self::rrmdir($fullname);
      } else {
        unlink($fullname);
      }
    }
    if($include_self) {
      rmdir($dir);
    }
  }

  public static function object_get($o, $properties, $default=null) {
    if(!$o) return $default;
    if(!is_object($o)) return false;
    if(is_array($properties)) {
      $ref = &$o;
      foreach($properties as $k) {
        if(property_exists($ref, $k) && (self::object_get($ref, $k) !== FALSE)) {
          $ref = &$ref->$k;
        } else {
          return $default;
        }
      }
      return $ref;
    } else {
      //if(!is_array($ar) || is_null($parents) || $parents === false) return $default;
      if(!property_exists($o, $properties)) return $default;
    }
    return $o->$properties;
  }

  public static function get_timspan_current_month() {
    $now=time();
    $d=mktime(00, 00, 00, date('m', $now), 1, date('Y', $now));
    return $d;
  }

  public static function create_temp_file($ext = 'temp', $path = '/tmp') {
    $fn = $path.'/'.self::random_string(8).'.'.$ext;
    return $fn;
  }

  public static function array_key_default(&$ar, $key, $default) {
    if(!array_key_exists($key, $ar)) {
      $ar[$key] = $default;
    }
  }

  public static function array_clean_array($arr, $default = '') {
    $r = [];
    foreach($arr as $k=>$v) {
      $r[$k] = $default;
    }
    return $r;
  }

  public static function array_clean_keep_exists(&$keeped, $source) {
    foreach($source as $k=>$v) {
      if(array_key_exists($k, $keeped)) {
        $keeped[$k] = $v;
      }
    }
  }

  /**
   * extend an array with default values
   * @param $ar
   * @param $default
   * @return
   */

  public static function array_extend(&$ar, $default) {
    if(!$ar) $ar = array();
  foreach($default as $key=>$value) {
      if(!array_key_exists($key, $ar)) $ar[$key] = $value;
    }
  }

  /**
   * @improvement of PHP function array_merge
   * allow null parameters
   **/

  public static function array_merge($ar1, $ar2) {
    $args = func_get_args();
    foreach($args as &$arg) {
      if(!is_array($arg)) $arg = array();
    }
    return call_user_func_array('array_merge', $args);
  }

  public static function array_get($ar, $parents, $default=null) {
    if(!$ar) return $default;
    if(!is_array($ar)) return false;
    if(is_array($parents)) {
      $ref = &$ar;
      foreach($parents as $k) {
        if(self::array_get($ref, $k) !== FALSE) {
          $ref = &$ref[$k];
        } else {
          return $default;
        }
      }
      return $ref;
    } else {
      if(!is_array($ar) || is_null($parents) || $parents === false) return $default;
      if(!array_key_exists($parents, $ar)) return $default;
    }
    return $ar[$parents];
  }

  public static function array_get_first($ar) {
    $f = array_shift($ar);
    return $f;
  }

  /**
   * Set value to array (nested or one dimension) with default value.
   *
   * @param $ar
   *   array.
   * @param $key
   *   key name
   * @param $value
   *   value
   * @param $override
   *   if @key exists, override or not
   * @return
   *   true: set.
   *   false: not set, may be key already exists when $override is false
   */
  public static function array_set(&$ar, $key, $value, $override = FALSE) {
    if($override || !array_key_exists($key, $ar)) {
      $ar[$key] = $value;
      return TRUE;
    }
    return FALSE;
  }

  /********************** string **************************/
  public static function str_replace_array($s, $replaces) {
    $s = str_replace(array_keys($replaces), array_values($replaces), $s);
    return $s;
  }

  public static function random_string($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
      $randomString .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $randomString;
  }

  public static function to_timestamp($t) {
    if(!is_numeric($t)) $t = strtotime($t);
    return $t;
  }

  /********************** simple debug***********************/
  public static function dbug($v, $flag="", $printfriendly = TRUE) {
    ob_start();
    if($flag) echo 'FLAG: '.$flag."\n";
    if(is_string($v)) echo $v."\n";
    else var_export($v);
    $s = ob_get_contents();
    ob_end_clean();
    if($printfriendly) {
      $s0 = "<div style='border:1px solid #ccc;background:LightGreen;width:800px; '>";
      if(!empty($flag)) $s0 .= "<h1>$flag</h1>";
      $s1 = htmlspecialchars($s);
      $s1 = str_replace("\n", "<br/>", $s1);
      $s1 = str_replace(" ", "&nbsp;", $s1);
      $s0 .= $s1;
      $s0 .= "</div>";
      $s = $s0;
    }
    echo $s;
  }

  public static function dbugmessage($v, $flag="", $printfriendly = TRUE) {
    ob_start();
    if($flag) echo 'FLAG: '.$flag."\n";
    if(is_string($v)) echo $v."\n";
    else var_export($v);
    $s = ob_get_contents();
    ob_end_clean();
    if($printfriendly) {
      $s0 = "<div style='border:1px solid #ccc;background:LightGreen;width:800px; '>";
      if(!empty($flag)) $s0 .= "<h1>$flag</h1>";
      $s1 = htmlspecialchars($s);
      $s1 = str_replace("\n", "<br/>", $s1);
      $s1 = str_replace(" ", "&nbsp;", $s1);
      $s0 .= $s1;
      $s0 .= "</div>";
      $s = $s0;
    }
    drupal_set_message($s);
  }

  public static function dnotice($msg, $echo = false) {
    $level = E_USER_NOTICE;
    trigger_error($msg, $level);
    if($echo) {
      echo $msg."\n";
    }
  }

  public static function dobjiterate($o, $deep = 1, $return = false) {
    $t = gettype($o);
    $ar = self::_darrayiterate($o, $deep);
    if($return) {
      $r = var_export($ar, true);
      return $r;
    } else {
      self::dbug($ar, $t);
    }
  }

  public static function _darrayiterate(&$o, $deep = 1) {
    $ar = array();
    foreach($o as $key => &$value) {
      $t = gettype($value);
      switch($t) {
        case 'string':
        case 'integer':
          $c = "$key: ($t) $value";
          break;
        case 'boolean':
          $c = "$key: ($t) ".(($value)?'true':'false');
          break;
        case 'object':
          $c = $key.':'.$t;

        default:
          $c = $key.':'.$t;
      }

      if($deep > 1) {
        if($t == 'array') {
          $c = array('self' => $c);
          $child = self::_darrayiterate($value, $deep-1);
          $c['child'] = $child;
        } else if($t == 'object') {
          $c = array('self' => $c);
          $child = self::_darrayiterate($value, $deep-1);
          $c['child'] = $child;
        }

      }
      $ar[] = $c;
    }
    return $ar;
  }

  public static function progressinfo($c, $step = 10) {
    if($c % $step == 0 ) {
      $mm = memory_get_usage();
      $mm = ceil($mm/1024);
      WGPHP::dnotice('* '.$c.' '.$mm);
    }
  }

  /**** fetchURL **/
  public static function fetch_url_data($url) {
    return self::_fetch_url_data($url);
  }

  public static function fetch_url_data_curl($url) {
    return self::_fetch_url_data2($url);
  }

  private static function _fetch_url_data($url) {
    $data = false;
    sleep(1);
    if(($data = self::_fetch_url_data0($url))) return $data;
    if(($data = self::_fetch_url_data1($url))) return $data;
    if(($data = self::_fetch_url_data2($url))) return $data;
    return $data;
  }

  private static function _fetch_url_data0($url) {
    $data  = @file_get_contents($url);
    return $data;
  }

  private static function _fetch_url_data1($url) {
    $opts = array(
              'http'=>array('method'=>"GET", 'header'=>"Accept-language: en\r\n" . "Cookie: foo=bar\r\n")
            );
    $context = stream_context_create($opts);
    $data = @file_get_contents($url, false, $context);
    return $data;
  }

  private static function _fetch_url_data2($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $output = curl_exec($ch);
    return $output;
  }



}
