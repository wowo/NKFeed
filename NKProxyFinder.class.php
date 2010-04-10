<?php
include_once('NKException.class.php');

class NKProxyFinder
{
  private $csvFilepath = '';

  const PROXY_IP = 0;
  const PROXY_PORT = 1;

  public function NKProxyFinder($filepath)
  {
    if (!is_readable($filepath)) {
      throw new NKException(sprintf('filepath "%s" is not redable!', $filepath));
    }
    $this->csvFilepath = $filepath;
  }

  public function getValidProxies()
  {
    $proxies = array();
    foreach ($this->readCSV($this->csvFilepath) as $row) {
      $proxy = array('ip' => $row[self::PROXY_IP], 'port' => $row[self::PROXY_PORT]);
      if ($this->isValidProxy($row[self::PROXY_IP], $row[self::PROXY_PORT])) {
        $proxies[] = $proxy;
      }
    }
    return $proxies;
  }

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

  private function readCSV ($filepath)
  {
    $csv = file($filepath);
    foreach ($csv as &$record) {
      $record = explode(';', $record);
    }
    return $csv;
  }

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
}
