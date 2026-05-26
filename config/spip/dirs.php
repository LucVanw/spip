<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
	$container->parameters()
		/**
		 * Fond documentaire permanent et accessible d'un site (logo, document, ...)
		 */
		->set('spip.dirs.doc', 'IMG/')
		/**
		 * Fichiers temporaires et inaccessibles d'une instance (cache, session, log, ...)
		 */
		->set('spip.dirs.tmp', 'tmp/')
		/**
		 * Fichiers temporaires et accessibles d'un site (assets compressés, minifiés, images générés, ...)
		 */
		->set('spip.dirs.var', 'local/')
		/**
		 * Noyau historique de SPIP
		 */
		->set('spip.dirs.core', 'ecrire/')
		/**
		 * Plugins SPIP fournis avec une distribution, activés automatiquement
		 */
		->set('spip.dirs.extensions', 'plugins-dist/')
		/**
		 * PPlugins SPIP installables pour l'instance
		 */
		->set('spip.dirs.plugins', 'plugins/')
		/**
		 * Plugins SPIP installables pour un site mutualisé
		 */
		->set('spip.dirs.extra_plugins', '')
		/**
		 * Jeu de Squelette SPIP par défaut de la distribution
		 */

		->set('spip.dirs.template', 'squelettes-dist/')
		/**
		 * Personalisation de la distribution
		 */
		->set('spip.dirs.custom', 'squelettes/')
		/**
		 * Personalisation des chemins des squelettes (actuellement inutilisé)
		 * 
		 * @todo Destiné à remplacer `$GLOBALS['dossier_squelettes'] = 'path1:path2:...:pathN'`
		 */
		->set('spip.dirs.extra_custom', '')
		/**
		 * Jeu de Squelette de l'espace privé SPIP par défaut de la distribution
		 */
		->set('spip.dirs.private_template', 'prive/')
		/**
		 * Répertoire des log.
		 */
		->set('spip.dirs.log', '%spip.dirs.tmp%log/')
		/**
		 * Répertoire des sessions.
		 */
		->set('spip.dirs.sessions', '%spip.dirs.tmp%sessions/');
};
