<?php
/**
 * CargoQueryDisplayer - class for displaying query results.
 *
 * @author Yaron Koren
 * @ingroup Cargo
 */

class CargoQueryDisplayer {

	var $mSQLQuery;
	var $mFormat;
	var $mDisplayParams = array();
	var $mParser = null;
	var $mFieldDescriptions;

	public static function newFromSQLQuery( $sqlQuery ) {
		$cqd = new CargoQueryDisplayer();
		$cqd->mSQLQuery = $sqlQuery;
		$cqd->mFieldDescriptions = $sqlQuery->mFieldDescriptions;

		return $cqd;
	}

	/**
	 * Given a format name, and a list of the fields, returns the name
	 * of the the function to call for that format.
	 */
	public function getFormatClass() {
		$formatClasses = array(
			'list' => 'CargoListFormat',
			'ul' => 'CargoULFormat',
			'ol' => 'CargoOLFormat',
			'template' => 'CargoTemplateFormat',
			'embedded' => 'CargoEmbeddedFormat',
			'csv' => 'CargoCSVFormat',
			'json' => 'CargoJSONFormat',
			'outline' => 'CargoOutlineFormat',
			'tree' => 'CargoTreeFormat',
			'table' => 'CargoTableFormat',
			'dynamic table' => 'CargoDynamicTableFormat',
			'googlemaps' => 'CargoGoogleMapsFormat',
			'openlayers' => 'CargoOpenLayersFormat',
			'calendar' => 'CargoCalendarFormat',
			'category' => 'CargoCategoryFormat',
			'bar chart' => 'CargoBarChartFormat',
		);

		if ( array_key_exists( $this->mFormat, $formatClasses ) ) {
			return $formatClasses[$this->mFormat];
		}

		$formatClass = null;
		wfRunHooks( 'CargoGetFormatClass', array( $this->mFormat, &$formatClass ) );
		if ( $formatClass != null ) {
			return $formatClass;
		}

		if ( count( $this->mFieldDescriptions ) > 1 ) {
			$format = 'table';
		} else {
			$format = 'list';
		}
		return $formatClasses[$format];
	}

	public function getFormatter( $out ) {
		$formatClass = $this->getFormatClass();
		$formatObject = new $formatClass( $out );
		return $formatObject;
	}

	public function getFormattedQueryResults( $queryResults ) {
		// The assignment will do a copy.
		$formattedQueryResults = $queryResults;
		foreach ( $queryResults as $rowNum => $row ) {
			foreach ( $row as $fieldName => $value ) {
				if ( trim( $value ) == '' ) {
					continue;
				}

				if ( !array_key_exists( $fieldName, $this->mFieldDescriptions ) ) {
					continue;
				}

				$fieldDescription = $this->mFieldDescriptions[$fieldName];
				if ( array_key_exists( 'type', $fieldDescription ) ) {
					$type = trim( $fieldDescription['type'] );
				} else {
					$type = null;
				}

				$text = '';
				if ( array_key_exists( 'isList', $fieldDescription ) ) {
					// There's probably an easier way to do
					// this, using array_map().
					$delimiter = $fieldDescription['delimiter'];
					$fieldValues = explode( $delimiter, $value );
					foreach( $fieldValues as $i => $fieldValue ) {
						if ( trim( $fieldValue ) == '' ) continue;
						if ( $i > 0 ) $text .= "$delimiter ";
						$text .= self::formatFieldValue( $fieldValue, $type, $fieldDescription, $this->mParser );
					}
				} else {
					$text = self::formatFieldValue( $value, $type, $fieldDescription, $this->mParser );
				}
				if ( $text != '' ) {
					$formattedQueryResults[$rowNum][$fieldName] = $text;
				}
			}
		}
		return $formattedQueryResults;
	}

	public static function formatFieldValue( $value, $type, $fieldDescription, $parser ) {
		if ( $type == 'Page' ) {
			$title = Title::newFromText( $value );
			return Linker::link( $title );
		} elseif ( $type == 'File' ) {
			// 'File' values are basically pages in the File:
			// namespace; they are displayed as thumbnails within
			// queries.
			$title = Title::newFromText( $value, NS_FILE );
			return Linker::makeThumbLinkObj( $title, wfLocalFile( $title ), $value, '' );
		} elseif ( $type == 'URL' ) {
			if ( array_key_exists( 'link text', $fieldDescription ) ) {
				return Html::element( 'a', array( 'href' => $value ), $fieldDescription['link text'] );
			} else {
				// Otherwise, do nothing.
				return null;
			}
		} elseif ( $type == 'Date' || $type == 'Datetime' ) {
			global $wgAmericanDates;
			$seconds = strtotime( $value );
			if ( $wgAmericanDates ) {
				// We use MediaWiki's representation of month
				// names, instead of PHP's, because its i18n
				// support is of course far superior.
				$dateText = CargoDrilldownUtils::monthToString( date( 'm', $seconds ) );
				$dateText .= ' ' . date( 'j, Y', $seconds );
			} else {
				$dateText = date( 'Y-m-d', $seconds );
			}
			if ( $type == 'Date' ) {
				return $dateText;
			}

			// It's a Datetime - add time as well.
			// @TODO - have some variable for 24-hour time display?
			$timeText = date( 'g:i:s A', $seconds );
			return "$dateText $timeText";
		} elseif ( $type == 'Wikitext' || $type == '' ) {
			return CargoUtils::smartParse( $value, $parser );
		}
		// If it's not any of these specially-handled types, just
		// return null.
	}

	/**
	 * Display the link to view more results, pointing to Special:ViewData.
	 */
	public function viewMoreResultsLink() {
		$vd = Title::makeTitleSafe( NS_SPECIAL, 'ViewData' );
		$queryStringParams = array();
		$sqlQuery = $this->mSQLQuery;
		$queryStringParams['tables'] = $sqlQuery->mTablesStr;
		$queryStringParams['fields'] = $sqlQuery->mFieldsStr;
		if ( $sqlQuery->mWhereStr != '' ) {
			$queryStringParams['where'] = $sqlQuery->mWhereStr;
		}
		if ( $sqlQuery->mJoinOnStr != '' ) {
			$queryStringParams['join_on'] = $sqlQuery->mJoinOnStr;
		}
		if ( $sqlQuery->mGroupByStr != '' ) {
			$queryStringParams['group_by'] = $sqlQuery->mGroupByStr;
		}
		if ( $sqlQuery->mOrderByStr != '' ) {
			$queryStringParams['order_by'] = $sqlQuery->mOrderByStr;
		}
		if ( $this->mFormat != '' ) {
			$queryStringParams['format'] = $this->mFormat;
		}
		$queryStringParams['offset'] = $sqlQuery->mQueryLimit;
		$queryStringParams['limit'] = 100; // Is that a reasonable number in all cases?

		// Add format-specific params.
		foreach ( $this->mDisplayParams as $key => $value ) {
			$queryStringParams[$key] = $value;
		}

		return Html::rawElement( 'p', null, Linker::link( $vd, wfMessage( 'moredotdotdot' )->parse(), array(), $queryStringParams ) );
	}

}