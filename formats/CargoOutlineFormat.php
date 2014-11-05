<?php

/**
 * A class to print query results in an outline format, along with some
 * helper classes to handle the aggregation
 *
 * Code is based heavily on the code for the 'outline' format in the
 * Semantic Result Formats extension.
 *
 * @author Yaron Koren
 */

/**
 * Represents a single row in the outline.
 */
class CargoOutlineRow {
	var $mOutlineFields;
	var $mDisplayFields;

	function __construct() {
		$this->mOutlineFields = array();
		$this->mDisplayFields = array();
	}

	function addOutlineFieldValues( $fieldName, $values ) {
		$this->mOutlineFields[$fieldName] = $values;
	}

	function addOutlineFieldValue( $fieldName, $value ) {
		$this->mOutlineFields[$fieldName] = array( $value );
	}

	function addDisplayFieldValue( $fieldName, $value ) {
		$this->mDisplayFields[$fieldName] = $value;
	}

	function getOutlineFieldValues( $fieldName ) {
		if ( !array_key_exists( $fieldName, $this->mOutlineFields ) ) {
			throw new MWException( "Error: the outline field '$fieldName' must be among this query's fields." );
		}
		return $this->mOutlineFields[$fieldName];
	}
}

/**
 * A tree structure for holding the outline data.
 */
class CargoOutlineTree {
	var $mTree;
	var $mUnsortedRows;

	function __construct( $rows = array() ) {
		$this->mTree = array();
		$this->mUnsortedRows = $rows;
	}

	function addRow( $row ) {
		$this->mUnsortedRows[] = $row;
	}

	function categorizeRow( $vals, $row ) {
		foreach ( $vals as $val ) {
			if ( array_key_exists( $val, $this->mTree ) ) {
				$this->mTree[$val]->mUnsortedRows[] = $row;
			} else {
				$this->mTree[$val] = new CargoOutlineTree( array( $row ) );
			}
		}
	}

	function addField( $field ) {
		if ( count( $this->mUnsortedRows ) > 0 ) {
			foreach ( $this->mUnsortedRows as $row ) {
				$fieldValues = $row->getOutlineFieldValues( $field );
				$this->categorizeRow( $fieldValues, $row );
			}
			$this->mUnsortedRows = null;
		} else {
			foreach ( $this->mTree as $i => $node ) {
				$this->mTree[$i]->addField( $field );
			}
		}
	}
}

class CargoOutlineFormat extends CargoListFormat {
	protected $mOutlineFields = array();
	var $mFieldDescriptions;

	function printTree( $outlineTree, $level = 0 ) {
		$text = "";
		if ( ! is_null( $outlineTree->mUnsortedRows ) ) {
			$text .= "<ul>\n";
			foreach ( $outlineTree->mUnsortedRows as $row ) {
				$text .= "<li>{$this->displayRow( $row->mDisplayFields, $this->mFieldDescriptions )}</li>\n";
			}
			$text .= "</ul>\n";
		}
		if ( $level > 0 ) $text .= "<ul>\n";
		$numLevels = count( $this->mOutlineFields );
		// Set font size and weight depending on level we're at.
		$fontLevel = $level;
		if ( $numLevels < 4 ) {
			$fontLevel += ( 4 - $numLevels );
		}
		if ( $fontLevel == 0 ) {
			$fontSize = 'x-large';
		} elseif ( $fontLevel == 1 ) {
			$fontSize = 'large';
		} elseif ( $fontLevel == 2 ) {
			$fontSize = 'medium';
		} else {
			$fontSize = 'small';
		}
		if ( $fontLevel == 3 ) {
			$fontWeight = 'bold';
		} else {
			$fontWeight = 'regular';
		}
		foreach ( $outlineTree->mTree as $key => $node ) {
			$text .= "<p style=\"font-size: $fontSize; font-weight: $fontWeight;\">$key</p>\n";
			$text .= $this->printTree( $node, $level + 1 );
		}
		if ( $level > 0 ) $text .= "</ul>\n";
		return $text;
	}

	function display( $valuesTable, $formattedValuesTable, $fieldDescriptions, $displayParams ) {
		if ( !array_key_exists( 'outline fields', $displayParams ) ) {
			throw new MWException( "Error: 'outline fields' parameter must be set for 'outline' format." );
		}
		$outlineFields = explode( ',', $displayParams['outline fields'] );
		$this->mOutlineFields = array_map( 'trim', $outlineFields );
		$this->mFieldDescriptions = $fieldDescriptions;

		// For each result row, create an array of the row itself
		// and all its sorted-on fields, and add it to the initial
		// 'tree'.
		$outlineTree = new CargoOutlineTree();
		foreach ( $formattedValuesTable as $queryResultsRow ) {
			$coRow = new CargoOutlineRow();
			foreach ( $queryResultsRow as $fieldName => $value ) {
				if ( in_array( $fieldName, $this->mOutlineFields ) ) {
					if ( array_key_exists( 'isList', $fieldDescriptions[$fieldName] ) ) {
						$delimiter = $fieldDescriptions[$fieldName]['delimiter'];
						$coRow->addOutlineFieldValues( $fieldName, array_map( 'trim', explode( $delimiter, $value ) ) );
					} else {
						$coRow->addOutlineFieldValue( $fieldName, $value );
					}
				} else {
					$coRow->addDisplayFieldValue( $fieldName, $value );
				}
			}
			$outlineTree->addRow( $coRow );
		}

		// Now, cycle through the outline fields, creating the tree.
		foreach ( $this->mOutlineFields as $outlineField ) {
			$outlineTree->addField( $outlineField );
		}
		$result = $this->printTree( $outlineTree );

		return $result;
	}

}
