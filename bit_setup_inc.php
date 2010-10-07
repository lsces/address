<?php
global $gBitSystem, $gBitSmarty, $gBitUser;

$registerHash = array(
	'package_name' => 'address',
	'package_path' => dirname( __FILE__ ).'/',
	'homeable' => TRUE,
);
$gBitSystem->registerPackage( $registerHash );

if( $gBitSystem->isPackageActive( 'address' ) && $gBitUser->hasPermission( 'p_address_view' )) {

		$menuHash = array(
			'package_name'  => ADDRESS_PKG_NAME,
			'index_url'     => ADDRESS_PKG_URL.'index.php',
			'menu_template' => 'bitpackage:address/menu_address.tpl',
		);
		$gBitSystem->registerAppMenu( $menuHash );
	}

?>
