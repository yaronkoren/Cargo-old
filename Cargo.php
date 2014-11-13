<?php
/**
 * Initialization file for Cargo.
 *
 * @ingroup Cargo
 * @author Yaron Koren
 */

if ( !defined( 'MEDIAWIKI' ) ) die();

define( 'CARGO_VERSION', '0.6' );

$wgExtensionCredits['parserhook'][] = array(
	'path' => __FILE__,
	'name'	=> 'Cargo',
	'version' => CARGO_VERSION,
	'author' => 'Yaron Koren',
	'url' => '',
	'descriptionmsg' => 'cargo-desc',
);

$dir = dirname( __FILE__ );

// Script path.
$cgScriptPath = $wgScriptPath . '/extensions/Cargo';

$wgJobClasses['cargoPopulateTable'] = 'CargoPopulateTableJob';
$wgJobClasses['cargoRecreateTables'] = 'CargoRecreateTablesJob';

$wgHooks['ParserFirstCallInit'][] = 'cargoRegisterParserFunctions';
$wgHooks['MakeGlobalVariablesScript'][] = 'CargoHooks::setGlobalJSVariables';
$wgHooks['PageContentSave'][] = 'CargoHooks::onPageContentSave';
$wgHooks['ApprovedRevsRevisionApproved'][] = 'CargoHooks::onARRevisionApproved';
$wgHooks['ApprovedRevsRevisionUnapproved'][] = 'CargoHooks::onARRevisionUnapproved';
$wgHooks['TitleMoveComplete'][] = 'CargoHooks::onTitleMoveComplete';
$wgHooks['ArticleDeleteComplete'][] = 'CargoHooks::onArticleDeleteComplete';
$wgHooks['LoadExtensionSchemaUpdates'][] = 'CargoHooks::describeDBSchema';
// 'SkinTemplateNavigation' replaced 'SkinTemplateTabs' in the Vector skin
$wgHooks['SkinTemplateTabs'][] = 'CargoRecreateDataAction::displayTab';
$wgHooks['SkinTemplateNavigation'][] = 'CargoRecreateDataAction::displayTab2';
$wgHooks['UnknownAction'][] = 'CargoRecreateDataAction::show';
$wgHooks['BaseTemplateToolbox'][] = 'CargoPageValuesAction::addLink';
$wgHooks['UnknownAction'][] = 'CargoPageValuesAction::show';

$wgMessagesDirs['Cargo'] = $dir . '/i18n';
$wgExtensionMessagesFiles['Cargo'] = $dir . '/Cargo.i18n.php';
$wgExtensionMessagesFiles['CargoMagic'] = $dir . '/Cargo.i18n.magic.php';

// API modules
$wgAPIModules['cargoquery'] = 'CargoQueryAPI';

// Register classes and special pages.
$wgAutoloadClasses['CargoHooks'] = $dir . '/Cargo.hooks.php';
$wgAutoloadClasses['CargoUtils'] = $dir . '/CargoUtils.php';
$wgAutoloadClasses['CargoDeclare'] = $dir . '/CargoDeclare.php';
$wgAutoloadClasses['CargoAttach'] = $dir . '/CargoAttach.php';
$wgAutoloadClasses['CargoStore'] = $dir . '/CargoStore.php';
$wgAutoloadClasses['CargoQuery'] = $dir . '/CargoQuery.php';
$wgAutoloadClasses['CargoCompoundQuery'] = $dir . '/CargoCompoundQuery.php';
$wgAutoloadClasses['CargoSQLQuery'] = $dir . '/CargoSQLQuery.php';
$wgAutoloadClasses['CargoRecurringEvent'] = $dir . '/CargoRecurringEvent.php';
$wgAutoloadClasses['CargoDisplayMap'] = $dir . '/CargoDisplayMap.php';
$wgAutoloadClasses['CargoPopulateTableJob'] = $dir . '/CargoPopulateTableJob.php';
$wgAutoloadClasses['CargoRecreateTablesJob'] = $dir . '/CargoRecreateTablesJob.php';
$wgAutoloadClasses['CargoRecreateDataAction'] = $dir . '/CargoRecreateDataAction.php';
$wgAutoloadClasses['CargoRecreateData'] = $dir . '/specials/CargoRecreateData.php';
$wgSpecialPages['ViewTable'] = 'CargoViewTable';
$wgAutoloadClasses['CargoViewTable'] = $dir . '/specials/CargoViewTable.php';
$wgSpecialPages['ViewData'] = 'CargoViewData';
$wgAutoloadClasses['CargoViewData'] = $dir . '/specials/CargoViewData.php';
$wgSpecialPages['CalendarData'] = 'CargoCalendarData';
$wgAutoloadClasses['CargoCalendarData'] = $dir . '/specials/CargoCalendarData.php';
$wgAutoloadClasses['CargoPageValuesAction'] = $dir . '/CargoPageValuesAction.php';
$wgSpecialPages['PageValues'] = 'CargoPageValues';
$wgAutoloadClasses['CargoPageValues'] = $dir . '/specials/CargoPageValues.php';
$wgAutoloadClasses['CargoQueryAPI'] = $dir . '/CargoQueryAPI.php';

