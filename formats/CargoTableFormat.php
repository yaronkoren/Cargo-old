<?php

class CargoTableFormat extends CargoDisplayFormat {

	function allowedParameters() {
		return array();
	}

	function display( $valuesTable, $fieldDescriptions, $displayParams ) {
		global $wgOut;
		$wgOut->addModuleStyles( 'ext.Cargo' );

		$text = '<table class="cargoTable"><tr>';
		foreach( array_keys( $fieldDescriptions ) as $field ) {
			$text .= Html::rawElement( 'th', null, $field );
		}
		$text .= "</tr>\n";
		foreach ( $valuesTable as $i => $row ) {
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
