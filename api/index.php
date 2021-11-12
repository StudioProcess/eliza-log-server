<?php
require __DIR__ . '/vendor/autoload.php';
include 'config.php';
include 'util.php';
use Firebase\JWT\JWT;


if ($DEBUG) {
  error_reporting(E_ALL);
  ini_set('display_errors', 1);
}

$dir = dirname($_SERVER['SCRIPT_NAME']);
$route = util\remove_prefix( $_SERVER['REQUEST_URI'], $dir); # remove dir
$route = preg_replace('/\?.*/', '', $route); # remove query string
$route = strtolower(trim($route, '/'));
$query = $_REQUEST;
$jwt_secret = trim(file_get_contents($FILES['jwt-secret']));
$headers = apache_request_headers();

function get_logfile($ts) {
  global $LOGDIR;
  $date = \DateTime::createFromFormat('U.u', $ts);
  $date->setTimezone(new \DateTimeZone('Europe/Vienna'));
  // $logdir = $_SERVER['DOCUMENT_ROOT'] . '/' . $LOGDIR . '/' . $date->format('Y-m');
  $logdir = $LOGDIR . '/' . $date->format('Y-m');
  if ( !file_exists($logdir) ) {
    mkdir($logdir, 0755, true);
  }
  // $path = $logdir . '/' . strval(util\id($ts)) . '.txt';
  $path = $logdir . '/' . $date->format('Y-m-d-His-u') . '.txt';
  return $path;
}

function log_message($ts, $message) {
  global $LOG_MESSAGE_MAX_LENGTH;
  $message = trim(strval($message));
  $path = get_logfile($ts);
  $timestamp = '[' . util\timestamp() . '] ';
  $data = $timestamp . $message . PHP_EOL;
  if (strlen($data) > $LOG_MESSAGE_MAX_LENGTH) {
    error(400, array('error' => 'message(s) exceeds max length'));
    exit();
  }
  return file_put_contents($path, $data, FILE_APPEND);
}

function request_info() {
  global $_SERVER;
  return $_SERVER['REQUEST_METHOD'] . " " . $_SERVER['REQUEST_URI'];
}

function log_debug($ts, $message, $prefix = 'DEBUG ') {
  global $DEBUG_LOG;
  if ($DEBUG_LOG) return log_message($ts, $prefix . $message);
  return NULL;
}

function log_messages($ts, $messages) {
  global $LOG_MESSAGE_MAX_LENGTH;
  $path = get_logfile($ts);
  $timestamp = '[' . util\timestamp() . '] ';
  $data = array_map(function($message) use ($timestamp) {
    return $timestamp . trim(strval($message));
  }, $messages);
  $data = implode(PHP_EOL, $data) . PHP_EOL;
  if (strlen($data) > $LOG_MESSAGE_MAX_LENGTH) {
    error(400, array('error' => 'message(s) exceeds max length'));
    exit();
  }
  return file_put_contents($path, $data, FILE_APPEND);
}

# get auth token from request
function get_auth_token() {
  # check Authorization Header
  global $headers;
  if (array_key_exists('Authorization', $headers)) {
    $auth = trim($headers['Authorization']);
    $matches = array();
    $res = preg_match('/Bearer (.*)/', $auth, $matches);
    if ($res) return $matches[1];
  }
  $key = 'session';
  # check $POST body for { token: 'xyz' }
  if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $body = file_get_contents('php://input');
    $data = json_decode($body, true);
    if (!is_null($data) && array_key_exists($key, $data)) {
      return $data[$key];
    }
  }
  # check query string
  if (array_key_exists($key, $_REQUEST)) {
    return $_REQUEST[$key];
  }
  return null;
}

function check_host() {
  global $CORS_ALLOW_ORIGINS, $ALLOW_HOSTS, $headers;
  # send cors header
  $origin = $headers['Origin'] ?? '';
  if ( in_array($origin, $CORS_ALLOW_ORIGINS) ) {
    header("Access-Control-Allow-Origin: " . $origin);
  } else if ( in_array('*', $CORS_ALLOW_ORIGINS) ) {
    header("Access-Control-Allow-Origin: *");
  }
  
  # check http(s) host
  if ( in_array('*', $ALLOW_HOSTS) ) return;
  if ( !in_array(strtolower($_SERVER["HTTP_HOST"]), $ALLOW_HOSTS) ) {
    // error(403, array('error' => 'forbidden'));
    error(403);
    exit();
  }
}