// Display formats
$wgAutoloadClasses['CargoDisplayFormat'] = $dir . '/formats/CargoDisplayFormat.php';
$wgAutoloadClasses['CargoListFormat'] = $dir . '/formats/CargoListFormat.php';
$wgAutoloadClasses['CargoULFormat'] = $dir . '/formats/CargoULFormat.php';
$wgAutoloadClasses['CargoOLFormat'] = $dir . '/formats/CargoOLFormat.php';
$wgAutoloadClasses['CargoTemplateFormat'] = $dir . '/formats/CargoTemplateFormat.php';
$wgAutoloadClasses['CargoOutlineFormat'] = $dir . '/formats/CargoOutlineFormat.php';
$wgAutoloadClasses['CargoTreeFormat'] = $dir . '/formats/CargoTreeFormat.php';
$wgAutoloadClasses['CargoEmbeddedFormat'] = $dir . '/formats/CargoEmbeddedFormat.php';
$wgAutoloadClasses['CargoTableFormat'] = $dir . '/formats/CargoTableFormat.php';
$wgAutoloadClasses['CargoDynamicTableFormat'] = $dir . '/formats/CargoDynamicTableFormat.php';
$wgAutoloadClasses['CargoMapsFormat'] = $dir . '/formats/CargoMapsFormat.php';
$wgAutoloadClasses['CargoGoogleMapsFormat'] = $dir . '/formats/CargoGoogleMapsFormat.php';
$wgAutoloadClasses['CargoOpenLayersFormat'] = $dir . '/formats/CargoOpenLayersFormat.php';
$wgAutoloadClasses['CargoCalendarFormat'] = $dir . '/formats/CargoCalendarFormat.php';
$wgAutoloadClasses['CargoCategoryFormat'] = $dir . '/formats/CargoCategoryFormat.php';

// Drilldown
$wgAutoloadClasses['CargoAppliedFilter'] = $dir . '/drilldown/CargoAppliedFilter.php';
$wgAutoloadClasses['CargoFilter'] = $dir . '/drilldown/CargoFilter.php';
$wgAutoloadClasses['CargoFilterValue'] = $dir . '/drilldown/CargoFilterValue.php';
$wgAutoloadClasses['CargoDrilldownUtils'] = $dir . '/drilldown/CargoDrilldownUtils.php';
$wgAutoloadClasses['CargoDrilldown'] = $dir . '/drilldown/CargoSpecialDrilldown.php';
$wgSpecialPages['Drilldown'] = 'CargoDrilldown';

// User right for recreating data.
$wgAvailableRights[] = 'recreatedata';
$wgGroupPermissions['sysop']['recreatedata'] = true;

// Page properties
$wgPageProps['CargoTableName'] = "The name of the database table that holds this template's data";
$wgPageProps['CargoFields'] = 'The set of fields stored for this template';

// ResourceLoader modules
$wgResourceModules += array(
	'ext.cargo.main' => array(
		'styles' => 'Cargo.css',
		'localBasePath' => __DIR__,
		'remoteExtPath' => 'Cargo'
	),
	'ext.cargo.drilldown' => array(
		'styles' => array(
			'drilldown/resources/CargoDrilldown.css',
			'drilldown/resources/CargoJQueryUIOverrides.css',
		),
		'scripts' => array(
			'drilldown/resources/CargoDrilldown.js',
		),
		'dependencies' => array(
			'jquery.ui.autocomplete',
			'jquery.ui.button',
		),
		'position' => 'top',
		'localBasePath' => __DIR__,
		'remoteExtPath' => 'Cargo'
	),
	'ext.cargo.maps' => array(
		'scripts' => array(
			'libs/ext.cargo.maps.js',
			'libs/markerclusterer.js',
		),
		'position' => 'top',
		'localBasePath' => __DIR__,
		'remoteExtPath' => 'Cargo'
	),
	'ext.cargo.calendar' => array(
		'styles' => array(
			'libs/fullcalendar.css',
			'libs/ext.cargo.calendar.css',
		),
		'scripts' => array(
			'libs/moment.js',
			'libs/fullcalendar.js',
			'libs/ext.cargo.calendar.js',
		),
		'position' => 'top',
		'localBasePath' => __DIR__,
		'remoteExtPath' => 'Cargo'
	),
	'ext.cargo.datatables' => array(
		'styles' => array(
			'libs/DataTables/css/jquery.dataTables.css',
		),
		'scripts' => array(
			'libs/DataTables/js/jquery.dataTables.js',
			'libs/ext.cargo.datatables.js',
		),
		'position' => 'top',
		'localBasePath' => __DIR__,
		'remoteExtPath' => 'Cargo'
	),
);

function cargoRegisterParserFunctions( &$parser ) {
	$parser->setFunctionHook( 'cargo_declare', array( 'CargoDeclare', 'run' ) );
	$parser->setFunctionHook( 'cargo_attach', array( 'CargoAttach', 'run' ) );
	$parser->setFunctionHook( 'cargo_store', array( 'CargoStore', 'run' ) );
	$parser->setFunctionHook( 'cargo_query', array( 'CargoQuery', 'run' ) );
	$parser->setFunctionHook( 'cargo_compound_query', array( 'CargoCompoundQuery', 'run' ) );
	$parser->setFunctionHook( 'recurring_event', array( 'CargoRecurringEvent', 'run' ) );
	$parser->setFunctionHook( 'cargo_display_map', array( 'CargoDisplayMap', 'run' ) );
	return true;
}

$wgCargoDecimalMark = '.';
$wgCargoDigitGroupingCharacter = ',';
$wgCargoRecurringEventMaxInstances = 100;
$wgCargoDBtype = null;
$wgCargoDBserver = null;
$wgCargoDBname = null;
$wgCargoDBuser = null;
$wgCargoDBpassword = null;
$wgCargoDefaultQueryLimit = 100;
$wgCargoMaxQueryLimit = 5000;

$wgCargoMapClusteringMinimum = 80;

$wgCargoDrilldownUseTabs = false;
// Set these to a positive number for cloud-style display.
$wgCargoDrilldownSmallestFontSize = -1;
$wgCargoDrilldownLargestFontSize = -1;
$wgCargoDrilldownMinValuesForComboBox = 40;
$wgCargoDrilldownNumRangesForNumbers = 5;
