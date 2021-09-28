<?php
namespace util {

  function remove_prefix($str, $prefix) {
    if (substr($str, 0, strlen($prefix)) == $prefix) {
      $str = substr($str, strlen($prefix));
    }
    return $str;
  }

  # format: yyyy-mm-dd hh:mm:ss.mmm +zz:zz
  function timestamp() {
    $ts = \DateTime::createFromFormat('U.u', microtime(true));
    $ts->setTimezone(new \DateTimeZone('Europe/Vienna'));
    return $ts->format('Y-m-d H:i:s.v P');
  }

  # base36 microsecond-based id
  function id($ts = null) {
    // $time = hrtime(true);
    if (is_null($ts)) {
      $ts = microtime(true);
    }
    $time = intval( $ts * 1000000 ); # time in microseconds
    $id = base_convert( strval($time), 10, 36);
    return $id;
  }

}

# global functions
namespace {
  
  function json($data) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
  }

  function error($code, $data = null) {
    http_response_code($code);
    if (! is_null($data)) {
      header('Content-Type: application/json; charset=utf-8');
      echo json_encode($data);
    }
  }

  function ok($code = 200) {
    http_response_code($code);
  }
  
}
?>