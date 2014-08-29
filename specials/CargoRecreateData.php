<?php
/**
 * Displays an interface to let users recreate data via the Cargo
 * extension.
 *
 * @author Yaron Koren
 * @ingroup Cargo
 */

class CargoRecreateData extends IncludableSpecialPage {
	var $mTableName;

	function __construct( $tableName ) {
		parent::__construct( 'RecreateData', 'recreatedata' );
		$this->mTableName = $tableName;
	}

	function execute( $templateTitle ) {
		$out = $this->getOutput();

		if ( ! $this->getUser()->isAllowed( 'recreatedata' ) ) {
			$out->permissionRequired( 'recreatedata' );
			return;
		}

		$this->setHeaders();

		if ( !CargoUtils::tableExists( $this->mTableName ) ) {
			$out->setPageTitle( wfMessage( 'cargo-createdatatable' )->parse() );
		}

		if ( empty( $templateTitle ) ) {
			// No template.
			// TODO - show an error message.
			return true;
		}

		$formSubmitted = $this->getRequest()->getText( 'submitted' ) == 'yes';
		if ( $formSubmitted ) {
			// Recreate the data!
			$this->recreateData( $templateTitle );
			$this->getOutput()->redirect( $templateTitle->getFullURL() );
			return true;
		}

		// Simple form.
		// Add in a little bit of JS to make sure that the button
		// isn't accidentally pressed twice.
		$text = '<form method="post" onSubmit="submitButton.disabled = true; return true;">';
		$text .= Html::element( 'p', null, wfMessage( 'cargo-recreatedata-desc' )->parse() );
		$text .= Html::hidden( 'action', 'recreatedata' ) . "\n";
		$text .= Html::hidden( 'submitted', 'yes' ) . "\n";

		$text .= Html::input( 'submitButton', wfMessage( 'ok' )->parse(), 'submit' );
		$text .= "\n</form>";
		$this->getOutput()->addHTML( $text );
		return true;
	}

	/**
	 * Recreates the data.
	 */
	function recreateData( $templateTitle ) {
		$templatePageID = $templateTitle->getArticleID();
		$result = CargoDeclare::recreateDBTablesForTemplate( $templatePageID );
		if ( !$result ) {
			return;
		}
		// Now create a job, CargoPopulateTable, for each page
		// that calls this template.
		$jobParams = array(
			'templateID' => $templatePageID,
			'dbTableName' => $this->mTableName
		);

		// We need to break this up into batches, to avoid running out
		// of memory for large page sets.
		// @TODO For *really* large page sets, it might make sense
		// to create a job for each batch.
		$offset = 0;
		do {
			$jobs = array();
			$titlesWithThisTemplate = $templateTitle->getTemplateLinksTo( array( 'LIMIT' => 500, 'OFFSET' => $offset ) );
			foreach ( $titlesWithThisTemplate as $titleWithThisTemplate ) {
				$jobs[] = new CargoPopulateTableJob( $titleWithThisTemplate, $jobParams );
			}
			Job::batchInsert( $jobs );
			$offset += 500;
		} while ( count( $titlesWithThisTemplate ) >= 500 );
	}

	/**
	 * Don't list this in Special:SpecialPages.
	 */
	function isListed() {
		return false;
	}
}
