<?php
/**
 * CargoQuery - class for the #cargo_query parser function.
 *
 * @author Yaron Koren
 * @ingroup Cargo
 */

class CargoQuery {

	static $numCalls = 0;

	/**
	 * Gets the schema information for the given set of tables.
	 */
	static function getTableSchemas( $tableNames ) {
		$mainTableNames = array();
		$fieldTableNames = array();
		foreach( $tableNames as $tableName ) {
			if ( strpos( $tableName, '__' ) !== false ) {
				$tableNameParts = explode( '__', $tableName );
				$mainTableNames[] = $tableNameParts[0];
				$fieldTableNames[] = $tableNameParts;
			} else {
				$mainTableNames[] = $tableName;
			}
		}
		$tableSchemas = array();
		$dbr = wfGetDB( DB_SLAVE );
		$res = $dbr->select( 'cargo_tables', array( 'main_table', 'table_schema' ), array( 'main_table' => $mainTableNames ) );
		while ( $row = $dbr->fetchRow( $res ) ) {
			$tableName = $row['main_table'];
			$tableFieldsString = $row['table_schema'];
			$tableSchemas[$tableName] = unserialize( $tableFieldsString );
		}
		// Somewhat of a @HACK - add $fieldTableNames into this
		// schemas array.
		$tableSchemas['_fieldTables'] = $fieldTableNames;
		return $tableSchemas;
	}

	/**
	 * Handles the #cargo_query parser function - calls a query on the
	 * Cargo data stored in the database.
	 */
	public static function run( &$parser ) {
		$params = func_get_args();
		array_shift( $params ); // we already know the $parser...

		$tablesStr = null;
		$fieldsStr = null;
		$whereStr = null;
		$joinOnStr = null;
		$groupByStr = null;
		$orderByStr = null;
		$limitStr = null;
		$format = 'auto'; // default
		$displayParams = array();

		foreach ( $params as $param ) {
			$parts = explode( '=', $param, 2 );
			
			if ( count( $parts ) != 2 ) {
				continue;
			}
			$key = trim( $parts[0] );
			$value = trim( $parts[1] );
			if ( $key == 'tables' ) {
				$tablesStr = $value;
			} elseif ( $key == 'fields' ) {
				$fieldsStr = $value;
			} elseif ( $key == 'where' ) {
				$whereStr = $value;
			} elseif ( $key == 'join on' ) {
				$joinOnStr = $value;
			} elseif ( $key == 'group by' ) {
				$groupByStr = $value;
			} elseif ( $key == 'order by' ) {
				$orderByStr = $value;
			} elseif ( $key == 'limit' ) {
				$limitStr = $value;
			} elseif ( $key == 'format' ) {
				$format = $value;
			} else {
				// We'll assume it's going to the formatter.
				$displayParams[$key] = $value;
			}
		}

		try {
			$queryResults = self::getOrDisplayQueryResultsFromStrings( $tablesStr, $fieldsStr, $whereStr, $joinOnStr, $groupByStr, $orderByStr, $limitStr, $format, $displayParams, $parser );
		} catch ( Exception $e ) {
			return CargoUtils::formatError( $e->getMessage() );
		}

		return $queryResults;
	}

	/**
	 * Given a format name, and a list of the fields, returns the name
	 * of the the function to call for that format.
	 */
	static function getFormatClass( $format, $fieldDescriptions ) {
		global $wgCargoDisplayFormats;

		if ( array_key_exists( $format, $wgCargoDisplayFormats ) ) {
		} elseif ( count( $fieldDescriptions ) > 1 ) {
			$format = 'simpletable';
		} else {
			$format = 'list';
		}
		return $wgCargoDisplayFormats[$format];
	}

