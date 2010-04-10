<?php
include_once('NKException.class.php');

/**
 * Finds proxy to connect to nasza-klasa.pl and retrieves valid proxies (those with you can connect to nasza-klasa.pl) from proxy list
 * 
 * @package default
 * @version $id$
 * @copyright 
 * @author Wojciech Sznapka <wojciech@sznapka.pl> 
 * @license 
 */
class NKProxyFinder
{
  private $csvFilepath = '';

  const PROXY_IP   = 0;
  const PROXY_PORT = 1;

  /**
   * The constructor
   * 
   * @param mixed $filepath 
   * @access public
   * @return void
   */
  public function NKProxyFinder($filepath)
  {
    if (!is_readable($filepath)) {
      throw new NKException(sprintf('filepath "%s" is not redable!', $filepath));
    }
    $this->csvFilepath = $filepath;
  }

  /**
   * gets proxies with which there is ability to connect to nasza-klasa.pl.  Proxies are taken from $this->csvFilepath
   * 
   * @access public
   * @return array
   */
  public function getValidProxies()
  {
    $proxies = array();
    foreach ($this->readCSV($this->csvFilepath) as $row) {
      $proxy = $this->getProxyFromRecord($row);
      if ($this->isValidProxy($row[self::PROXY_IP], $row[self::PROXY_PORT])) {
        $proxies[] = $proxy;
      }
    }
    return $proxies;
  }

  /**
   * get one random proxy from $this->csvFilepath, as an assoc array: array(ip => <string>, port => <int>)
   * 
   * @access public
   * @return array
   */
  public function getRandomProxy()
  {
    $proxies = $this->readCSV($this->csvFilepath);
    if (count($proxies) == 0) {
      throw new NKException('No proxies in source');
    }
    return $this->getProxyFromRecord($proxies[array_rand($proxies)]);
  }

  /**
   * saves valid proxies to file 
   * 
   * @see NKProxyFinder::getValidProxies
   * @param string $filepath 
   * @access public
   * @return void
   */
  public function saveValidProxiesToFile($filepath = 'data/proxies.csv')
  {
    if (!is_writable(dirname($filepath))) {
      throw new NKException(sprintf('filepath "%s" is not writable!', dirname($filepath)));
    }

    $output  = array();
    foreach ($this->getValidProxies() as $row) {
      $output[] = implode(";", $row);
    }
    file_put_contents($filepath, implode("\n", $output));
  }

  /**
   * get proxy as an array from csv record 
   * 
   * @param mixed $record 
   * @access private
   * @return array(ip => <string>, port => <int>)
   */
  private function getProxyFromRecord($record)
  {
    return array('ip' => $record[self::PROXY_IP], 'port' => $record[self::PROXY_PORT]);
  }

  /**
   * checks is proxy is valid - not banned on nasza-klasa.pl's firewall.
   * It simply curls nasza-klasa with 3 second timeout and check if there's no error and response code is 200
   * 
   * @param mixed $ip 
   * @param mixed $port 
   * @access private
   * @return boolean
   */
  private function isValidProxy($ip, $port)
  {
    $curl = curl_init();
    curl_setopt_array($curl, array(
      CURLOPT_REFERER => "http://nasza-klasa.pl",
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_URL => "http://nasza-klasa.pl",
      CURLOPT_USERAGENT  => "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1",
      CURLOPT_PROXY => $ip,
      CURLOPT_PROXYPORT => $port,
      CURLOPT_TIMEOUT => 3,
      )
    );
    $html = curl_exec($curl);
    if ($error = curl_error($curl)) {
      return false;
    }
    if (($code = curl_getinfo($curl, CURLINFO_HTTP_CODE)) != 200) {
      return false;
    }
    curl_close($curl);
    return true;
  }

  /**
   * reads CSV and returns it as an array
   * 
   * @param mixed $filepath 
   * @access private
   * @return array
   */
  private function readCSV ($filepath)
  {
    $csv = file($filepath);
    foreach ($csv as &$record) {
      $record = explode(';', $record);
    }
    return $csv;
  }
}
