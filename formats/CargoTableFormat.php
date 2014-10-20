<?php
/**
 * @author Yaron Koren
 * @ingroup Cargo
 */

class CargoTableFormat extends CargoDisplayFormat {

	function allowedParameters() {
		return array();
	}

	function display( $valuesTable, $formattedValuesTable, $fieldDescriptions, $displayParams ) {
		$text = '<table style="border-collapse: collapse;">';
		$text .= '<tr>';
		foreach( array_keys( $fieldDescriptions ) as $field ) {
			$text .= Html::rawElement( 'th', null, $field ) . "\n";
		}
		$text .= "</tr>\n";
		foreach ( $formattedValuesTable as $i => $row ) {
			$backgroundColor = ( $i % 2 == 0 ) ? '#fff' : '#eee';
			$text .= "<tr style=\"background: $backgroundColor\">\n";
			foreach( array_keys( $fieldDescriptions ) as $field ) {
				if ( array_key_exists( $field, $row ) ) {
					$value = $row[$field];
				} else {
					$value = null;
				}
				$text .= Html::rawElement( 'td', array( 'style' => 'padding: 5px; border: #ccc 1px solid;' ), $value ) . "\n";
			}
			$text .= "</tr>\n";
		}
		$text .= "</table>";
		return $text;
	}

}
