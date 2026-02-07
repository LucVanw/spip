<?php

/**
 * Répertoires physiques de l'installation SPIP.
 */
return [
	/**
	 * Configuration permanente et inaccessible de la distribution et du ou des sites (clés, accès bdd, ...)
	 */
	'etc' => 'config/',
	/**
	 * Fond documentaire permanent et accessible d'un site (logo, document, ...)
	 */
	'doc' => 'IMG/',
	/**
	 * Fichiers temporaires et inaccessibles d'une instance (cache, session, log, ...)
	 */
	'tmp' => 'tmp/',
	/**
	 * Fichiers temporaires et accessibles d'un site (assets compressés, minifiés, images générés, ...)
	 */
	'var' => 'local/',
	/**
	 * Noyau historique de SPIP
	 */
	'core' => 'ecrire/',
	/**
	 * Plugins SPIP fournis avec une distribution, activés automatiquement
	 */
	'extensions' => 'plugins-dist/',
	/**
	 * Plugins SPIP installables pour l'instance
	 */
	'plugins' => 'plugins/',
	/**
	 * Plugins SPIP installables pour un site mutualisé
	 * Cf. _DIR_PLUGINS_SUPPL
	 */
	'extra_plugins' => '',
	/**
	 * Jeu de Squelette SPIP par défaut de la distribution
	 */
	'template' => 'squelettes-dist/',
	/**
	 * Personalisaation de la distribution
	 */
	'custom' => 'squelettes/',
	/**
	 * Personalisaation d'un site mutualisé
	 * Cf. $GLOBALS['dossier_squelettes'] = 'path1:path2:...:pathN'
	 */
	'extra_custom' => '',
	/**
	 * Jeu de Squelette de l'espace privé SPIP par défaut de la distribution
	 */
	'private_template' => 'prive/',
	/**
	 * Répertoire des caches.
	 */
	'cache' => '%spip.dirs.tmp%/cache/',
	/**
	 * Répertoire des log.
	 */
	'log' => '%spip.dirs.tmp%/log/',
	/**
	 * Répertoire des sessions.
	 */
	'sessions' => '%spip.dirs.tmp%/sessions/',
];
