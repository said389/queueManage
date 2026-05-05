<?php

if ( !defined( 'ENTRY_POINT' ) ) {
    die('This is not an entry point. DefaultSettings' );
}

// This array contains editable settings by sysAdmin
$gvEditableConfs = array(
	// First and second entries need to be AdminCode and AdminPassword, always!
	new EditableConf( 'gvSysAdminCode', '0000', 'Code d\'accès administrateur' ),
	new EditableConf( 'gvSysAdminPassword', 'admin0', 'Mot de passe administrateur' ),
	new EditableConf( 'gvMinPasswordLength', 6, 'Longueur minimale du mot de passe' ),
	new EditableConf( 'gvSessionTimeout', 900, 'Durée de session (sec)' ),
	new EditableConf( 'gvTrashThreshold', 30, 'Seuil de suppression ticket (sec)' ),
	new EditableConf( 'gvPhoneCodeLength', 4, 'Longueur du code de vérification web (max 40)' ),
	new EditableConf( 'gvQueueLengthWebLimit', 10, 'Limite longueur file d\'attente pour réservation web' ),
	new EditableConf( 'gvQueueEtaWebLimit', 900, 'Limite temps d\'attente file d\'attente pour réservation web (sec)' ),
	new EditableConf( 'gvQueueLengthAppLimit', 5, 'Limite longueur file d\'attente pour réservation app' ),
	new EditableConf( 'gvQueueEtaAppLimit', 600, 'Limite temps d\'attente file d\'attente pour réservation app (sec)' ),
	new EditableConf( 'gvQrCodeMsg', 'Texte QrCode', 'Texte à côté du QrCode', 'textarea' ),
	new EditableConf( 'gvSpotTitle', 'Titre du spot', 'Titre du spot du ticket' ),
	new EditableConf( 'gvSpotBody', 'Texte du spot', 'Texte du spot du ticket', 'textarea' ),
	new EditableConf( 'gvCallOtherTdWhenEmpty', true, 'Opérateurs en mode occupé' ),
	new EditableConf( 'gvAllowPause', true, 'Activer le bouton pause pour les opérateurs' ),
);


// Default settings overridden by LocalSettings
$gvLangCode = 'it';
$gvTimeZone = 'Europe/Rome';
$gvDirectory = dirname( __DIR__ );
$gvPath = '/queueManage';
$gvServerName = 'localhost';
$gvProtocol = 'http://';
$gvPort = ''; // e.g. 8080, empty = 80 (http) or 143 (https)

$gvDbConfig = array();
$gvDbConfig['host'] = "localhost";
$gvDbConfig['database'] = "fastqueue";
$gvDbConfig['username'] = "root";
$gvDbConfig['password'] = "";
$gvDbConfig['port'] = "3307"; 
$gvJqueryUrl = 'http://code.jquery.com/jquery-2.1.1.min.js';

// Debug settings
$gvDebug['active'] = true;
$gvDebug['destinationHost'] = '127.0.0.1';
$gvDebug['destinationPort'] = '2015';
$gvDebug['disableSms'] = true;

// Office infos
$gvOfficeCode = 'FSQIT0000001';
$gvOfficeName = 'Ufficio Postale - Isernia centro';
$gvOfficeAddress = 'Via XXIV Maggio, 234';
$gvOfficeSecret = 'ImSecret';

// Set default values for editable settings
foreach( $gvEditableConfs as $conf ) {
	$conf->exportDefault();
}
