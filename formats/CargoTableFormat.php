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
		global $wgOut;
		$wgOut->addModuleStyles( 'ext.cargo.main' );

		$text = '<table class="cargoTable"><tr>';
		foreach( array_keys( $fieldDescriptions ) as $field ) {
			$text .= Html::rawElement( 'th', null, $field ) . "\n";
		}
		$text .= "</tr>\n";
		foreach ( $formattedValuesTable as $i => $row ) {
			$text .= "<tr>\n";
			foreach( array_keys( $fieldDescriptions ) as $field ) {
				$text .= Html::rawElement( 'td', null, $row[$field] ) . "\n";
			}
			$text .= "</tr>\n";
		}
		$text .= "</table>";
		return $text;
	}

}
