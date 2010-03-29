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
      throw new Exception('Podaj login i hasło jako parametry');
    }
    $login    = @$_SERVER['argv'][1];
    $password = @$_SERVER['argv'][2];
  }

  protected function getContent()
  {
    $curl = curl_init();
    curl_setopt_array($curl, array(
      CURLOPT_COOKIEJAR  => "/tmp/nk.cookie",
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_POSTFIELDS => sprintf("login=%s&password=%s", $login, $password),
      CURLOPT_URL        => "http://nasza-klasa.pl/login",
      CURLOPT_USERAGENT  => "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1",
      CURLOPT_RETURNTRANSFER => true,
      )
    );
    $html = curl_exec($curl);
    $html = $this->cleanHtml($html);
    $html = $this->repairHtml($html);
    return $html;
  }

  protected function cleanHtml($html)
  {
    $html = str_replace('&nbsp;', '', $html);
    //$html = str_replace('xmlns=', 'ns=', $html);
    return $html;
  }

  protected function repairHtml($html)
  {
    $tidy = new tidy();
    $tidy->parseString($html, array('output-xhtml' => true, 'clean' => true, 'show-body-only' => false));
    $tidy->cleanRepair();
    return (string)$tidy;
  }

  protected function getXML($html)
  {
    $xml = simplexml_load_string($html);
    $xml->registerXPathNamespace("xmlns", "http://www.w3.org/1999/xhtml");
    return $xml;
  }

  public function getFriendsPhotos()
  {
    $html = $this->getContent();
    $xml  = $this->getHtml($html);
    var_dump($xml->xpath("//xmlns:div[@id='friends_photos_box']//xmlns:div[@class='thumb']"));
  }
}

$feed = new NKFeed();
$feed->getFriendsPhotos();