function allow_method_cors($method='GET') {
  global $_SERVER;
  if ( $_SERVER['REQUEST_METHOD'] == 'OPTIONS' ) {
    // Access-Control-Allow-Origin set by check_host()
    header("Access-Control-Allow-Methods: " . $method . ", OPTIONS" );
    header("Access-Control-Max-Age: 86400");
    ok(204); // No content
    exit();
  }
  if ( $method != $_SERVER['REQUEST_METHOD'] ) {
    error(405); // method not allowed
    exit();
  }
}

function debug_status() {
  global $dir, $route, $query, $jwt_secret;
  echo '<pre>' . PHP_EOL;
  echo 'php: ' . phpversion() . PHP_EOL;
  echo 'dir: ' . $dir . PHP_EOL;
  echo 'route: ' . $route . PHP_EOL;
  echo 'query: ';
  print_r($query);
  // session_start();
  // echo 'session id: ' . session_id() . PHP_EOL;
  // if (empty($_SESSION)) {
  //   $_SESSION['id'] = session_id();
  // }
  // echo 'session data: ';
  // print_r($_SESSION);
  // echo 'current id: ' . util\id() . PHP_EOL;
  echo 'current timestamp: ' . microtime(true) . PHP_EOL;
  echo 'auth: ' . get_auth_token() . PHP_EOL;
  echo '';
  echo 'headers: ';
  print_r(apache_request_headers());
  echo '';
  echo '$_SERVER: ';
  print_r($_SERVER);
  echo '</pre>';
}

function info() {
  global $FILES;
  $string = file_get_contents($FILES['composer-json']);
  $json = json_decode($string, true);
  if (is_null($json)) {
    $json = array('name' => '', 'description' => '', 'version' => '');
  }
  $git_sha = file_get_contents($FILES['git-sha']);
  if (!$git_sha) $git_sha = '';
  $git_sha = trim($git_sha);
  json(array(
    'name' => $json['name'],
    'description' => $json['description'],
    'version' => $json['version'],
    'git_sha' => $git_sha
  ));
}

check_host();

if ($route == 'session') {
  allow_method_cors();
  $time = microtime(true);
  $payload = array(
    'ts' => $time, # timestamp
  );
  # expiration 
  if ($EXPIRATION_SECS > 0) {
    $payload['exp'] = intval($time) + $EXPIRATION_SECS;
  }
  log_debug( $time, request_info() );
  json(array(
    'session' => JWT::encode($payload, $jwt_secret),
  ));
  exit();
}
elseif ($route == 'log') {
  allow_method_cors();
  $auth = get_auth_token();
  if ( is_null($auth) ) {
    error(401, array('error' => 'session required'));
    exit();
  }
  try {
    $token = JWT::decode($auth, $jwt_secret, array('HS256'));
  } catch (Exception $e) {
    if ($e instanceof Firebase\JWT\ExpiredException) {
      error(403, array('error' => 'session expired'));
      exit();
    }
    error(403, array('error' => 'invalid session'));
    exit();
  }
  if ( array_key_exists('message', $query) ) {
    log_debug( $token->ts, request_info() );
    log_message( $token->ts, $query['message'] );
  } else if ( array_key_exists('messages', $query) ) {
    log_debug( $token->ts, request_info() );
    $messages = json_decode($query['messages']);
    if ( $messages == NULL || (!is_array($messages)) || empty($messages) ) {
      error(400, array('error' => 'messages needs to be a JSON array of one or more strings'));
      exit();
    }
    log_messages( $token->ts, $messages );
  } else {
    error(400, array('error' => 'message(s) required'));
    exit();
  }
  ok();
  exit();
}
elseif ($route == '') {
  allow_method_cors();
  info();
  exit();
}
else {
  if ($DEBUG) {
    debug_status();
    exit();
  }
  error(404);
  exit();
}
?>