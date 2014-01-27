<?php
/**
 * DooMemCached class file.
 *
 * @author Leng Sheng Hong <darkredz@gmail.com>
 * @link http://www.doophp.com/
 * @copyright Copyright &copy; 2009 Leng Sheng Hong
 * @license http://www.doophp.com/license
 */


/**
 * DooMemCache provides caching methods utilizing the Memcache extension.
 *
 * If you have multiple servers for memcache, you would have to set it up in common.conf.php
 * <code>
 * // host, port, persistent, weight
 * $config['MEMCACHE'] = array(
 *                       array('192.168.1.31', '11211', true, 40),
 *                       array('192.168.1.23', '11211', true, 80)
 *                     );
 * </code>
 *
 * @author Leng Sheng Hong <darkredz@gmail.com>
 * @version $Id: DooMemCache.php 1000 2009-08-22 19:36:10
 * @package doo.cache
 * @since 1.1
 */

class DooMemCached
{
  /**
   * Memcached connection
   * @var Memcache
   */
  protected $_memcached;

  /**
   * Configurations of the connections
   * @var array
   */
  protected $_config;

  private $server_max_index;

  public function  __construct($conf = Null) {
    $this->_memcached = new Memcached();
    $this->_config = $conf;
    $count_servers = count($this->_config);
    $this->server_max_index = $count_servers - 1;
    // host, port, persistent, weight
    if ($conf !== Null){
      foreach ($conf as $c) {
        $weight = isset($c[2]) ? $c[2] : 0;
        $this->_memcached->addServer($c[0], $c[1], $weight);
      }
    }
  }

  /**
   * Adds a cache with an unique Id.
   *
   * @param string $id Cache Id
   * @param mixed $data Data to be stored
   * @param int $expire Seconds to expired
   * @param int $compressed To store the data in Zlib compressed format
   * @return bool True if success
   */
  public function set($id, $data, $expire = 0) {
    if ($expire){
      $this->setToCluster($id, $data, $expire);
    } else {
      $this->setToCluster($id, $data, 0);
    }
  }

  private function setToCluster($id, $data, $expire) {
    foreach ($this->_config as $k => $server) {
      $this->_memcached->setByKey("mcs_" . $k, $id, $data, $expire);
    }
  }

  /**
   * Retrieves a value from cache with an Id.
   *
   * @param string $id A unique key identifying the cache
   * @return mixed The value stored in cache. Return false if no cache found or already expired.
   */
  public function get($id) {
    $rand_server = rand(0, $this->server_max_index);
    return $this->_memcached->getByKey("mcs_" . $rand_server, $id);
  }

  /**
   * Deletes an APC data cache with an identifying Id
   *
   * @param string $id Id of the cache
   * @return bool True if success
   */
  public function flush($id) {
    $this->deleteFromCluster($id);
  }

  private function deleteFromCluster($id){
    foreach ($this->_config as $k => $server) {
      $this->_memcached->deleteByKey("mcs_" . $k, $id);
    }
  }

  /**
   * Deletes all data cache
   * @return bool True if success
   */
  public function flushAll() {
    return $this->_memcached->flush();
  }

  public function getServerList() {
    return $this->_memcached->getServerList();
  }

  public function getServerStatus() {
    return $this->_memcached->getStats();
  }

}

