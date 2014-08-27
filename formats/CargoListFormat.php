<?php

class CargoListFormat extends CargoDisplayFormat {

	function allowedParameters() {
		return array( 'delimiter' );
	}

	function displayRow( $row, $fieldDescriptions ) {
		$startParenthesisAdded = false;
		$firstField = true;
		foreach ( $fieldDescriptions as $fieldName => $fieldDescription ) {
			$fieldValue = $row[$fieldName];
			if ( trim( $fieldValue ) == '' ) {
				continue;
			}
			if ( $firstField ) {
				$text = $fieldValue;
				$firstField = false;
			} else {
				if ( ! $startParenthesisAdded ) {
					$text .= ' (';
					$startParenthesisAdded = true;
				} else {
					$text .= ', ';
				}
				$text .= "<strong>$fieldName:</strong> $fieldValue";
			}
		}
		if ( $startParenthesisAdded ) {
			$text .= ')';
		}
		return $text;
	}

	function display( $valuesTable, $fieldDescriptions, $displayParams ) {
		$text = '';
		$delimiter = ( array_key_exists( 'delimiter', $displayParams ) ) ? $displayParams['delimiter'] : ',';
		foreach ( $valuesTable as $i => $row ) {
			if ( $i > 0 ) {
				$text .= $delimiter . ' ';
			}
			$text .= $this->displayRow( $row, $fieldDescriptions );
		}
		return $text;
	}

}
