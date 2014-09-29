<?php
/**
 * @author Yaron Koren
 * @ingroup Cargo
 */

class CargoOLFormat extends CargoListFormat {

	function allowedParameters() {
		return array();
	}

	function display( $valuesTable, $formattedValuesTable, $fieldDescriptions, $displayParams ) {
		if ( array_key_exists( 'offset', $displayParams ) ) {
			$offset = $displayParams['offset'];
		} else {
			$offset = 0;
		}
		$text = "<ol start='" . ( $offset + 1 ) . "'>\n";
		foreach ( $formattedValuesTable as $i => $row ) {
			$text .= '<li>' . $this->displayRow( $row, $fieldDescriptions ) . "</li>\n";
		}
		$text .= "</ol>\n";
		return $text;
	}

}
