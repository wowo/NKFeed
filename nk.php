<?php

class NKFeed
{
  private $login = '';
  private $password = '';
  private $curl = NULL;

  public function NKFeed()
  {
    $this->loadCredentials();
  }

  protected function loadCredentials()
  {
    if ($_SERVER['argc'] < 3) {
      throw new Exception('Pass login and password');
    }
    $this->login    = @$_SERVER['argv'][1];
    $this->password = @$_SERVER['argv'][2];
  }

  protected function getContent()
  {
    $curl = curl_init();
    $data = sprintf("login=%s&password=%s", $this->login, $this->password);
    curl_setopt_array($curl, array(
      CURLOPT_COOKIEJAR  => "/tmp/nk.cookie",
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_POSTFIELDS => $data,
      CURLOPT_URL        => "http://nasza-klasa.pl/login",
      CURLOPT_USERAGENT  => "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1",
      CURLOPT_RETURNTRANSFER => true,
      )
    );
    $html = curl_exec($curl);
    if (($code = curl_getinfo($curl, CURLINFO_HTTP_CODE)) != 200) {
      throw new Exception(sprintf('Curl call returned status code %d', $code));
    }
    $html = $this->cleanHtml($html);
    $html = $this->repairHtml($html);
    return $html;
  }

  protected function cleanHtml($html)
  {
    $html = str_replace('&nbsp;', '', $html);
    return $html;
  }

  protected function repairHtml($html)
  {
    $options = array(
      'output-xhtml' => true, 
      'clean' => true, 
    );
    $tidy = new tidy();
    $tidy->parseString($html, $options, 'UTF8');
    $tidy->cleanRepair();
    return (string)$tidy;
  }

  protected function convertHtml($html)
  {
    $html = iconv('ISO-8859-2', 'UTF-8', $html);
    return $html;
  }

  protected function getXML($html)
  {
    $xml = simplexml_load_string($html);
    $xml->registerXPathNamespace("xmlns", "http://www.w3.org/1999/xhtml");
    return $xml;
  }

  public function getFriendsPhotos()
  {
    $xml  = $this->getXML($this->getContent());
    $friends = $xml->xpath("//xmlns:div[@id='friends_photos_box']//xmlns:div[@class='thumb']");
    $result  = array();
    foreach ($friends as $friend) {
      $result[] = array(
        'user' => trim(str_replace("\n", " ", (string)$friend->div[1]->a)),
        'date' => trim(str_replace("\n", " ", (string)$friend->div[1])),
        'img'  => trim((string)$friend->div[0]->a->img['src']),
      );
    }
    return $result;
  }
}

$feed = new NKFeed();
print_r($feed->getFriendsPhotos());
