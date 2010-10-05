<?php

// $Header$

// Copyright( c ) 2010, Lester Caine.
// All Rights Reserved. See below for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See http://www.gnu.org/copyleft/lesser.html for details.

require_once( '../kernel/setup_inc.php' );

include_once( ADDRESS_PKG_PATH.'Address.php' );

$gBitSystem->isPackageActive( 'address', TRUE );

$gBitSystem->verifyPermission( 'p_address_view' );

// now, lets get the ball rolling!
$gAddress = new Address();

// Build the address book
$listAddresses = $gAddress->getList( $_REQUEST );

$gBitSmarty->assign( 'listInfo', $_REQUEST['listInfo'] );
$gBitSmarty->assign( 'listAddress', $listAddresses );

// Display the template
$gBitSystem->display( 'bitpackage:address/list_addresses.tpl', tra( 'Address Book' ), array( 'display_mode' => 'list' ));

?>
