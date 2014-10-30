<?php
/**
 * @author Yaron Koren
 * @ingroup Cargo
 */

class CargoCalendarFormat extends CargoDisplayFormat {
	function allowedParameters() {
		return array( 'width', 'start date' );
	}

	function isDeferred() {
		return true;
	}

	function queryAndDisplay( $sqlQueries, $displayParams, $querySpecificParams = null ) {
		global $wgCargoCalendarDataURL;

		$this->mOutput->addModules( 'ext.cargo.calendar' );
		$cd = SpecialPage::getTitleFor( 'CalendarData' );
		$queryParams = array(
			'tables' => array(),
			'join on' => array(),
			'fields' => array(),
			'where' => array(),
			'color' => array(),
		);
		foreach ( $sqlQueries as $i => $sqlQuery ) {
			$queryParams['tables'][] = implode( ',', $sqlQuery->mTableNames );
			$queryParams['join on'][] = $sqlQuery->mJoinOnStr;
			$queryParams['fields'][] = $sqlQuery->mFieldsStr;
			$queryParams['where'][] = $sqlQuery->mWhere;
			if ( $querySpecificParams != null ) {
				if ( array_key_exists( 'color', $querySpecificParams[$i] ) ) {
					$queryParams['color'][] = $querySpecificParams[$i]['color'];
				} else {
					// Stick an empty value in there, to
					// preserve the order for the queries
					// that do contain a color.
					$queryParams['color'][] = null;
				}
			}
		}

		if ( array_key_exists( 'width', $displayParams ) ) {
			$width = $displayParams['width'];
		} else {
			$width = "100%";
		}

		$attrs = array(
			'class' => 'cargoCalendar',
			'dataurl' => $cd->getFullURL( $queryParams ),
			'style' => "width: $width"
		);
		if ( array_key_exists( 'start date', $displayParams ) ) {
			$attrs['startdate'] = $displayParams['start date'];
		}
		$text = Html::rawElement( 'div', $attrs, '' );

		return $text;
	}

}
