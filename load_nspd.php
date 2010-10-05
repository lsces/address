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
$address->NSPDExpunge();

$row = 0;

$handle = fopen("data/NSPDO_AUG_2010_UK_1M_O.csv", "r");
if ( $handle == FALSE) {
	$row = -999;
} else {
	while (($data = fgetcsv($handle, 800, ",")) !== FALSE) {
    	if ( $row ) $address->NSPDRecordLoad( $data );
    	$row++;
	}
	fclose($handle);
}

$gBitSmarty->assign( 'count', $row );
$gBitSmarty->assign( 'title', 'NSPD' );

$gBitSystem->display( 'bitpackage:address/load_cvs.tpl', tra( 'Load results: ' ) );
?>
