<?php
/**
 * @author Yaron Koren
 * @ingroup Cargo
 */

class CargoDisplayFormat {

	function __construct( $output ) {
		$this->mOutput = $output;
	}

	function allowedParameters() {
		return array();
	}

}
