<?php

class CargoTemplateFormat extends CargoDisplayFormat {

	function allowedParameters() {
		return array( 'template' );
	}

	function displayRow( $templateName, $row, $fieldDescriptions ) {
		$wikiText = '{{' . $templateName;
		// We add the field number in to the template call to not
		// mess up values that contain '='.
		$fieldNum = 1;
		foreach ( $fieldDescriptions as $fieldName => $fieldDescription ) {
			$wikiText .= '|' . $fieldNum . '=' . $row[$fieldName];
			$fieldNum++;
		}
		$wikiText .= '}}' . "\n";
		return $wikiText;
	}

	function display( $valuesTable, $formattedValuesTable, $fieldDescriptions, $displayParams ) {
		$text = '';
		$templateName = $displayParams['template'];
		foreach ( $valuesTable as $row ) {
			$text .= $this->displayRow( $templateName, $row, $fieldDescriptions );
		}
		global $wgParser;
		global $wgTitle;
		$parserOutput = $wgParser->parse( $text, $wgTitle, new ParserOptions() );
		return $parserOutput->getText();
	}

}