<?php
/**
 * @author Yaron Koren
 * @ingroup Cargo
 */

class CargoCategoryFormat extends CargoListFormat {

	function allowedParameters() {
		return array();
	}

	function display( $valuesTable, $formattedValuesTable, $fieldDescriptions, $displayParams ) {
		global $wgContLang;

		$result = '';
		$numColumns = 3;
		$showHeaders = true;
		$num = count( $valuesTable );

		$prev_first_char = "";
		$rows_per_column = ceil( $num / $numColumns );
		// column width is a percentage
		$column_width = floor( 100 / $numColumns );

		// Print all result rows:
		$rowindex = 0;

		foreach ( $formattedValuesTable as $i => $row ) {
//print_r($row);die;
			$content = reset( $valuesTable[$i] );

			$cur_first_char = $wgContLang->firstChar( $content );

			if ( $rowindex % $rows_per_column == 0 ) {
				$result .= "\n			<div style=\"float: left; width: $column_width%;\">\n";
				if ( $cur_first_char == $prev_first_char )
					$result .= "				<h3>$cur_first_char " . wfMessage( 'listingcontinuesabbrev' )->text() . "</h3>\n				<ul>\n";
			}

			// if we're at a new first letter, end
			// the last list and start a new one
			if ( $cur_first_char != $prev_first_char ) {
				if ( $rowindex % $rows_per_column > 0 )
					$result .= "				</ul>\n";
				$result .= "				<h3>$cur_first_char</h3>\n				<ul>\n";
			}
			$prev_first_char = $cur_first_char;

			$result .= '<li>';
			$first_col = true;

/*
			if ( $this->mTemplate !== '' ) { // build template code
				$this->hasTemplates = true;
				$wikitext = ( $this->mUserParam ) ? "|userparam=$this->mUserParam":'';
				$i = 1; // explicitly number parameters for more robust parsing (values may contain "=")

				foreach ( $row as $field ) {
					$wikitext .= '|' . $i++ . '=';
					$first_value = true;

					while ( ( $text = $field->getNextText( SMW_OUTPUT_WIKI, $this->getLinker( $first_col ) ) ) !== false ) {
						if ( $first_value ) $first_value = false; else $wikitext .= $this->mDelim . ' ';
						$wikitext .= $text;
					}

					$first_col = false;
				}

				$wikitext .= "|#=$rowindex";
				$result .= '{{' . $this->mTemplate . $wikitext . '}}';
				// str_replace('|', '&#x007C;', // encode '|' for use in templates (templates fail otherwise) -- this is not the place for doing this, since even DV-Wikitexts contain proper "|"!
			} else {  // build simple list
*/
				$first_col = true;
				$found_values = false; // has anything but the first column been printed?

/*
				foreach( array_keys( $fieldDescriptions ) as $field ) {
					$first_value = true;
					$text = $row[$field];

					//while ( ( $text = $field false ) {
						if ( !$first_col && !$found_values ) { // first values after first column
							$result .= ' (';
							$found_values = true;
						} elseif ( $found_values || !$first_value ) {
							// any value after '(' or non-first values on first column
							$result .= ', ';
						}

						if ( $first_value ) { // first value in any column, print header
							$first_value = false;

							//if ( $showHeaders ) {
							//	$result .= $field . ' ';
							//}
						}

						$result .= $text; // actual output value
					//}

					$first_col = false;
				}
*/
				$result .= self::displayRow( $row, $fieldDescriptions );

				if ( $found_values ) $result .= ')';
			//}

			$result .= '</li>';

			// end list if we're at the end of the column
			// or the page
			if ( ( $rowindex + 1 ) % $rows_per_column == 0 && ( $rowindex + 1 ) < $num ) {
				$result .= "				</ul>\n			</div> <!-- end column -->";
			}

			$rowindex++;
		}

		if ( $result === '' ) {

			$res->addErrors( array(
				$this->msg( 'smw-qp-empty-data' )->inContentLanguage()->text()
			) );

			return $result;
		}

		$result .= "</ul>\n</div> <!-- end column -->";
		// clear all the CSS floats
		$result .= "\n" . '<br style="clear: both;"/>';

		// <H3> will generate TOC entries otherwise.  Probably need another way
		// to accomplish this -- user might still want TOC for other page content.
		//$result .= '__NOTOC__';
		return $result;
	}

}
