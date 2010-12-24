<?php
/**
 * @version $Header$
 *
 * Copyright ( c ) 2010 Lester Caine
 * All Rights Reserved. See below for details and a complete list of authors.
 * Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See http://www.gnu.org/copyleft/lesser.html for details
 *
 * @package address
 */

/**
 * required setup
 */
require_once( LIBERTY_PKG_PATH.'LibertyBase.php' );		// Contact base class
require_once( NLPG_PKG_PATH.'lib/phpcoord-2.3.php' );

/**
 * @package address
 */
class Address extends LibertyBase {
	var $mAddressId;
	var $mCustomerId;
	var $mHouseName;

	/**
	 * Constructor 
	 * 
	 * Build a Contact object based on LibertyContent
	 * @param integer Contact Id identifer
	 */
	function Address( $pAddressId = NULL, $pCustomerId = NULL ) {
		LibertyBase::LibertyBase();
		$this->mAddressId = (int)$pAddressId;
		$this->mCustomerId = (int)$pCustomerId;
		$this->mHouseName = '';
	}

	/**
	 * Load a Contact content Item
	 *
	 * (Describe Contact object here )
	 */
	function load($pAddressId = NULL) {
		if ( $pAddressId ) $this->mAddressId = (int)$pAddressId;
		if( $this->verifyId( $this->mAddressId ) ) {
 			$query = "select ab.*, apc.*, az,`zone_name`, ac,`country_name`
				FROM `".BIT_DB_PREFIX."address_book` ab
				LEFT JOIN `".BIT_DB_PREFIX."address_postcode` apc ON apc.`postcode` = ab.`postcode`
				LEFT JOIN `".BIT_DB_PREFIX."address_zones` az ON az.`zone_id` = apc.`zone_id`
				LEFT JOIN `".BIT_DB_PREFIX."address_country` ac ON ac.`country_id` = apc.`country_id`
				WHERE ab.`address_book_id`=?";
/*
*/
			$result = $this->mDb->query( $query, array( $this->mAddressId ) );

			if ( $result && $result->numRows() ) {
				$this->mInfo = $result->fields;
				$this->mAddressId = (int)$result->fields['address_book_id'];
				$this->mCustomerId = (int)$result->fields['customers_id'];
				$this->mHouseName = $result->fields['primary_name'].','.$result->fields['secondary_name'];
				$os1 = new OSRef($this->mInfo['grideast'], $this->mInfo['gridnorth']);
				$ll1 = $os1->toLatLng();
				$this->mInfo['prop_lat'] = $ll1->lat;
				$this->mInfo['prop_lng'] = $ll1->lng;
			}
		}
		return;
	}

	/**
	* verify, clean up and prepare data to be stored
	* @param $pParamHash all information that is being stored. will update $pParamHash by reference with fixed array of itmes
	* @return bool TRUE on success, FALSE if store could not occur. If FALSE, $this->mErrors will have reason why
	* @access private
	**/
	function verify( &$pParamHash ) {
		// make sure we're all loaded up if everything is valid
		if( $this->isValid() && empty( $this->mInfo ) ) {
			$this->load( TRUE );
		}

		if( !empty( $this->mAddressId ) ) {
			$pParamHash['address_book_id'] = $this->mAddressId;
		} else {
			unset( $pParamHash['address_book_id'] );
		}

		if ( empty( $pParamHash['customers_id'] ) )
			$pParamHash['customers_id'] = $this->mCustomersId;
			
		return( count( $this->mErrors ) == 0 );
	}

	/**
	* Store contact data
	* @param $pParamHash contains all data to store the contact
	* @param $pParamHash[title] title of the new contact
	* @param $pParamHash[edit] description of the contact
	* @return bool TRUE on success, FALSE if store could not occur. If FALSE, $this->mErrors will have reason why
	**/
	function store( &$pParamHash ) {
		if( $this->verify( $pParamHash ) ) {
			// Start a transaction wrapping the whole insert into liberty 

			$this->mDb->StartTrans();
			$table = BIT_DB_PREFIX."address_book";

			if( $this->verifyId( $this->mAddressId ) ) {
				if( !empty( $pParamHash['address_store'] ) ) {
					$result = $this->mDb->associateUpdate( $table, $pParamHash['address_store'], array( "address_book_id" => $this->mAddressId ) );
				} else {
					if( isset( $pParamHash['address_book_id'] ) && is_numeric( $pParamHash['address_book_id'] ) ) {
						$pParamHash['address_store']['address_book_id'] = $pParamHash['address_book_id'];
					} else {
						$pParamHash['address_store']['address_book_id'] = $this->mDb->GenID( 'address_id_seq');
					}	
					$pParamHash['address_store']['customer_id'] = $pParamHash['address_store']['customer_id'];
					$this->mAddressId = $pParamHash['address_store']['address_book_id'];
					$this->mContactId = $pParamHash['address_store']['contact_id'];
					$result = $this->mDb->associateInsert( $table, $pParamHash['address_store'] );
				}
			}
			if ( $result ) {
				// load before completing transaction as firebird isolates results
				$this->load();
				$this->mDb->CompleteTrans();
			} else {
				$this->mDb->RollbackTrans();
				$this->mErrors['store'] = 'Failed to store this address.';
			}
		}
		return( count( $this->mErrors ) == 0 );
	}

