<?php
include_once('NKException.class.php');
include_once('NKProxyFinder.class.php');

/**
 * NKFeed - retrieves data from nasza-klasa.pl
 * 
 * @package default
 * @version $id$
 * @copyright 
 * @author Wojciech Sznapka <wojciech@sznapka.pl> 
 * @license 
 */
class NKFeed
{
  private $login    = '';
  private $password = '';
  private $content  = '';
  private $useProxy = false;
  private $proxySource = 'data/proxies.csv';

  /**
   * the contructor
   * 
   * @param mixed $login 
   * @param mixed $password 
   * @access public
   * @return void
   */
  public function NKFeed($login, $password, $useProxy = false)
  {
    $this->login    = $login;
    $this->password = $password;
    $this->useProxy = $useProxy;
  }

  /**
   * gets html content of nasza-klasa.pl homepage
   * 
   * @throws NKException when login fails
   * @access protected
   * @return string
   */
  protected function getContent()
  {
    if (empty($this->content)) {
      $curl = curl_init();
      $data = sprintf("login=%s&password=%s", $this->login, $this->password);
      curl_setopt_array($curl, array(
        CURLOPT_COOKIEJAR  => "/tmp/nk.cookie",
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_POSTFIELDS => $data,
        CURLOPT_REFERER => "http://nasza-klasa.pl",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_URL => "http://nasza-klasa.pl/login",
        CURLOPT_USERAGENT  => "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1",
        )
      );
      if ($this->useProxy) {
        $proxy = $this->getProxy();
        curl_setopt($curl, CURLOPT_PROXY, $proxy['ip']);
        curl_setopt($curl, CURLOPT_PROXYPORT, $proxy['port']);
      }
      $html = curl_exec($curl);
      if ($error = curl_error($curl)) {
        throw new NKException(NULL, NKException::CURL_ERROR, NULL, array(), sprintf('error: %s, login: %s, proxy: %s', $error, $this->login, $proxy['ip']));
      }
      if (($code = curl_getinfo($curl, CURLINFO_HTTP_CODE)) != 200) {
        throw new NKException(NULL, NKException::CURL_STATUS_CODE, NULL, array(), sprintf('code: %d, login: %s, proxy: %s', $code, $this->login, $proxy['ip']));
      }
      curl_close($curl);
      $html = $this->repairHtml($html);
      $this->content = $html;
    }
    if (!$this->isLoggedIn($this->content)) {
      throw new NKException(NULL, NKException::LOGIN_FAILED, NULL, array(), sprintf('login: %s', $this->login));
    }
    return $this->content;
  }

  /**
   * gets proxy from proxy source
   * 
   * @throws NKException when proxy source isn't readable
   * @access protected
   * @return string
   */
  protected function getProxy()
  {
    if (is_readable($this->proxySource)) {
      $proxyFinder = new NKProxyFinder($this->proxySource);
      return $proxyFinder->getRandomProxy();
    } else {
      throw new NKException(NULL, NKException::PROXY_SOURCE_NOT_READABLE, NULL, array(), $this->proxySource);
    }
  }
            
  /**
   * check if user is logged in (looking for url login)
   * 
   * @param mixed $html 
   * @access protected
   * @return boolean
   */
  protected function isLoggedIn($html)
  {
    return (stripos($html, 'nasza-klasa.pl/login') === false);
  }

  /**
   * gets image thumbnail from nasza-klasa.pl
   * 
   * @param mixed $src 
   * @access protected
   * @return string
   */
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
    if ($error = curl_error($curl)) {
      throw new NKException(NULL, NKException::CURL_ERROR, NULL, array(), sprintf('error: %s, login: %s, proxy: %s', $error, $this->login, $proxy['ip']));
    }
    if (($code = curl_getinfo($curl, CURLINFO_HTTP_CODE)) != 200) {
      throw new NKException(NULL, NKException::CURL_STATUS_CODE, NULL, array(), sprintf('code: %d, login: %s, proxy: %s', $code, $this->login, $proxy['ip']));
    }
    curl_close($curl);
    return base64_encode($image);
  }

  /**
   * repairs broken nasza-klasa.pl html, it uses HTMLPurifier library
   * 
   * @param mixed $html 
   * @access protected
   * @return string
   */
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

  /**
   * converts html from iso-8859-2 charset to utf-8
   * 
   * @param mixed $html 
   * @access protected
   * @return string
   */
  protected function convertHtml($html)
  {
    $html = iconv('ISO-8859-2', 'UTF-8', $html);
    return $html;
  }

  /**
   * converts html into simplexml xml object
   * 
   * @param mixed $html 
   * @access protected
   * @return SimpleXMLObject
   */
  protected function getXML($html)
  {
    $xml = simplexml_load_string($html);
    return $xml;
  }

  /**
   * get friends photos 
   * 
   * @access public
   * @return array
   */
  public function getFriendsPhotos()
  {
    $result  = array();
    $xml  = $this->getXML($this->getContent());
    $friends = $xml->xpath("//div[@id='friends_photos_box']//div[@class='thumb']");
    foreach (is_array($friends) ? $friends : array() as $friend) {
      $img    = $friend->xpath(".//img[@class='thumb']");
      $author = $friend->xpath(".//div[@class='author']/a");
      $date   = $friend->xpath(".//div[@class='author']");
      $url    = $friend->xpath(".//a");
      $result[] = array(
        'user' => trim(str_replace("\n", " ", (string)$author[0])),
        'date' => trim(str_replace("\n", " ", (string)$date[0])),
        'src'  => (string)$img[0]['src'],
        'img'  => $this->getImage($img[0]['src']),
        'url'  => sprintf("http://nasza-klasa.pl%s", $url[0]['href']),
      );
    }
    return $result;
  }

  /**
   * get events 
   * 
   * @access public
   * @return array
   */
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
