#!/usr/local/bin/php5
<?php
require_once('NKProxyFinder.class.php');

try {
  $proxyFinder = new NKProxyFinder(@$_SERVER['argv'][1]);
  $proxyFinder->saveValidProxiesToFile(@$_SERVER['argv'][2]);
} catch (Exception $e) {
  printf("[err] Catched exception of type %s with message: %s\n", get_class($e), $e->getMessage());
  exit(1);
}