	/**
	 * Delete content object and all related records
	 */
	function expunge()
	{
		$ret = FALSE;
		if ($this->isValid() ) {
			$this->mDb->StartTrans();
			$query = "DELETE FROM `".BIT_DB_PREFIX."address_book` WHERE `address_book_id` = ?";
			$result = $this->mDb->query($query, array($this->mAddressId ) );
			if (LibertyContent::expunge() ) {
			$ret = TRUE;
				$this->mDb->CompleteTrans();
			} else {
				$this->mDb->RollbackTrans();
			}
		}
		return $ret;
	}
    
	/**
	 * Returns Request_URI to a Contact content object
	 *
	 * @param string name of
	 * @param array different possibilities depending on derived class
	 * @return string the link to display the page.
	 */
	function getDisplayUrl( $pContactId=NULL ) {
		global $gBitSystem;
		if( empty( $pContactId ) ) {
			$pContactId = $this->mAddressId;
		}

		return ADDRESS_PKG_URL.'index.php?address_id='.$pAddressId;
	}

	/**
	 * Returns HTML link to display a Contact object
	 * 
	 * @param string Not used ( generated locally )
	 * @param array mInfo style array of content information
	 * @return the link to display the page.
	 */
	function getDisplayLink( $pText, $aux ) {
		if ( $this->mAddressId != $aux['address_book_id'] ) $this->load($aux['address_book_id']);

		if (empty($this->mInfo['address_book_id']) ) {
			$ret = '<a href="'.$this->getDisplayUrl($aux['contact_id']).'">'.$aux['title'].'</a>';
		} else {
			$ret = '<a href="'.$this->getDisplayUrl($aux['contact_id']).'">'."Address - ".$this->mInfo['title'].'</a>';
		}
		return $ret;
	}

	/**
	 * Returns title of an Contact object
	 *
	 * @param array mInfo style array of content information
	 * @return string Text for the title description
	 */
	function getTitle( $pHash = NULL ) {
		$ret = NULL;
		if( empty( $pHash ) ) {
			$pHash = &$this->mInfo;
		} else {
			if ( $this->mAddressId != $pHash['address_book_id'] ) {
				$this->load($pHash['address_book_id']);
				$pHash = &$this->mInfo;
			}
		}

		if( !empty( $pHash['title'] ) ) {
			$ret = "Address - ".$this->mInfo['title'];
		} elseif( !empty( $pHash['content_name'] ) ) {
			$ret = $pHash['content_name'];
		}
		return $ret;
	}

	/**
	 * Returns list of address entries
	 *
	 * @param integer 
	 * @param integer 
	 * @param integer 
	 * @return string Text for the title description
	 */
	function getList( &$pListHash ) {
		LibertyContent::prepGetList( $pListHash );
		
		$whereSql = $joinSql = $selectSql = '';
		$bindVars = array();
		
		if ( isset($pListHash['find']) ) {
			$findesc = '%' . strtoupper( $pListHash['find'] ) . '%';
			$whereSql .= " AND (UPPER(con.`SURNAME`) like ? or UPPER(con.`FORENAME`) like ?) ";
			array_push( $bindVars, $findesc );
		}

		if ( isset($pListHash['add_sql']) ) {
			$whereSql .= " AND $add_sql ";
		}

		$query = "SELECT ab.*, apc.*, az.`zone_name`, ac.`country_name`
				FROM `".BIT_DB_PREFIX."address_book` ab
				LEFT JOIN `".BIT_DB_PREFIX."address_postcode` apc ON apc.`postcode` = ab.`postcode`
				LEFT JOIN `".BIT_DB_PREFIX."address_zones` az ON az.`zone_id` = apc.`zone_id`
				LEFT JOIN `".BIT_DB_PREFIX."address_country` ac ON ac.`country_id` = apc.`country_id` 
				$joinSql ";
//				WHERE $whereSql  
//				order by ".$this->mDb->convertSortmode( $pListHash['sort_mode'] );
		$query_cant = "SELECT COUNT(ab.`address_book_id`) FROM `".BIT_DB_PREFIX."address_book` ab
				$joinSql ";
//				WHERE $whereSql";

		$ret = array();
		$this->mDb->StartTrans();
		$result = $this->mDb->query( $query, $bindVars, $pListHash['max_records'], $pListHash['offset'] );
		$cant = $this->mDb->getOne( $query_cant, $bindVars );
		$this->mDb->CompleteTrans();

		while ($res = $result->fetchRow()) {
			$res['contact_url'] = $this->getDisplayUrl( $res['contact_id'] );
			$ret[] = $res;
		}

		$pListHash['cant'] = $cant;
		LibertyContent::postGetList( $pListHash );
		return $ret;
	}

