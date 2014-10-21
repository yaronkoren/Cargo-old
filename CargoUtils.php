<?php
/**
 * CargoUtils - utility functions for the Cargo extension.
 *
 * @author Yaron Koren
 * @ingroup Cargo
 */

class CargoUtils {

	public static function getDB() {
		global $wgDBserver, $wgDBname, $wgDBuser, $wgDBpassword, $wgDBtype;
		global $wgCargoDBserver, $wgCargoDBname, $wgCargoDBuser, $wgCargoDBpassword, $wgCargoDBtype;

		$dbType = is_null( $wgCargoDBtype ) ? $wgDBtype : $wgCargoDBtype;
		$dbServer = is_null( $wgCargoDBserver ) ? $wgDBserver : $wgCargoDBserver;
		$dbUsername = is_null( $wgCargoDBuser ) ? $wgDBuser : $wgCargoDBuser;
		$dbPassword = is_null( $wgCargoDBpassword ) ? $wgDBpassword : $wgCargoDBpassword;
		$dbName = is_null( $wgCargoDBname ) ? $wgDBname : $wgCargoDBname;
		$dbFlags = DBO_DEFAULT;
		$dbTablePrefix = 'cargo__';

		$db = DatabaseBase::factory( $dbType,
			array(
				'host' => $dbServer,
				'user' => $dbUsername,
				'password' => $dbPassword,
				'dbname' => $dbName,
				'flags' => $dbFlags,
				'tablePrefix' => $dbTablePrefix,
			)
		);
		return $db;
	}

	/**
	 * Gets a page property for the specified page ID and property name.
	 */
	public static function getPageProp( $pageID, $pageProp ) {
		$dbr = wfGetDB( DB_SLAVE );
		$res = $dbr->select( 'page_props',
			array(
				'pp_value'
			),
			array(
				'pp_page' => $pageID,
				'pp_propname' => $pageProp,
			)
		);

		if ( ! $row = $dbr->fetchRow( $res ) ) {
			return null;
		}

		return $row['pp_value'];
	}

	public static function formatError( $errorString ) {
		return '<div class="error">' . $errorString . '</div>';
	}

	public static function getTables() {
		$tableNames = array();
		$dbr = wfGetDB( DB_SLAVE );
		$res = $dbr->select( 'cargo_tables', 'main_table' );
		while ( $row = $dbr->fetchRow( $res ) ) {
			$tableNames[] = $row[0];
		}
		return $tableNames;
	}

	/**
	 * Get the Cargo table for the passed-in template specified via
	 * either #cargo_declare or #cargo_attach, if the template has a
	 * call to either one.
	 */
	static function getTableNameForTemplate( $templateTitle ) {
		$templatePageID = $templateTitle->getArticleID();
		$declaredTableName = self::getPageProp( $templatePageID, 'CargoTableName' );
		if ( !is_null( $declaredTableName ) ) {
			return array( $declaredTableName, true );
		}
		$attachedTableName = self::getPageProp( $templatePageID, 'CargoAttachedTable' );
		return array( $attachedTableName, false );
	}

	static function tableExists( $tableName ) {
		$cdb = self::getDB();
		try {
			$cdb->select( $tableName, '*', null, null, array( 'LIMIT' => 1 ) );
		} catch ( Exception $e ) {
			return false;
		}
		return true;
	}

	/**
	 * Splits a string by the delimiter, but ignores delimiters contained
	 * within parentheses.
	 */
	static function smartSplit( $delimiter, $string) {
		if ( $string == '' ) {
			return array();
		}

		$returnValues = array();
		$numOpenParentheses = 0;
		$curReturnValue = '';

		for ( $i = 0; $i < strlen( $string ); $i++ ) {
			$curChar = $string{$i};
			if ( $curChar == '(' ) {
				$numOpenParentheses++;
			} elseif ( $curChar == ')' ) {
				$numOpenParentheses--;
			}

			if ( $curChar == $delimiter && $numOpenParentheses == 0 ) {
				$returnValues[] = $curReturnValue;
				$curReturnValue = '';
			} else {
				$curReturnValue .= $curChar;
			}
		}
		$returnValues[] = $curReturnValue;
		
		return $returnValues;
	}

}
