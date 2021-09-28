<?php

$DEBUG = true;
$LOGDIR = '../logs_test'; # relative to this file
$FILES = array(
  'jwt-secret'    => './jwt.secret',
  'git-sha'       => './git-sha',
  'composer-json' => './composer.json'
);
$EXPIRATION_SECS = 3600 * 24; # session expiration in seconds (use <=0 for no expiration)
$CORS_ALLOW_ORIGIN = '*';
$ALLOW_HOSTS = array('*');

?>