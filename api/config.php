<?php

$DEBUG = true;
$DEBUG_LOG = false; # debug messages (i.e. request info) in log files
$LOGDIR = '../logs'; # relative to this file
$FILES = array(
  'jwt-secret'    => './jwt.secret', # secret for JWT session tokens
  'git-sha'       => './git-sha', # git sha (for /info)
  'composer-json' => './composer.json' # contains name and version (for /info)
);
$EXPIRATION_SECS = 3600 * 24; # session expiration in seconds (use <=0 for no expiration)
$CORS_ALLOW_ORIGINS = array('https://kg.laurentlang.com', 'https://sketch.process.studio', 'https://islandrabe.com', 'https://knigge.chat'); # for Access-Control-Allow-Origin header
$ALLOW_HOSTS = array('*'); # check $_SERVER['HTTP_HOST'] and $_SERVER["REQUEST_SCHEME"]
$LOG_MESSAGE_MAX_LENGTH = 2048; # includes timestamp(s)

?>