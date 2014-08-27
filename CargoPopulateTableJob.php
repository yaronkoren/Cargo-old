<?php

/**
 * Background job to populate the database table for one template using the
 * data from the call(s) to that template in one page.
 *
 * @author Yaron Koren
 */
class CargoPopulateTableJob extends Job {
	function __construct( $title, $params = '', $id = 0 ) {
		parent::__construct( 'cargoPopulateTable', $title, $params, $id );
	}

	/**
	 * Run a CargoPopulateTable job.
	 *
	 * @return boolean success
	 */
	function run() {
		wfProfileIn( __METHOD__ );

		if ( is_null( $this->title ) ) {
			$this->error = "cargoPopulateTable: Invalid title";
			wfProfileOut( __METHOD__ );
			return false;
		}

		// All we need to do here is set some global variables based
		// on the parameters of this job, then parse the page -
		// the #cargo_store function will take care of the rest.
		CargoStore::$settings['origin'] = 'template';
		CargoStore::$settings['templateID'] = $this->params['templateID'];
		CargoStore::$settings['dbTableName'] = $this->params['dbTableName'];

		// @TODO - is there a "cleaner" way to get a page to be parsed?
		global $wgParser;
		$article = new Article( $this->title );
		$wgParser->parse( $article->getContent(), $this->title, new ParserOptions() );

		wfProfileOut( __METHOD__ );
		return true;
	}
}
