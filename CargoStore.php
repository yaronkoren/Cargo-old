<?php
/**
 * Class for the #cargo_store function.
 *
 * @author Yaron Koren
 * @ingroup Cargo
 */

class CargoStore {

	static $settings = array();

	/**
	 * Gets the template page where this table is defined -
	 * hopefully there's exactly one of them.
	 */
	public static function getTemplateIDForDBTable( $tableName ) {
		$dbr = wfGetDB( DB_SLAVE );
		$res = $dbr->select( 'page_props',
			array(
				'pp_page'
			),
			array(
				'pp_value' => $tableName,
				'pp_propname' => 'CargoTableName'
			)
		);
		if ( ! $row = $dbr->fetchRow( $res ) ) {
			return null;
		}
		return $row['pp_page'];
	}

	/**
	 * Handles the #cargo_set parser function - saves data for one
	 * template call.
	 */
	public static function run( &$parser ) {
		// This function does actual DB modifications - so only proceed
		// is this is called via either a page save or as a result of
		// a template that this page calls, and that includes a
		// #cargo_declare call, getting resaved.
		if ( count( self::$settings ) == 0 ) {
wfDebugLog('cargo', "CargoStore::run() - skipping.\n");
			return;
		} elseif ( !array_key_exists( 'origin', self::$settings ) ) {
wfDebugLog('cargo', "CargoStore::run() - skipping 2.\n");
			return;
		}

		$params = func_get_args();
		array_shift( $params ); // we already know the $parser...

		$tableName = null;
		$tableFieldValues = array();

		foreach ( $params as $param ) {
			$parts = explode( '=', $param, 2 );
			
			if ( count( $parts ) != 2 ) {
				continue;
			}
			$key = trim( $parts[0] );
			$value = trim( $parts[1] );
			if ( $key == '_table' ) {
				$tableName = $value;
			} else {
				$fieldName = $key;
				$fieldValue = $value;
				$tableFieldValues[$fieldName] = $fieldValue;
			}
		}

		if ( $tableName == '' ) {
			return;
		}

		$templatePageID = self::getTemplateIDForDBTable( $tableName );

		if ( self::$settings['origin'] == 'template' ) {
			// It came from a template save - make sure it passes
			// various criteria.
			if ( self::$settings['dbTableName'] != $tableName ) {
wfDebugLog('cargo', "CargoStore::run() - skipping 3.\n");
				return;
			}
			if ( self::$settings['templateID'] != $templatePageID ) {
wfDebugLog('cargo', "CargoStore::run() - skipping 4.\n");
				return;
			}
		}

		// Get the declaration of the table.
		$dbr = wfGetDB( DB_SLAVE );
		$res = $dbr->select( 'cargo_tables', 'table_schema', array( 'template_id' => $templatePageID ) );
		$row = $dbr->fetchRow( $res );
		$tableFieldsString = $row['table_schema'];
		$tableFields = unserialize( $tableFieldsString );

		foreach ( $tableFieldValues as $fieldName => $fieldValue ) {
			if ( !array_key_exists( $fieldName, $tableFields ) ) {
				throw new MWException( "Error: Unknown field, \"$fieldName\"." );
			}
		}

		// We're still here! Let's add to the DB table(s).

		// First, though, let's do some processing:
		// - remove invalid values, if any
		// - put dates into correct format
		foreach ( $tableFields as $fieldName => $typeDescription ) {
			if ( array_key_exists( 'allowedValues', $typeDescription ) ) {
				$allowedValues = $typeDescription['allowedValues'];
				if ( !in_array( $tableFieldValues[$fieldName], $allowedValues ) ) {
					$tableFieldValues[$fieldName] = null;
				}
			}
			if ( $typeDescription['type'] == 'Date' ) {
				// Put into YYYY-MM-DD format.
				$curValue = $tableFieldValues[$fieldName];
				if ( $curValue != '' ) {
					$seconds = strtotime( $curValue );
					$tableFieldValues[$fieldName] = date('Y-m-d', $seconds );
				}
			}
		}

		// Add the "metadata" field values.
		$pageName = $parser->getTitle()->getPrefixedText();
		$pageID = $parser->getTitle()->getArticleID();
		$tableFieldValues['_pageName'] = $pageName;
		$tableFieldValues['_pageID'] = $pageID;

		$cdb = CargoUtils::getDB();

		$res = $cdb->select( $tableName, 'MAX(_ID) AS ID' );
		$row = $cdb->fetchRow( $res );
		$curRowID = $row['ID'] + 1;
		$tableFieldValues['_ID'] = $curRowID;

		// For each field that holds a list of values, also add its
		// values to its own table; and rename the actual field.
		foreach ( $tableFields as $fieldName => $typeDescription ) {
			if ( array_key_exists( 'isList', $typeDescription ) ) {
				$fieldTableName = $tableName . '__' . $fieldName;
				$delimiter = $typeDescription['delimiter'];
				$individualValues = explode( $delimiter, $tableFieldValues[$fieldName] );
				foreach ( $individualValues as $individualValue ) {
					// Ignore blank values.
					if ( trim( $individualValue ) == '' ) {
						continue;
					}
					$res3 = $cdb->insert( $fieldTableName,
						array( '_rowID' => $curRowID, '_value' => $individualValue ) );
				}

				// Now rename the field.
				$tableFieldValues[$fieldName . '__full'] = $tableFieldValues[$fieldName];
				unset( $tableFieldValues[$fieldName] );

			}
		}

		// Insert the current data into the main table.
		$cdb->insert( $tableName, $tableFieldValues );

		$cdb->close();

		// Finally, add a record of this to the cargo_pages table, if
		// necessary.
		$dbr = wfGetDB( DB_MASTER );
		$res = $dbr->select( 'cargo_pages', 'page_id', array( 'table_name' => $tableName, 'page_id' => $pageID ) );
		if ( ! $row = $dbr->fetchRow( $res ) ) {
			$dbr->insert( 'cargo_pages', array( 'table_name' => $tableName, 'page_id' => $pageID ) );
		}
	}

}