	/**
	 * Takes in a set of strings representing elements of a SQL query,
	 * and returns either an array of results, or a display of the
	 * results, depending on whether or not the format parameter is
	 * specified.
	 * This method is used by both #cargo_query and the 'cargoquery'
	 * API action.
	 */
	public static function getOrDisplayQueryResultsFromStrings( $tablesStr, $fieldsStr, $whereStr, $joinOnStr, $groupByStr, $orderByStr, $limitStr, $format = null, $displayParams = null, $parser = null ) {

		$sqlQuery = CargoSQLQuery::newFromValues( $tablesStr, $fieldsStr, $whereStr, $joinOnStr, $groupByStr, $orderByStr, $limitStr );

		$queryResults = $sqlQuery->run();

		if ( is_null( $format ) ) {
			return $queryResults;
		}

		// Format the data, according to the type.
		if ( $format != 'template' ) {
			self::formatQueryResults( $queryResults, $sqlQuery->mFieldDescriptions, $parser );
		}

		// Finally, do the display, based on the format.
		$formatClass = self::getFormatClass( $format, $sqlQuery->mFieldDescriptions );
		$formatObject = new $formatClass();
		$text = $formatObject->display( $queryResults, $sqlQuery->mFieldDescriptions, $displayParams );

		// If there are (seemingly) more results than what we showed,
		// show a "View more" link that links to Special:ViewData.
		if ( count( $queryResults ) == $sqlQuery->mQueryLimit ) {
			//$text .= '[{{canonicalurl:Special:ViewData}}|See more]';
			$vd = Title::makeTitleSafe( NS_SPECIAL, 'ViewData' );
			$queryStringParams = array();
			$queryStringParams['tables'] = $tablesStr;
			$queryStringParams['fields'] = $fieldsStr;
			if ( $whereStr != '' ) {
				$queryStringParams['where'] = $whereStr;
			}
			if ( $joinOnStr != '' ) {
				$queryStringParams['join_on'] = $joinOnStr;
			}
			if ( $groupByStr != '' ) {
				$queryStringParams['group_by'] = $groupByStr;
			}
			if ( $orderByStr != '' ) {
				$queryStringParams['order_by'] = $orderByStr;
			}
			//$queryStringParams['limit'] = $queryLimit;
			if ( $format != '' ) {
				$queryStringParams['format'] = $format;
			}
			$queryStringParams['offset'] = $sqlQuery->mQueryLimit;

			// Add format-specific params.
			foreach ( $displayParams as $key => $value ) {
					$queryStringParams[$key] = $value;
			}

			$linkHTML = Html::rawElement( 'p', null, Linker::link( $vd, wfMessage( 'moredotdotdot' )->parse(), array(), $queryStringParams ) );
			$text .= $linkHTML;
		}

//		self::$numCalls++;

//		if ( self::$numCalls == 2 ) {
		// Don't parse the HTML.
		$text = $parser->insertStripItem( $text, $parser->mStripState );
//		}

		return $text;
	}

	static function formatQueryResults( &$queryResults, $fieldDescriptions, $parser ) {
		foreach ( $queryResults as $rowNum => $row ) {
			foreach ( $row as $fieldName => $value ) {
				if ( trim( $value ) == '' ) {
					continue;
				}

				$fieldDescription = $fieldDescriptions[$fieldName];
				if ( array_key_exists( 'type', $fieldDescription ) ) {
					$type = $fieldDescription['type'];
				} else {
					$type = null;
				}
				if ( $type == 'Page' ) {
					$text = '';
					if ( array_key_exists( 'isList', $fieldDescription ) ) {
						// There's probably an easier
						// way to do this, using
						// array_map().
						$delimiter = $fieldDescription['delimiter'];
						$fieldValues = explode( $delimiter, $value );
						foreach( $fieldValues as $i => $fieldValue ) {
							if ( trim( $fieldValue ) == '' ) continue;
							if ( $i > 0 ) $text .= "$delimiter ";
							$title = Title::newFromText( $fieldValue );
							$text .= Linker::link( $title );
						}
					} else {
						$title = Title::newFromText( $value );
						$text .= Linker::link( $title );
					}
					$queryResults[$rowNum][$fieldName] = $text;
				} elseif ( $type == 'URL' ) {
					if ( array_key_exists( 'link text', $fieldDescription ) ) {
						$text = Html::element( 'a', array( 'href' => $value ), $fieldDescription['link text'] );
						$queryResults[$rowNum][$fieldName] = $text;
					} else {
						// Otherwise, do nothing.
					}
				} elseif ( $type == 'Wikitext' || $type == '' ) {
					// Parse it as if it's wikitext.
					global $wgTitle;
					if ( is_null( $parser ) ) {
						global $wgParser;
						$parser = $wgParser;
					}
					$parserOutput = $parser->parse( $value, $wgTitle, new ParserOptions(), false );
					$queryResults[$rowNum][$fieldName] = $parserOutput->getText();
				}
			}
		}
	}

}
