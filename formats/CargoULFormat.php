<?php

class CargoULFormat extends CargoListFormat {

	function allowedParameters() {
		return array();
	}

	function display( $valuesTable, $fieldDescriptions, $displayParams ) {
		$text = "<ul>\n";
		foreach ( $valuesTable as $i => $row ) {
			$text .= '<li>' . $this->displayRow( $row, $fieldDescriptions ) . "</li>\n";
		}
		$text .= "</ul>\n";
		return $text;
	}

}
