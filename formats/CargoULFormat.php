<?php
/**
 * @author Yaron Koren
 * @ingroup Cargo
 */

class CargoULFormat extends CargoListFormat {

	function allowedParameters() {
		return array();
	}

	function display( $valuesTable, $formattedValuesTable, $fieldDescriptions, $displayParams ) {
		$text = "<ul>\n";
		foreach ( $formattedValuesTable as $i => $row ) {
			$text .= '<li>' . $this->displayRow( $row, $fieldDescriptions ) . "</li>\n";
		}
		$text .= "</ul>\n";
		return $text;
	}

}
