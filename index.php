<?php

include_once('NKFeed.class.php');

//$feed = new NKFeed(@$_SERVER['argv'][1], @$_SERVER['argv'][2]);
$feed = new NKFeed(@$_REQUEST['login'], @$_REQUEST['password']);
$output = array();
if (@$_REQUEST['friends']) {
  $output['friends'] = $feed->getFriendsPhotos();
}
if (@$_REQUEST['events']) {
  $output['events'] = $feed->getEvents();
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
