<?php

class memTest{

  private $_connections = array();
  private $_connection_types = array();
  private $times = 10000;
  private $results = array();
  private $keys = array();

  function __construct() {
    $this->createConnections();
    
    $env_times = getenv( 'memtest_times' );
    var_dump( is_numeric( $env_times ) );
    if ( is_numeric( $env_times ) ) {
      $this->times = (int) $env_times;
    }

    $this->createKeys();

  }

  private function createConnections(){

    $this->_connections['memcache'] = array(  
      'connection' => new Memcache(),
      'addMethod' => 'add',
      'getMethod' => 'get',
      'setMethod' => 'set',
      'connectMethod' => 'addServer', 
      'port' => '11211',
      'getPrefixes'  => array( 'add', 'set' )
    );
    $this->_connections['memcached'] = array(  
      'connection' => new Memcached(),
      'addMethod' => 'add',
      'getMethod' => 'get',
      'setMethod' => 'set',
      'connectMethod' => 'addServer', 
      'port' => '11211',
      'getPrefixes'  => array( 'add', 'set' )
    );
    $this->_connections['memcached_withBinaryProtocol'] = array(  
      'connection' => new Memcached(),
      'addMethod' => 'add',
      'getMethod' => 'get',
      'setMethod' => 'set',
      'connectMethod' => 'addServer', 
      'port' => '11211',
      'getPrefixes'  => array( 'add', 'set' )
    );
    $this->_connections['redis'] = array(  
      'connection' => new Redis(),
      'addMethod' => 'set',
      'getMethod' => 'get',
      'setMethod' => 'set',
      'connectMethod' => 'connect', 
      'port' => '6379',
      'getPrefixes'  => array( 'add', 'set' )
    );
    foreach( $this->_connections as $connection) {
      $connection['connection']->$connection['connectMethod']( '127.0.0.1', $connection['port'] );

    }
    $this->_connections[ 'memcached_withBinaryProtocol' ][ 'connection' ]->setOption( Memcached::OPT_BINARY_PROTOCOL, true );

    $this->_connection_types = array_keys( $this->_connections );
  }

  /**
   * Populates $this->keys with random values for each connection
   */
  private function createKeys() {
    foreach ( $this->_connection_types as $connection ) {
      $this->keys[ $connection ] = array();
    }

    for ( $t = 0; $t < $this->times; $t++ ) {
      $key = $this->_randomString(); 
      $value = $this->_randomString( rand( 1, 2000 ) );
      foreach ( $this->_connection_types as $connection ) {
        $this->keys[ $connection ][] = array( 
          'key' => $key . $connection,
          'value' => $value
        );
      }
    }
  }

  /**
   * Test the add method
   */
  function testAdds() {
    foreach( $this->_connection_types as $ct ){
      for ( $t = 0; $t < $this->times; $t++ ) {
        $memcacheKey = $this->keys[ $ct ][ $t ][ 'key' ]; 
        $value = $this->keys[ $ct ][ $t ][ 'value' ]; 
        $method = $this->_connections[ $ct ][ 'addMethod'];
        $connection = $this->_connections[ $ct ][ 'connection' ];
        $start = microtime(true);
        
        $connection->$method( 'add-' . $memcacheKey , $value );

        $end = microtime( true );
        $execution = $end - $start;

        $this->results[ 'add' ][ $ct ][] = $execution;
      }
    }
  }

  /**
   * Test the set method
   */
  function testSets() {
    foreach( $this->_connection_types as $ct ){
      for ( $t = 0; $t < $this->times; $t++ ) {
        $memcacheKey = $this->keys[ $ct ][ $t ][ 'key' ]; 
        $value = $this->keys[ $ct ][ $t ][ 'value' ]; 
        $method = $this->_connections[ $ct ][ 'setMethod'];
        $connection = $this->_connections[ $ct ][ 'connection' ];
        $start = microtime(true);
        
        $connection->$method( 'set-' . $memcacheKey , $value );

        $end = microtime( true );
        $execution = $end - $start;

        $this->results[ 'set' ][ $ct ][] = $execution;
      }
    }
  }

  /**
   * Test the get method
  */
  function testGets() {
    foreach( $this->_connection_types as $ct ){
      for ( $t = 0; $t < $this->times; $t++ ) {
        $memcacheKey = $this->keys[ $ct ][ $t ][ 'key' ]; 
        $value = $this->keys[ $ct ][ $t ][ 'value' ]; 

        $method = $this->_connections[ $ct ][ 'getMethod'];
        $connection = $this->_connections[ $ct ][ 'connection' ];
        $start = microtime(true);
       
        foreach( $this->_connections[ $ct ][ 'getPrefixes']  as $prefix ) {
          $connection->$method( $prefix . '-' . $memcacheKey );
        }

        $end = microtime( true );
        $execution = $end - $start;

        $this->results[ 'get' ][ $ct ][] = $execution;
      }
    }
  }

  /**
   * Echo out the results
   */
  function reportResults() {

    // We want padded names for display
    $lengths = array_map( 'strlen', $this->_connection_types );
    $longest = max( $lengths ) + 2; 

    echo "\nResults are in thousandth of a second";
    foreach( $this->results as $k => $tests ) {
      echo "\n---";
      echo "\nTEST: $k";

     
      echo "\n\tAverage Execution:";
      foreach( $this->_connection_types as $ct ){
        $name = str_pad( $ct, $longest );
        echo "\n$name: " . $this->_formatNum( array_sum( $tests[$ct] ) / count( $tests[$ct] ) );
      }

      echo "\n\t95% execution time";
      foreach( $this->_connection_types as $ct ){
        $name = str_pad( $ct, $longest );
        $ninety_five = round( ( $this->times / 100 ) * 95 );

        sort( $tests[$ct] );

        echo "\n$name: " . $this->_formatNum( $tests[$ct][ $ninety_five ] );
      }

      echo "\n\t99% execution time";
      foreach( $this->_connection_types as $ct ){
        $name = str_pad( $ct, $longest );
        $ninety_nine = round( ( $this->times / 100 ) * 99 );

        sort( $tests[$ct] );

        echo "\n$name: " . $this->_formatNum( $tests[$ct][ $ninety_nine ] );
      }

      echo "\n";
    }
    

  }

  /**
   * Return a random string
   *
   * @param int $length Length of string 
  */
  function _randomString($length = 10) {
    $str = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
    while( strlen( $str) < $length ) {
      $str .= $str;
    }
    return substr( str_shuffle( $str ), 0, $length );
  }

  /**
   * Return a number in our desired format
   *
   * Since the results are generally in ten-thousands, multiply results by 1000 and
   * Only return the first four places after the decimal
   *
   * @parm float $number the number we want formated
  */
  function _formatNum( $number ) {
    return number_format( $number * 1000, 4 );
  }


}

$m = new MemTest();
$m->testAdds();
$m->testSets();
$m->testGets();
$m->reportResults();
