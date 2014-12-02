<?php
/**
 * Defines a special page that shows the contents of a single table in
 * the Cargo database.
 *
 * @author Yaron Koren
 * @ingroup Cargo
 */

class CargoViewTable extends IncludableSpecialPage {

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct( 'ViewTable' );
	}

	function execute( $query ) {
		$out = $this->getOutput();
		$this->setHeaders();

		$tableName = $query;
		if ( $tableName == '' ) {
			$pageTitle = wfMessage( 'viewtable' )->parse();
			$out->setPageTitle( $pageTitle );
			$out->addHTML( $this->displayListOfTables() );
			return;
		}

		$pageTitle = wfMessage( 'cargo-viewtable-viewtable', $tableName )->parse();
		$out->setPageTitle( $pageTitle );

		$cdb = CargoUtils::getDB();

		// First, display a count.
		try {
			$res = $cdb->select( $tableName, 'COUNT(*)' );
		} catch ( Exception $e ) {
			$out->addHTML( Html::element( 'div', array( 'class' => 'error' ), wfMessage( 'cargo-viewtable-tablenotfound', $tableName )->parse() ) . "\n" );
			return;
		}
		$row = $cdb->fetchRow( $res );
		$out->addWikiText( wfMessage( 'cargo-viewtable-totalrows', "'''" . $row[0] . "'''" ) . "\n" );

		$sqlQuery = new CargoSQLQuery();
		$sqlQuery->mTableNames = array( $tableName );

		$tableSchemas = CargoUtils::getTableSchemas( array( $tableName ) );
		$sqlQuery->mTableSchemas = $tableSchemas;

		$aliasedFieldNames = array( wfMessage( 'nstab-main' )->parse() => '_pageName' );
		foreach( $tableSchemas[$tableName] as $fieldName => $fieldDescription ) {
			// Skip "hidden" fields.
			if ( array_key_exists( 'hidden', $fieldDescription ) ) {
				continue;
			}

			$fieldAlias = str_replace( '_', ' ', $fieldName );
			// Special handling for URLs, to avoid them
			// overwhelming the page.
			if ( $fieldDescription['type'] == 'URL' ) {
				// Thankfully, there's a message in core
				// MediaWiki that seems to just be "URL".
				$fieldName = "CONCAT('[', $fieldName, ' " . wfMessage( 'version-entrypoints-header-url' )->parse() . "]')";
			}

			if ( array_key_exists( 'isList', $fieldDescription ) ) {
				$aliasedFieldNames[$fieldAlias] = $fieldName . '__full';
			} elseif ( $fieldDescription['type'] == 'Coordinates' ) {
				$aliasedFieldNames[$fieldAlias] = $fieldName . '__full';
			} else {
				$aliasedFieldNames[$fieldAlias] = $fieldName;
			}
		}

		$sqlQuery->mAliasedFieldNames = $aliasedFieldNames;
		$sqlQuery->setDescriptionsForFields();
		$sqlQuery->handleDateFields();
		$sqlQuery->setOrderBy();
		$sqlQuery->mQueryLimit = 100;

		$queryResults = $sqlQuery->run();

		$queryDisplayer = CargoQueryDisplayer::newFromSQLQuery( $sqlQuery );
		$formattedQueryResults = $queryDisplayer->getFormattedQueryResults( $queryResults );

		$displayParams = array();

		$tableFormat = new CargoTableFormat( $this->getOutput() );
		$text = $tableFormat->display( $queryResults, $formattedQueryResults, $sqlQuery->mFieldDescriptions, $displayParams );

		// If there are (seemingly) more results than what we showed,
		// show a "View more" link that links to Special:ViewData.
		if ( count( $queryResults ) == $sqlQuery->mQueryLimit ) {
			$fieldsStr = '';
			foreach ( $aliasedFieldNames as $alias => $fieldName ) {
				$fieldsStr .= "$fieldName=$alias,";
			}
			// Remove the comma at the end.
			$fieldsStr = trim( $fieldsStr, ',' );
			$text .= $queryDisplayer->viewMoreResultsLink();
		}

		$out->addHTML( $text );
	}

	/**
	 * Returns HTML for a bulleted list of Cargo tables, with a link to
	 * the Special:ViewTable page for each one.
	 */
	function displayListOfTables() {
		$tableNames = CargoUtils::getTables();
		$templatesThatDeclareTables = self::getAllPageProps( 'CargoTableName' );

		$viewTablePage = Title::makeTitleSafe( NS_SPECIAL, 'ViewTable' );
		$viewTableText = $viewTablePage->getFullURL();
		$text = Html::element( 'p', null, wfMessage( 'cargo-viewtable-tablelist' )->parse() ) . "\n";
		$text .= "<ul>\n";
		foreach ( $tableNames as $tableName ) {
			$tableLink = Html::element( 'a', array( 'href' => "$viewTableText/$tableName", 'style' => 'font-weight: bold;' ), $tableName );
			$templatesForThisTable = $templatesThatDeclareTables[$tableName];
			if ( count( $templatesForThisTable ) == 0 ) {
				$tableLink .= ' (' . wfMessage( 'cargo-viewtable-notdeclared' )->text() . ')';
			} else {
				$templateLinks = array();
				foreach( $templatesForThisTable as $templateID ) {
					$templateTitle = Title::newFromID( $templateID );
					$templateLinks[] = Linker::link( $templateTitle );
				}
				$tableLink .= ' (' . implode( $templateLinks ) . ')';
			}
			$text .= Html::rawElement( 'li', null, $tableLink );
		}
		$text .= "</ul>\n";
		return $text;
	}

	/**
	 * Similar to CargoUtils::getPageProp().
	 */
	 public static function getAllPageProps( $pageProp ) {
		$dbr = wfGetDB( DB_SLAVE );
		$res = $dbr->select( 'page_props',
			array(
				'pp_page',
				'pp_value'
			),
			array(
				'pp_propname' => $pageProp
			)
		);

		$pagesPerValue = array();
		while ( $row = $dbr->fetchRow( $res ) ) {
			$pageID = $row['pp_page'];
			$pageValue = $row['pp_value'];
			if ( array_key_exists( $pageValue, $pagesPerValue ) ) {
				$pagesPerValue[$pageValue][] = $pageID;
			} else {
				$pagesPerValue[$pageValue] = array( $pageID );
			}
		}

		return $pagesPerValue;
	}

}
