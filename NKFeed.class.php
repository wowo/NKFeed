<?php

class NKFeed
{
  private $login = '';
  private $password = '';
  private $content = '';

  public function NKFeed($login, $password)
  {
    $this->login    = $login;
    $this->password = $password;
  }

  protected function getContent()
  {
    if (empty($this->content)) {
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
      $this->content = $html;
    }
    return $this->content;
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
    $result  = array();
    $xml  = $this->getXML($this->getContent());
    $friends = $xml->xpath("//xmlns:div[@id='friends_photos_box']//xmlns:div[@class='thumb']");
    foreach (is_array($friends) ? $friends : array() as $friend) {
      $result[] = array(
        'user' => trim(str_replace("\n", " ", (string)$friend->div[1]->a)),
        'date' => trim(str_replace("\n", " ", (string)$friend->div[1])),
        'img'  => trim((string)$friend->div[0]->a->img['src']),
      );
    }
    return $result;
  }

  public function getEvents()
  {
    $xml  = $this->getXML($this->getContent());
    $xpath = "//xmlns:table[contains(@class, 'mine')]//xmlns:a[@class='photo_thmb']/xmlns:img | ";
    $xpath .= "//xmlns:table[contains(@class, 'mine')]//xmlns:p[@class='comment']/xmlns:a | ";
    $xpath .= "//xmlns:table[contains(@class, 'mine')]//xmlns:td[@class='time'] ";
    $events = $xml->xpath($xpath);

    $i = 0;
    $result  = array();
    $subresult = array();
    foreach ((array)$events as $event) {
      if ($i == 0) {
        $subresult['thumb'] = (string)$event['src'];
      } elseif ($i == 1) {
        $subresult['comment'] = str_replace("\n", " ", (string)$event);
      } elseif ($i == 2) {
        $subresult['time'] = (string)$event;
      }
      if ($i++ == 2) {
        $result[] = $subresult;
        $i = 0;
      }
    }
    return $result;
  }
}
