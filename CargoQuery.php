<?php
/**
 * CargoQuery - class for the #cargo_query parser function.
 *
 * @author Yaron Koren
 * @ingroup Cargo
 */

class CargoQuery {

	/**
	 * Gets the schema information for the given set of tables.
	 */
	static function getTableSchemas( $tableNames ) {
		$mainTableNames = array();
		foreach( $tableNames as $tableName ) {
			if ( strpos( $tableName, '__' ) !== false ) {
				// We just want the first part of it.
				$tableNameParts = explode( '__', $tableName );
				$tableName = $tableNameParts[0];
			}
			if ( !in_array( $tableName, $mainTableNames ) ) {
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
	 * Display the link to view more results, pointing to Special:ViewData.
	 */
	public static function viewMoreResultsLink( $tablesStr, $fieldsStr, $whereStr, $joinOnStr, $groupByStr, $orderByStr, $queryLimit, $format, $displayParams ) {
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
		if ( $format != '' ) {
			$queryStringParams['format'] = $format;
		}
		$queryStringParams['offset'] = $queryLimit;
		$queryStringParams['limit'] = 100; // Is that a reasonable number in all cases?

		// Add format-specific params.
		foreach ( $displayParams as $key => $value ) {
			$queryStringParams[$key] = $value;
		}

		return Html::rawElement( 'p', null, Linker::link( $vd, wfMessage( 'moredotdotdot' )->parse(), array(), $queryStringParams ) );
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

		$formatClass = self::getFormatClass( $format, $sqlQuery->mFieldDescriptions );
		$formatObject = new $formatClass( $parser->getOutput() );

		// Let the format run the query itself, if it wants to.
		if ( $formatObject->isDeferred() ) {
			$text = $formatObject->queryAndDisplay( array( $sqlQuery ), $displayParams );
			$text = $parser->insertStripItem( $text, $parser->mStripState );
			return $text;
		}

		$queryResults = $sqlQuery->run();

		if ( is_null( $format ) ) {
			return $queryResults;
		}

		$formattedQueryResults = self::getFormattedQueryResults( $queryResults, $sqlQuery->mFieldDescriptions, $parser );

		// Finally, do the display, based on the format.
		$text = $formatObject->display( $queryResults, $formattedQueryResults, $sqlQuery->mFieldDescriptions, $displayParams );

		// If there are (seemingly) more results than what we showed,
		// show a "View more" link that links to Special:ViewData.
		if ( count( $queryResults ) == $sqlQuery->mQueryLimit ) {
			$text .= self::viewMoreResultsLink( $tablesStr, $fieldsStr, $whereStr, $joinOnStr, $groupByStr, $orderByStr, $sqlQuery->mQueryLimit, $format, $displayParams );
		}

		$text = $parser->insertStripItem( $text, $parser->mStripState );

		return $text;
	}

	static function getFormattedQueryResults( $queryResults, $fieldDescriptions, $parser ) {
		// The assignment will do a copy.
		$formattedQueryResults = $queryResults;
		foreach ( $queryResults as $rowNum => $row ) {
			foreach ( $row as $fieldName => $value ) {
				if ( trim( $value ) == '' ) {
					continue;
				}

				if ( !array_key_exists( $fieldName, $fieldDescriptions ) ) {
					continue;
				}

				$fieldDescription = $fieldDescriptions[$fieldName];
				if ( array_key_exists( 'type', $fieldDescription ) ) {
					$type = trim( $fieldDescription['type'] );
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
					$formattedQueryResults[$rowNum][$fieldName] = $text;
				} elseif ( $type == 'URL' ) {
					if ( array_key_exists( 'link text', $fieldDescription ) ) {
						$text = Html::element( 'a', array( 'href' => $value ), $fieldDescription['link text'] );
						$formattedQueryResults[$rowNum][$fieldName] = $text;
					} else {
						// Otherwise, do nothing.
					}
				} elseif ( $type == 'Wikitext' || $type == '' ) {
					// This is here in case the value was
					// set using {{PAGENAME}}, which for
					// some reason HTML-encodes some of its
					// characters - see
					// https://www.mediawiki.org/wiki/Help:Magic_words#Page_names
					// Of course, Text and Page fields could
					// be set using {{PAGENAME}} as well,
					// but those seem less likely.
					$value = htmlspecialchars_decode( $value );
					// Parse it as if it's wikitext.
					// The exact call depends on whether
					// we're in a special page or not.
					global $wgTitle, $wgRequest;
					if ( is_null( $parser ) ) {
						global $wgParser;
						$parser = $wgParser;
					}
					if ( $wgTitle->isSpecialPage() ||
						// The 'pagevalues' action is
						// also a Cargo special page.
						$wgRequest->getVal( 'action' ) == 'pagevalues' ) {
						$parserOutput = $parser->parse( $value, $wgTitle, new ParserOptions(), false );
						$value = $parserOutput->getText();
					} else {
						$value = $parser->internalParse( $value );
					}
					$formattedQueryResults[$rowNum][$fieldName] = $value;
				}
			}
		}
		return $formattedQueryResults;
	}

}