	/**
	* Returns titles of the address format table
	*
	* @return array List of address formats
	*/
	function getAddressFormatList() {
		$query = "SELECT `address_summary` FROM `'.BIT_DB_PREFIX.'address_format`
				  ORDER BY `address_summary`";
		$result = $this->mDb->query($query);
		$ret = array();

		while ($res = $result->fetchRow()) {
			$ret[] = trim($res["address_summary"]);
		}
		return $ret;
	}

	/**
	 * Delete nspd data and all related records
	 */
	function NSPDExpunge()
	{
		$ret = FALSE;
		$query = "DELETE FROM `".BIT_DB_PREFIX."address_nspd`";
		$result = $this->mDb->query( $query );
		return $ret;
	}

	/**
	 * PostcodeRecordLoad( $data ); 
	 * NSPD postcode file import 
	 */
	function NSPDRecordLoad( &$data ) {
		$table = BIT_DB_PREFIX."address_nspd";

		$pDataHash['record_store']['pcd'] = $data[0];
		$pDataHash['record_store']['pcd2'] = $data[1];
		$pDataHash['record_store']['pcds'] = $data[2];
		$pDataHash['record_store']['dointr'] = $data[3];
		$pDataHash['record_store']['doterm'] = $data[4];
		$pDataHash['record_store']['oscty'] = $data[5];
		$pDataHash['record_store']['oslaua'] = $data[6];
		$pDataHash['record_store']['osward'] = $data[7];
		$pDataHash['record_store']['usertype'] = $data[8];
		$pDataHash['record_store']['oseast1m'] = $data[9];
		$pDataHash['record_store']['osnrth1m'] = $data[10];
		$pDataHash['record_store']['osgrdind'] = $data[11];
		$pDataHash['record_store']['oshlthau'] = $data[12];
		$pDataHash['record_store']['hro'] = $data[13];
		$pDataHash['record_store']['ctry'] = $data[14];
		$pDataHash['record_store']['gor'] = $data[15];
		$pDataHash['record_store']['streg'] = $data[16];
		$pDataHash['record_store']['pcon'] = $data[17];
		$pDataHash['record_store']['eer'] = $data[18];
		$pDataHash['record_store']['teclec'] = $data[19];
		$pDataHash['record_store']['ttwa'] = $data[20];
		$pDataHash['record_store']['pct'] = $data[21];
		$pDataHash['record_store']['nuts'] = $data[22];
		$pDataHash['record_store']['psed'] = $data[23];
		$pDataHash['record_store']['cened'] = $data[24];
		$pDataHash['record_store']['edind'] = $data[25];
		$pDataHash['record_store']['oshaprev'] = $data[26];
		$pDataHash['record_store']['lea'] = $data[27];
		$pDataHash['record_store']['oldha'] = $data[28];
		$pDataHash['record_store']['wardc91'] = $data[29];
		$pDataHash['record_store']['wardo91'] = $data[30];
		$pDataHash['record_store']['ward98'] = $data[31];
		$pDataHash['record_store']['statsward'] = $data[32];
		$pDataHash['record_store']['oacode'] = $data[33];
		$pDataHash['record_store']['oaind'] = $data[34];
		$pDataHash['record_store']['casward'] = $data[35];
		$pDataHash['record_store']['park'] = $data[36];
		$pDataHash['record_store']['soa1'] = $data[37];
		$pDataHash['record_store']['dzone1'] = $data[38];
		$pDataHash['record_store']['soa2'] = $data[39];
		$pDataHash['record_store']['urindew'] = $data[40];
		$pDataHash['record_store']['urindsc'] = $data[41];
		$pDataHash['record_store']['urindni'] = $data[42];
		$pDataHash['record_store']['dzone2'] = $data[43];
		$pDataHash['record_store']['soa1ni'] = $data[44];
		$pDataHash['record_store']['oac'] = $data[45];
		$pDataHash['record_store']['oldpct'] = $data[46];
		
		$this->mDb->StartTrans();
		$result = $this->mDb->associateInsert( $table, $pDataHash['record_store'] );
		$this->mDb->CompleteTrans();

		return( count( $this->mErrors ) == 0 ); 
	}
	

	/**
	 * Delete nspd data and all related records
	 */
	function FTPCExpunge()
	{
		$ret = FALSE;
		$query = "DELETE FROM `".BIT_DB_PREFIX."address_postcode`";
		$result = $this->mDb->query( $query );
		return $ret;
	}

	/**
	 * PostcodeRecordLoad( $data ); 
	 * NSPD postcode file import 
	 */
	function FTPCRecordLoad( &$data ) {
		$table = BIT_DB_PREFIX."address_postcode";

		$pDataHash['record_store']['postcode'] = $data[0];
		$pDataHash['record_store']['add1'] = $data[1];
		$pDataHash['record_store']['add2'] = $data[2];
		$pDataHash['record_store']['add3'] = $data[3];
		$pDataHash['record_store']['add4'] = $data[4];
		$pDataHash['record_store']['town'] = $data[5];
		$pDataHash['record_store']['county'] = $data[6];
		
		$this->mDb->StartTrans();
		$result = $this->mDb->associateInsert( $table, $pDataHash['record_store'] );
		$this->mDb->CompleteTrans();

		return( count( $this->mErrors ) == 0 ); 
	}
	
}
?>
