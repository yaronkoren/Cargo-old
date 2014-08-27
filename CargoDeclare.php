<?php
/**
 * Class for the #cargo_declare parser function, as well as for the creation
 * (and re-creation) of Cargo database tables.
 *
 * @author Yaron Koren
 * @ingroup Cargo
 */

class CargoDeclare {

	/**
	 * Handles the #cargo_declare parser function.
	 */
	public static function run( &$parser ) {
		if ( $parser->getTitle()->getNamespace() != NS_TEMPLATE ) {
			return CargoUtils::formatError( "Error: #cargo_declare must be called from a template page." );
		}

		$params = func_get_args();
		array_shift( $params ); // we already know the $parser...

		$tableName = null;
		$cargoFields = array();
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
				$typeDescription = $value;
				// Validate field name.
				if ( strpos( $fieldName, ' ' ) !== false ) {
					return CargoUtils::formatError( "Error: Field name \"$fieldName\" contains spaces. Spaces are not allowed; consider using underscores(\"_\") instead." );
				} elseif ( strpos( $fieldName, '_' ) === 0 ) {
					return CargoUtils::formatError( "Error: Field name \"$fieldName\" begins with an underscore; this is not allowed." );
				} elseif ( strpos( $fieldName, '__' ) !== false ) {
					return CargoUtils::formatError( "Error: Field name \"$fieldName\" contains more than one underscore in a row; this is not allowed." );
				} elseif ( strpos( $fieldName, ',' ) !== false ) {
					return CargoUtils::formatError( "Error: Field name \"$fieldName\" contains a comma; this is not allowed." );
				}

				$fieldData = array();

				if ( strpos( $typeDescription, 'List') === 0 ) {
					$matches = array();
					$foundMatch = preg_match( '/List \((.*)\) of (.*)/', $typeDescription, $matches);
					if (! $foundMatch) {
						return CargoUtils::formatError( "Error: could not parse type for field \"$fieldName\"." );
					}
					$fieldData['isList'] = true;
					$fieldData['delimiter'] = $matches[1];
					$typeDescription = $matches[2];
				}

				// There may be additional parameters, in
				// parentheses.
				$matches = array();
				$foundMatch2 = preg_match( '/(.*)\s*\((.*)\)/', $typeDescription, $matches);
				if ( $foundMatch2 ) {
					$typeDescription = $matches[1];
					$extraParamsString = $matches[2];
					$extraParams = explode( ';', $extraParamsString );
					foreach ( $extraParams as $extraParam ) {
						$extraParamParts = explode( '=', $extraParam );
						if ( count( $extraParamParts ) != 2 ) {
							continue;
						}
						$paramKey = trim( $extraParamParts[0] );
						$paramValue = trim( $extraParamParts[1] );
						if ( $paramKey == 'allowed values' ) {
							$fieldData['allowedValues'] = explode( ',', $paramValue );
						} elseif ( $paramKey == 'size' ) {
							$fieldData['size'] = $paramValue;
						}
					}
				}

				$fieldData['type'] = $typeDescription;
				$cargoFields[$fieldName] = $fieldData;
			}
		}

		// Validate table name.
		if ( $tableName == '' ) {
			return CargoUtils::formatError( "Error: Table name must be specified." );
		} elseif ( strpos( $tableName, ' ' ) !== false ) {
			return CargoUtils::formatError( "Error: Table name \"$tableName\" contains spaces. Spaces are not allowed; consider using underscores(\"_\") instead." );
		} elseif ( strpos( $tableName, '_' ) === 0 ) {
			return CargoUtils::formatError( "Error: Table name \"$tableName\" begins with an underscore; this is not allowed." );
		} elseif ( strpos( $tableName, '__' ) !== false ) {
			return CargoUtils::formatError( "Error: Table name \"$tableName\" contains more than one underscore in a row; this is not allowed." );
		} elseif ( strpos( $tableName, ',' ) !== false ) {
			return CargoUtils::formatError( "Error: Table name \"$tableName\" contains a comma; this is not allowed." );
		}

		$parserOutput = $parser->getOutput();

		$parserOutput->setProperty( 'CargoTableName', $tableName );
		$parserOutput->setProperty( 'CargoFields', serialize( $cargoFields ) );

		// Link to the Special:ViewTable page for this table.
		$vt = SpecialPage::getTitleFor( 'ViewTable' );
		$pageName = $vt->getPrefixedText() . "/$tableName";
		$viewTableMsg = wfMessage( 'ViewTable' )->parse();
		$text = "This template defines the table \"$tableName\". [[$pageName|$viewTableMsg]].";

		return $text;
	}

	/**
	 * Get the SQL date type that corresponds to the specified Cargo
	 * type, depending on which database system is being used.
	 */
	public static function fieldTypeToSQLType( $fieldType, $dbType, $size = null ) {
		// Possible values for $dbType: "mssql", "mysql", "oracle",
		// "postgres", "sqlite"
		// @TODO - make sure it's one of these.
		if ( $fieldType == 'Integer' ) {
			switch ( $dbType ) {
				case "mssql":
				case "mysql":
				case "postgres":
					return 'Int';
				case "sqlite":
					return 'INTEGER';
				case "oracle":
					return 'Number';
			}
		} elseif ( $fieldType == 'Float' ) {
			switch ( $dbType ) {
				case "mssql":
				case "mysql":
					return 'Float';
				case "postgres":
					return 'Numeric';
				case "sqlite":
					return 'REAL';
				case "oracle":
					return 'Number';
			}
		} elseif ( $fieldType == 'Boolean' ) {
			switch ( $dbType ) {
				case "mssql":
					return 'Bit';
				case "mysql":
				case "postgres":
					return 'Boolean';
				case "sqlite":
					return 'INTEGER';
				case "oracle":
					return 'Byte';
			}
		} elseif ( $fieldType == 'Date' ) {
			switch ( $dbType ) {
				case "mssql":
				case "mysql":
				case "postgres":
				case "oracle":
					return 'Date';
				case "sqlite":
					// Should really be 'REAL', with
					// accompanying handling.
					return 'TEXT';
			}
		} else { // 'Text', 'Page', etc.
			if ( $size == null ) {
				$size = 300;
			}
			switch ( $dbType ) {
				case "mssql":
				case "mysql":
				case "postgres":
				case "oracle":
					return "Varchar($size)";
				case "sqlite":
					return 'TEXT';
			}
		}
	}

	/**
	 * Drop, and then create again, the database table(s) holding the
	 * data for this template.
	 * Why "tables"? Because every field that holds a list of values gets
	 * its own helper table.
	 */
	public static function recreateDBTablesForTemplate( $templatePageID ) {
		global $wgDBtype;

		$tableFieldsString = CargoUtils::getPageProp( $templatePageID, 'CargoFields' );
		// First, see if there even is DB storage for this template -
		// if not, exit.
		if ( is_null( $tableFieldsString ) ) {
			return false;
		}

		$tableFields = unserialize( $tableFieldsString );
		if ( !is_array( $tableFields ) ) {
			throw new MWException( "Invalid field information found for template." );
		}

		$dbr = wfGetDB( DB_MASTER );
		$cdb = CargoUtils::getDB();

		$res = $dbr->select( 'cargo_tables', 'main_table', array( 'template_id' => $templatePageID ) );
		while ( $row = $dbr->fetchRow( $res ) ) {
			$curTable = $row['main_table'];
			$cdb->dropTable( $curTable );
			$dbr->delete( 'cargo_pages', array( 'table_name' => $curTable ) );
		}

		$dbr->delete( 'cargo_tables', array( 'template_id' => $templatePageID ) );

		$tableName = CargoUtils::getPageProp( $templatePageID, 'CargoTableName' );
		// Unfortunately, there is not yet a 'CREATE TABLE' wrapper
		// in the MediaWiki DB API, so we have to call SQL directly.
		$intTypeString = self::fieldTypeToSQLType( 'Integer', $wgDBtype );
		$textTypeString = self::fieldTypeToSQLType( 'Text', $wgDBtype );

		$createSQL = "CREATE TABLE " .
			$cdb->tableName( $tableName ) . ' ( ' .
			"_ID $intTypeString NOT NULL UNIQUE, " .
			"_pageName $textTypeString NOT NULL, " .
			"_pageID $intTypeString NOT NULL";

		foreach ( $tableFields as $fieldName => $fieldDescription ) {
			if ( array_key_exists( 'size', $fieldDescription ) ) {
				$size = $fieldDescription['size'];
			} else {
				$size = null;
			}

			if ( array_key_exists( 'isList', $fieldDescription ) ) {
				// No field will be created with this name -
				// instead, we'll have one called
				// fieldName + '__full', and a separate table
				// for holding each value.
				$createSQL .= ', ' . $fieldName . '__full ';
				// The field holding the full list will always
				// just be text
				$createSQL .= $textTypeString;
			} else {
				$createSQL .= ", $fieldName ";
				$createSQL .= self::fieldTypeToSQLType( $fieldDescription['type'], $wgDBtype, $size );
			}
		}
		$createSQL .= ' )';

		//$cdb->ignoreErrors( true );
		$cdb->query( $createSQL );
		//$cdb->ignoreErrors( false );

		$createIndexSQL = "CREATE INDEX page_id_$tableName ON " . $cdb->tableName( $tableName ) . " (_pageID)";
		$createIndexSQL = "CREATE INDEX page_name_$tableName ON " . $cdb->tableName( $tableName ) . " (_pageName)";
		$createIndexSQL = "CREATE UNIQUE INDEX id_$tableName ON " . $cdb->tableName( $tableName ) . " (_ID)";
		$cdb->query( $createIndexSQL );

		// Now also create tables for each of the 'list' fields,
		// if there are any.
		$fieldTableNames = array();
		foreach ( $tableFields as $fieldName => $fieldDescription ) {
			if ( !array_key_exists( 'isList', $fieldDescription ) ) {
				continue;
			}
			// The double underscore in this table name should
			// prevent anyone from giving this name to a "real"
			// table.
			$fieldTableName = $tableName . '__' . $fieldName;
			$cdb->dropTable( $fieldTableName );
			$createSQL = "CREATE TABLE " .
				$cdb->tableName( $fieldTableName ) . ' ( ' .
				"_rowID $intTypeString, " .
				'_value ' . self::fieldTypeToSQLType( $fieldDescription['type'], $wgDBtype, $size ) .
				' )';
			$cdb->query( $createSQL );
			$createIndexSQL = "CREATE INDEX row_id_$fieldTableName ON " . $cdb->tableName( $fieldTableName ) . " (_rowID)";
			$cdb->query( $createIndexSQL );
			$fieldTableNames[] = $tableName . '__' . $fieldName;
		}
		$cdb->close();

		// Finally, store all the info in the cargo_tables table.
		$dbr->insert( 'cargo_tables', array( 'template_id' => $templatePageID, 'main_table' => $tableName, 'field_tables' => serialize( $fieldTableNames ), 'table_schema' => $tableFieldsString ) );
		return true;
	}

}
