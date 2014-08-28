<?php

class CargoViewTable extends IncludableSpecialPage {

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct( 'ViewTable' );
	}

	function execute( $query ) {
		$out = $this->getOutput();
		$out->addModuleStyles( 'ext.Cargo' );
		$this->setHeaders();

		$tableName = $query;
		if ( $tableName == '' ) {
			$out->addHTML( "<div class=\"error\">A table name must be specified.</div>\n" );
			return;
		}

		$pageTitle = wfMessage( 'viewtable' )->parse() . ': ' . $tableName;
		$out->setPageTitle( $pageTitle );

		$cdb = CargoUtils::getDB();
		$tableNames = array( $tableName );

		// First, display a count.
		try {
			$res = $cdb->select( $tableNames, 'COUNT(*)' );
		} catch ( Exception $e ) {
			$out->addHTML( "<div class=\"error\">Table \"$tableName\" not found in Cargo database.</div>\n" );
			return;
		}
		$row = $cdb->fetchRow( $res );
		$out->addWikiText( "This table has '''" . $row[0] . "''' rows altogether.\n" );

		$sqlQuery = new CargoSQLQuery();
		$sqlQuery->mTableNames = $tableNames;

		$tableSchemas = CargoQuery::getTableSchemas( array( $tableName ) );
		$sqlQuery->mTableSchemas = $tableSchemas;

		$aliasedFieldNames = array( wfMessage( 'nstab-main' )->parse() => '_pageName' );
		foreach( $tableSchemas[$tableName] as $fieldName => $fieldDescription ) {
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
			} else {
				$aliasedFieldNames[$fieldAlias] = $fieldName;
			}
		}

		$sqlQuery->mAliasedFieldNames = $aliasedFieldNames;
		$sqlQuery->setDescriptionsForFields();
		$sqlQuery->mQueryLimit = 100;

		$queryResults = $sqlQuery->run();

		CargoQuery::formatQueryResults( $queryResults, $sqlQuery->mFieldDescriptions, null );

		$displayParams = array();
		$tableFormat = new CargoTableFormat();
		$text = $tableFormat->display( $queryResults, $sqlQuery->mFieldDescriptions, $displayParams );

		// If there are (seemingly) more results than what we showed,
		// show a "View more" link that links to Special:ViewData.
		if ( count( $queryResults ) == $sqlQuery->mQueryLimit ) {
			$fieldsStr = '';
			foreach ( $aliasedFieldNames as $alias => $fieldName ) {
				$fieldsStr .= "$fieldName=$alias,";
			}
			// Remove the comma at the end.
			$fieldsStr = trim( $fieldsStr, ',' );
			$text .= CargoQuery::viewMoreResultsLink( $tableName, $fieldsStr, null, null, null, $sqlQuery->mOrderBy, $sqlQuery->mQueryLimit, 'simpletable', $displayParams );
		}

		$out->addHTML( $text );
	}

}
