<?php

class CargoTableFormat extends CargoDisplayFormat {

	function allowedParameters() {
		return array();
	}

	function display( $valuesTable, $formattedValuesTable, $fieldDescriptions, $displayParams ) {
		global $wgOut;
		$wgOut->addModuleStyles( 'ext.cargo.main' );

		$text = '<table class="cargoTable"><tr>';
		foreach( array_keys( $fieldDescriptions ) as $field ) {
			$text .= Html::rawElement( 'th', null, $field );
		}
		$text .= "</tr>\n";
		foreach ( $formattedValuesTable as $i => $row ) {
			$text .= '<tr>';
			foreach( array_keys( $fieldDescriptions ) as $field ) {
				$text .= Html::rawElement( 'td', null, $row[$field] );
			}
			$text .= "</tr>\n";
		}
		$text .= "</table>";
		return $text;
	}

}