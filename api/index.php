<?php
require __DIR__ . '/../vendor/autoload.php';
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
$jwt_secret = trim(file_get_contents('jwt.secret'));


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
  $path = get_logfile($ts);
  $timestamp = '[' . util\timestamp() . '] ';
  $data = $timestamp . strval($message) . PHP_EOL;
  return file_put_contents($path, $data, FILE_APPEND);
}

# get auth token from request
function get_auth_token() {
  # check Authorization Header
  $headers = apache_request_headers();
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
  global $CORS_ALLOW_ORIGIN, $ALLOW_HOSTS;
  # send cors header
  header("Access-Control-Allow-Origin: " . $CORS_ALLOW_ORIGIN);
  # check http host
  if ( in_array('*', $ALLOW_HOSTS) ) return;
  if ( !in_array(strtolower($_SERVER["HTTP_HOST"]), $ALLOW_HOSTS) ) {
    // error(403, array('error' => 'forbidden'));
    error(403);
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
  session_start();
  echo 'session id: ' . session_id() . PHP_EOL;
  // if (empty($_SESSION)) {
  //   $_SESSION['id'] = session_id();
  // }
  echo 'session data: ';
  print_r($_SESSION);
  echo 'current id: ' . util\id() . PHP_EOL;
  echo 'auth: ' . get_auth_token() . PHP_EOL;
  echo '';
  echo 'headers: ';
  print_r(apache_request_headers());
  // echo '';
  // echo '$_SERVER: ';
  // print_r($_SERVER);
  echo '</pre>';
}

function info() {
  $string = file_get_contents("../composer.json");
  $json = json_decode($string, true);
  $git_sha = file_get_contents("../git-sha");
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
  $time = microtime(true);
  $payload = array(
    'ts' => $time, # timestamp
  );
  # expiration 
  if ($EXPIRATION_SECS > 0) {
    $payload['exp'] = intval($time) + $EXPIRATION_SECS;
  }
  json(array(
    'token' => JWT::encode($payload, $jwt_secret),
  ));
  exit();
}
elseif ($route == 'log') {
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
  
  if (! array_key_exists('message', $query) ) {
    error(400, array('error' => 'message required'));
    exit();
  }
  log_message( $token->ts, $query['message'] );
  ok();
  exit();
}
elseif ($route == '') {
  info();
  exit();
}
else {
  if ($DEBUG) {
    debug_status();
    exit();
  }
  error(404);
}
?>