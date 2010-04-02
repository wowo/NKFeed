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
      curl_close($curl);
      $html = $this->repairHtml($html);
      $this->content = $html;
    }
    return $this->content;
  }

  protected function getImage($src)
  {
    $curl = curl_init();
    curl_setopt_array($curl, array(
      CURLOPT_COOKIEJAR  => "/tmp/nk.cookie",
      CURLOPT_URL        => $src,
      CURLOPT_USERAGENT  => "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1",
      CURLOPT_RETURNTRANSFER => true,
      )
    );
    $image = curl_exec($curl);
    if (($code = curl_getinfo($curl, CURLINFO_HTTP_CODE)) != 200) {
      throw new Exception(sprintf('Curl call returned status code %d', $code));
    }
    curl_close($curl);
    return base64_encode($image);
  }

  protected function repairHtml($html)
  {
    require_once 'htmlpurifier-4.0.0/library/HTMLPurifier.auto.php';
    $cacheDir = '/tmp/htmlPurifierCache';
    if (!file_exists($cacheDir)) {
      mkdir($cacheDir);
    }
    $config = HTMLPurifier_Config::createDefault();
    $config->set('Cache.SerializerPath', $cacheDir);
    $config->set('HTML.Doctype', 'XHTML 1.0 Strict');
    $config->set('Attr.EnableID', true);
    $purifier = new HTMLPurifier($config);
    $html = sprintf("<div>%s</div>", $purifier->purify($html));
    return $html;
  }

  protected function convertHtml($html)
  {
    $html = iconv('ISO-8859-2', 'UTF-8', $html);
    return $html;
  }

  protected function getXML($html)
  {
    $xml = simplexml_load_string($html);
    return $xml;
  }

  public function getFriendsPhotos()
  {
    $result  = array();
    $xml  = $this->getXML($this->getContent());
    $friends = $xml->xpath("//div[@id='friends_photos_box']//div[@class='thumb']");
    foreach (is_array($friends) ? $friends : array() as $friend) {
      $img    = $friend->xpath(".//img[@class='thumb']");
      $author = $friend->xpath(".//div[@class='author']/a");
      $date   = $friend->xpath(".//div[@class='author']");
      $result[] = array(
        'user' => trim(str_replace("\n", " ", (string)$author[0])),
        'date' => trim(str_replace("\n", " ", (string)$date[0])),
        'src'  => (string)$img[0]['src'],
        'img'  => $this->getImage($img[0]['src']),
      );
    }
    return $result;
  }

  public function getEvents()
  {
    $xml  = $this->getXML($this->getContent());
    $xpath = "//table[contains(@class, 'mine')]//a[@class='photo_thmb']/img | ";
    $xpath .= "//table[contains(@class, 'mine')]//p[@class='comment']/a | ";
    $xpath .= "//table[contains(@class, 'mine')]//td[@class='time'] ";
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
