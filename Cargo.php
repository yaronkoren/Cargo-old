<?php
/**
 * Initialization file for Cargo.
 *
 * @ingroup Cargo
 * @author Yaron Koren
 */

if ( !defined( 'MEDIAWIKI' ) ) die();

define( 'CARGO_VERSION', '0.1' );

$wgExtensionCredits['parserhook'][] = array(
	'path' => __FILE__,
	'name'	=> 'Cargo',
	'version' => CARGO_VERSION,
	'author' => 'Yaron Koren',
	'url' => '',
	'descriptionmsg' => 'cargo-desc',
);

$dir = dirname( __FILE__ );

$wgJobClasses['cargoPopulateTable'] = 'CargoPopulateTableJob';

$wgHooks['ParserFirstCallInit'][] = 'cargoRegisterParserFunctions';
$wgHooks['PageContentSave'][] = 'CargoHooks::onPageContentSave';
$wgHooks['TitleMoveComplete'][] = 'CargoHooks::onTitleMoveComplete';
$wgHooks['ArticleDeleteComplete'][] = 'CargoHooks::onArticleDeleteComplete';
$wgHooks['LoadExtensionSchemaUpdates'][] = 'CargoHooks::describeDBSchema';
// 'SkinTemplateNavigation' replaced 'SkinTemplateTabs' in the Vector skin
$wgHooks['SkinTemplateTabs'][] = 'CargoRecreateDataAction::displayTab';
$wgHooks['SkinTemplateNavigation'][] = 'CargoRecreateDataAction::displayTab2';
$wgHooks['UnknownAction'][] = 'CargoRecreateDataAction::show';

$wgMessagesDirs['Cargo'] = $dir . '/i18n';
$wgExtensionMessagesFiles['Cargo'] = $dir . '/Cargo.i18n.php';
$wgExtensionMessagesFiles['CargoMagic'] = $dir . '/Cargo.i18n.magic.php';

// API modules
$wgAPIModules['cargoquery'] = 'CargoQueryAPI';

// Register classes and special pages.
$wgAutoloadClasses['CargoHooks'] = $dir . '/Cargo.hooks.php';
$wgAutoloadClasses['CargoUtils'] = $dir . '/CargoUtils.php';
$wgAutoloadClasses['CargoDeclare'] = $dir . '/CargoDeclare.php';
$wgAutoloadClasses['CargoStore'] = $dir . '/CargoStore.php';
$wgAutoloadClasses['CargoQuery'] = $dir . '/CargoQuery.php';
$wgAutoloadClasses['CargoSQLQuery'] = $dir . '/CargoSQLQuery.php';
$wgAutoloadClasses['CargoRecurringEvent'] = $dir . '/CargoRecurringEvent.php';
$wgAutoloadClasses['CargoPopulateTableJob'] = $dir . '/CargoPopulateTableJob.php';
$wgAutoloadClasses['CargoRecreateDataAction'] = $dir . '/CargoRecreateDataAction.php';
$wgAutoloadClasses['CargoRecreateData'] = $dir . '/specials/CargoRecreateData.php';
$wgSpecialPages['ViewTable'] = 'CargoViewTable';
$wgAutoloadClasses['CargoViewTable'] = $dir . '/specials/CargoViewTable.php';
$wgSpecialPages['ViewData'] = 'CargoViewData';
$wgAutoloadClasses['CargoViewData'] = $dir . '/specials/CargoViewData.php';
$wgAutoloadClasses['CargoQueryAPI'] = $dir . '/CargoQueryAPI.php';

// Display formats
$wgAutoloadClasses['CargoDisplayFormat'] = $dir . '/formats/CargoDisplayFormat.php';
$wgAutoloadClasses['CargoListFormat'] = $dir . '/formats/CargoListFormat.php';
$wgAutoloadClasses['CargoULFormat'] = $dir . '/formats/CargoULFormat.php';
$wgAutoloadClasses['CargoOLFormat'] = $dir . '/formats/CargoOLFormat.php';
$wgAutoloadClasses['CargoTemplateFormat'] = $dir . '/formats/CargoTemplateFormat.php';
$wgAutoloadClasses['CargoTableFormat'] = $dir . '/formats/CargoTableFormat.php';

// User right for recreating data.
$wgAvailableRights[] = 'recreatedata';
$wgGroupPermissions['sysop']['recreatedata'] = true;

// Page properties
$wgPageProps['CargoTableName'] = "The name of the database table that holds this template's data";
$wgPageProps['CargoFields'] = 'The set of fields stored for this template';

// ResourceLoader modules
$wgResourceModules['ext.Cargo'] = array(
	'styles' => 'Cargo.css',
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'Cargo'
);

function cargoRegisterParserFunctions( &$parser ) {
	$parser->setFunctionHook( 'cargo_declare', array( 'CargoDeclare', 'run' ) );
	$parser->setFunctionHook( 'cargo_store', array( 'CargoStore', 'run' ) );
	$parser->setFunctionHook( 'cargo_query', array( 'CargoQuery', 'run' ) );
	$parser->setFunctionHook( 'recurring_event', array( 'CargoRecurringEvent', 'run' ) );
	return true;
}

$wgCargoRecurringEventMaxInstances = 100;
$wgCargoDBtype = null;
$wgCargoDBserver = null;
$wgCargoDBname = null;
$wgCargoDBuser = null;
$wgCargoDBpassword = null;
$wgCargoDefaultQueryLimit = 100;
$wgCargoMaxQueryLimit = 5000;
$wgCargoDisplayFormats = array(
	'list' => 'CargoListFormat',
	'ul' => 'CargoULFormat',
	'ol' => 'CargoOLFormat',
	'template' => 'CargoTemplateFormat',
	'simpletable' => 'CargoTableFormat',
);
