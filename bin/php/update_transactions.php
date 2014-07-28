<?php

/**
 * @package MultiSafepay
 * @author  Serhey Dolgushev <dolgushev.serhey@gmail.com>
 * @date    11 Jul 2014
 * */
ini_set( 'memory_limit', '1024M' );

require 'autoload.php';
$cli = eZCLI::instance();

$scriptSettings                   = array();
$scriptSettings['description']    = 'Update status for MSP transactions';
$scriptSettings['use-session']    = true;
$scriptSettings['use-modules']    = true;
$scriptSettings['use-extensions'] = true;

$script  = eZScript::instance( $scriptSettings );
$script->startup();
$script->initialize();
$options = $script->getOptions(
    '', '', array()
);


$timestamp     = microtime( true );
$minCreateDate = time() - eZINI::instance( 'multisafepay.ini' )->variable( 'General', 'TransactionsSyncExpirtTime' );

$cli->output( 'Fetching non-completed MSP transactions' );
$fetchParams  = array(
    'status'  => array( array( 'initialized', 'uncleared' ) ),
    'created' => array( '>', $minCreateDate )
);
$transactions = MultiSafepayTransaction::fetchList( $fetchParams );
$count        = count( $transactions );
foreach( $transactions as $k => $transaction ) {
    $memoryUsage = number_format( memory_get_usage( true ) / ( 1024 * 1024 ), 2 );
    $message     = '[' . date( 'c' ) . '] ' . number_format( $k / $count * 100, 2 )
        . '% (' . $k . '/' . $count . '), Memory usage: ' . $memoryUsage . ' Mb';
    $cli->output( $message );

    try {
        $transaction->updateStatus();
    } catch( Exception $e ) {
        $cli->output( 'Transaction #' . $transaction->attribute( 't_id' ) . ', ERROR' . $e->getMessage() );
        continue;
    }

    $cli->output( 'Transaction #' . $transaction->attribute( 't_id' ) . ': ' . $transaction->attribute( 'status' ) );

    if( $transaction->attribute( 'status' ) == 'completed' ) {
        $order = $transaction->attribute( 'order' );
        if( $order instanceof eZOrder === false ) {
            continue;
        }

        if( (bool) $order->attribute( 'is_temporary' ) === false ) {
            continue;
        }

        $transaction->updatePaymentObject();
    }
}

$cli->output( 'Fetching completed MSP transactions' );
$fetchParams  = array(
    'status'  => array( array( 'completed' ) ),
    'updated' => array( '>', $minCreateDate )
);
$transactions = MultiSafepayTransaction::fetchList( $fetchParams );
$count        = count( $transactions );
foreach( $transactions as $k => $transaction ) {
    $memoryUsage = number_format( memory_get_usage( true ) / ( 1024 * 1024 ), 2 );
    $message     = '[' . date( 'c' ) . '] ' . number_format( $k / $count * 100, 2 )
        . '% (' . $k . '/' . $count . '), Memory usage: ' . $memoryUsage . ' Mb';
    $cli->output( $message );

    $order = $transaction->attribute( 'order' );
    if( $order instanceof eZOrder === false ) {
        continue;
    }

    if( (bool) $order->attribute( 'is_temporary' ) === false ) {
        continue;
    }

    $cli->output( 'Processing order#' . $order->attribute( 'id' ) );
    $transaction->updatePaymentObject();
}

$cli->output( 'Finished in ' . number_format( microtime( true ) - $timestamp, 2 ) . ' sec.' );
$script->shutdown( 0 );
