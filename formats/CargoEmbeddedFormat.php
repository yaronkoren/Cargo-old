<?php
/**
 * @author Yaron Koren
 * @ingroup Cargo
 */

class CargoEmbeddedFormat extends CargoDisplayFormat {

	function allowedParameters() {
		return array();
	}

	function displayRow( $row ) {
		$pageName = reset( $row );
		$wikiText =<<<END
<p style="font-size: small; color: #555; text-align: right;">$pageName</p>
{{:$pageName}}


--------------

END;
		return $wikiText;
	}

	function display( $valuesTable, $formattedValuesTable, $fieldDescriptions, $displayParams ) {
		$text = '';
		foreach ( $valuesTable as $row ) {
			$text .= $this->displayRow( $row );
		}
		return CargoQuery::smartParse( $text, $this->mParser );
	}

}
