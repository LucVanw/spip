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
	 * Jeu de Squelette SPIP par défaut de la distribution
	 */
	'template' => 'squelettes-dist/',
	/**
	 * Personalisaation de la distribution (ou d'un site ?)
	 *
	 * @todo gérer $GLOBALS['dossier_squelettes']
	 */
	'custom' => 'squelettes/',
	/**
	 * Jeu de Squelette de l'espace privé SPIP par défaut de la distribution
	 */
	'private_template' => 'prive/',
];
