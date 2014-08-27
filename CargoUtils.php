<?php
/**
 * CargoUtils - utility functions for the Cargo extension.
 *
 * @author Yaron Koren
 * @ingroup Cargo
 */

class CargoUtils {

	public static function getDB() {
		global $wgDBserver, $wgDBname, $wgDBuser, $wgDBpassword, $wgDBprefix, $wgDBtype;
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
				// Both 'dbname' and 'dbName' have been
				// used in different versions.
				'dbname' => $dbName,
				'dbName' => $dbName,
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

/*
	static function pageHasCargoDeclaration( $title ) {
		if ( $title->getNamespace() != NS_TEMPLATE ) {
			return false;
		}

		$templatePageID = $title->getArticleID();
		$tableName = self::getPageProp( $templatePageID, 'CargoTableName' );
		if ( $tableName == '' ) {
			return false;
		}

		return true;
	}
*/

	static function getDeclaredTableName( $title ) {
		$templatePageID = $title->getArticleID();
		return self::getPageProp( $templatePageID, 'CargoTableName' );
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
