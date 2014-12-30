<?php

$results = array();
$times = 10000;
$m = new Memcache();
$m->addServer( '127.0.0.1', 11211 );
$d = new Memcached();
$d->addServer( '127.0.0.1', 11211 );
$b = new Memcached();
$b->addServer( '127.0.0.1', 11211 );
$b->setOption( Memcached::OPT_BINARY_PROTOCOL, true );

function randomString($length = 10) {
	return substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, $length);
}

function formatNum( $number ) {
	return number_format( $number * 1000, 4 );
}


for ( $t = 0; $t < $times; $t++ ) {

	$key = randomString(); 
	$memcacheKey = $key . 'm';
	$memcachedKey = $key . 'd';
	$memcachedbKey = $key . 'b';
	$value = randomString( rand( 1, 200 ) );


	// memcache add
	$start = microtime(true);

	$m->add( 'add-' . $memcacheKey , $value );

	$end = microtime( true );
	$execution = $end - $start;

	$results[ 'add' ][ 'memcache' ][] = $execution;

	// memcached add
	$start = microtime(true);

	$d->add( 'add-' . $memcachedKey , $value );

	$end = microtime( true );
	$execution = $end - $start;

	$results[ 'add' ][ 'memcached' ][] = $execution;

	// memcached binary add
	$start = microtime(true);

	$b->add( 'add-' . $memcachedKey , $value );

	$end = microtime( true );
	$execution = $end - $start;

	$results[ 'add' ][ 'memcachedBinary' ][] = $execution;

	// memcache set
	$start = microtime(true);

	$m->set( 'set-' . $memcacheKey , $value );

	$end = microtime( true );
	$execution = $end - $start;

	$results[ 'set' ][ 'memcache' ][] = $execution;

	// memcached set
	$start = microtime(true);

	$d->set( 'set-' . $memcachedKey , $value );

	$end = microtime( true );
	$execution = $end - $start;

	$results[ 'set' ][ 'memcached' ][] = $execution;

	// memcached set
	$start = microtime(true);

	$b->set( 'set-' . $memcachedKey , $value );

	$end = microtime( true );
	$execution = $end - $start;

	$results[ 'set' ][ 'memcachedBinary' ][] = $execution;

	// memcache get
	$start = microtime(true);

	$m->get( 'add-' . $memcacheKey ); 
	$m->get( 'set-' . $memcacheKey ); 

	$end = microtime( true );
	$execution = $end - $start;

	$results[ 'get' ][ 'memcache' ][] = $execution;

	// memcached get
	$start = microtime(true);

	$d->get( 'add-' . $memcacheKey ); 
	$d->get( 'set-' . $memcacheKey ); 

	$end = microtime( true );
	$execution = $end - $start;

	$results[ 'get' ][ 'memcached' ][] = $execution;

	// memcached get
	$start = microtime(true);

	$b->get( 'add-' . $memcacheKey ); 
	$b->get( 'set-' . $memcacheKey ); 

	$end = microtime( true );
	$execution = $end - $start;

	$results[ 'get' ][ 'memcachedBinary' ][] = $execution;

}

echo "\nResults are in thousandth of a second";
foreach( $results as $k => $tests ) {
	echo "\n---";
	echo "\nTEST: $k";
	foreach( $tests as $type => $v) { 
		echo "\n$type: Average Execution: " . formatNum( array_sum( $v ) / count( $v ) );
		sort( $v );
		$ninety_nine = round( ( $times / 100 ) * 99 );
		$ninety_five = round( ( $times / 100 ) * 95 );
		echo "\n$type: 99%: " . formatNum( $v[ $ninety_nine ] );
		echo "\n$type: 99%: " . formatNum( $v[ $ninety_nine ] );
	}
	echo "\n";
}
