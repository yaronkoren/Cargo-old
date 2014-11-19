<?php
/**
 * Gets the results of a Cargo query for one date range, specifically for use
 * by the FullCalendar JS library, for the 'calendar' format.
 *
 * @author Yaron Koren
 * @ingroup Cargo
 */

class CargoCalendarData extends SpecialPage {

	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct( 'CalendarData' );
	}

	function execute( $query ) {
		$this->getOutput()->setArticleBodyOnly( true );
		//list( $limit, $offset ) = wfCheckLimits();

		$req = $this->getRequest();
		$tableArray = $req->getArray( 'tables' );
		$whereArray = $req->getArray( 'where' );
		$joinOnArray = $req->getArray( 'join_on' );
		$groupByArray = $req->getArray( 'group_by' );
		$orderByArray = $req->getArray( 'order_by' );
		$colorArray = $req->getArray( 'color' );
		$fieldsArray = $req->getArray( 'fields' );

		$limitStr = null;

		$startDate = $req->getVal( 'start' );
		$endDate = $req->getVal( 'end' );

		$displayedArray = array();
		foreach ( $tableArray as $i => $table ) {
			$sqlQuery = CargoSQLQuery::newFromValues( $table, $fieldsArray[$i], $whereArray[$i], $joinOnArray[$i], $groupByArray[$i], $orderByArray[$i], $limitStr );

			$dateFields = array();
			foreach( $sqlQuery->mFieldDescriptions as $field => $description ) {
				if ( $description['type'] == 'Date' || $description['type'] == 'Datetime' ) {
					$dateFields[] = $field;
				}
			}

			$where = $sqlQuery->mWhere;
			if ( $where != '' ) {
				$where .= " AND ";
			}
			$where .= "(";
			foreach ( $dateFields as $j => $dateField ) {
				if ( $j > 0 ) {
					$where .= " OR ";
				}
				$where .= "($dateField >= '$startDate' AND $dateField <= '$endDate')";
			}
			$where .= ")";
			$sqlQuery->mWhere = $where;

			$queryResults = $sqlQuery->run();

			foreach ( $queryResults as $queryResult ) {
				$title = Title::newFromText( $queryResult['_pageName'] );
				$displayedArray[] = array(
					// Get first field for the title - not
					// necessarily the page name.
					'title' => reset( $queryResult ),
					'url' => $title->getLocalURL(),
					'start' => $queryResult[$dateFields[0]],
					'color' => $colorArray[$i]
				);
			}
		}

		print json_encode( $displayedArray );
	}

}
