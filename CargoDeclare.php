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
					$typeDescription = trim( $matches[1] );
					$extraParamsString = $matches[2];
					$extraParams = explode( ';', $extraParamsString );
					foreach ( $extraParams as $extraParam ) {
						$extraParamParts = explode( '=', $extraParam, 2 );
						if ( count( $extraParamParts ) == 1 ) {
							$paramKey = trim( $extraParamParts[0] );
							$fieldData[$paramKey] = true;
						} else {
							$paramKey = trim( $extraParamParts[0] );
							$paramValue = trim( $extraParamParts[1] );
							if ( $paramKey == 'allowed values' ) {
								$fieldData['allowedValues'] = array_map( 'trim', explode( ',', $paramValue ) );
							} else {
								$fieldData[$paramKey] = $paramValue;
							}
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
		$text = wfMessage( 'cargo-definestable', $tableName )->text();
		$text .= " [[$pageName|$viewTableMsg]].";

		return $text;
	}

}
