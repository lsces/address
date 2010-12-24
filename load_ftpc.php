<?php
/*
 * Created on 5 Jan 2008
 *
 * To change the template for this generated file go to
 * Window - Preferences - PHPeclipse - PHP - Code Templates
 */

// Initialization
require_once( '../kernel/setup_inc.php' );
require_once(ADDRESS_PKG_PATH.'Address.php' );

// Is package installed and enabled
$gBitSystem->verifyPackage( 'address' );

// Now check permissions to access this page
$gBitSystem->verifyPermission('p_addresss_admin' );

$address = new Address();

set_time_limit(0);
$address->FTPCExpunge();

$row = 0;

$handle = fopen("data/postcode.csv", "r");
if ( $handle == FALSE) {
	$row = -999;
} else {
	while (($data = fgetcsv($handle, 800, "\t")) !== FALSE) {
    	if ( $row ) $address->FTPCRecordLoad( $data );
    	$row++;
	}
	fclose($handle);
}

$gBitSmarty->assign( 'count', $row );
$gBitSmarty->assign( 'title', 'Free The Postcode' );

$gBitSystem->display( 'bitpackage:address/load_cvs.tpl', tra( 'Load results: ' ) );
?>
