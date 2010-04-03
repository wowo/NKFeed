<?php

include_once('NKFeed.class.php');

//$feed = new NKFeed(@$_SERVER['argv'][1], @$_SERVER['argv'][2]);
$feed = new NKFeed(@$_REQUEST['login'], @$_REQUEST['password']);
$output = array();
try {
  if (@$_REQUEST['friends']) {
    $output['friends'] = $feed->getFriendsPhotos();
  }
  if (@$_REQUEST['events']) {
    $output['events'] = $feed->getEvents();
  }
} catch (NKException $e) {
  if ($e->getCode() == NKException::LOGIN_FAILED) {
    header("HTTP/1.0 401 Unauthorized", true, 401);
  } else {
    header("HTTP/1.0 409 Conflict", true, 409);
  }
  $output = array('error' => $e->getMessage());
} catch (Exception $e) {
  header("HTTP/1.0 503 Service Unavailable", true, 503);
  $output = array(
    'error' => 'Wystąpił błąd, proszę spróbować później',
    'fullError' => $e->getMessage(),
  );
}


switch (@$_REQUEST['format']) {
  case 'xml':
    echo 'xml';
    break;
  case 'html':
    echo 'html';
    break;
  default:
  case 'json':
    echo json_encode($output);
    break;
}
