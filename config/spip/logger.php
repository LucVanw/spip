<?php

/**
 * Paramètres par défaut du logger SPIP.
 *
 * Chaque clé de ce fichier devient un paramètre du conteneur préfixé
 * par "spip.logger." (cf. SpipLeague\Component\Kernel\ConfigLoader).
 *
 * Ces paramètres alimentent ensuite le service `spip.logger_config`
 * (cf. SpipLeague\Component\Kernel\registerLoggerServices()).
 *
 * ⚠ Ce fichier fait partie de la distribution SPIP et peut être
 * réécrit lors d'une mise à jour. Pour surcharger une valeur sur une
 * installation, utilisez `config/custom.php` avec la clé pleine :
 *
 *     return [
 *         'spip.logger.max_files' => 10,
 *         'spip.logger.max_level' => 'debug',
 *     ];
 *
 * Remplace en SPIP 5.0 les constantes historiques `_MAX_LOG`,
 * `_LOG_FILTRE_GRAVITE`, `_LOG_FILELINE`, `_LOG_BRUT`, `_DIR_LOG`,
 * `_FILE_LOG` et `_FILE_LOG_SUFFIX`, qui ne sont plus lues.
 */
return [
	/**
	 * Rotation des journaux : nombre maximum de fichiers conservés
	 * par canal. `0` désactive complètement le log.
	 *
	 * Remplace la globale dépréciée `$nombre_de_logs`.
	 */
	'max_files' => 4,
	/**
	 * Rotation des journaux : taille maximale d'un fichier de log,
	 * exprimée en kilo-octets. `0` désactive l'écriture.
	 *
	 * Remplace la globale dépréciée `$taille_des_logs`.
	 */
	'max_size' => 100,
	/**
	 * Nombre maximum d'entrées de log écrites par hit HTTP (ou CLI).
	 *
	 * Remplace la constante `_MAX_LOG`.
	 */
	'max_log' => 100,
	/**
	 * Niveau minimum capturé.
	 *
	 * Accepte une chaîne PSR `LogLevel` (`'emergency'`, `'alert'`,
	 * `'critical'`, `'error'`, `'warning'`, `'notice'`, `'info'`,
	 * `'debug'`), ou un entier `Monolog\Level`. Une valeur inconnue
	 * retombe silencieusement sur `notice`.
	 *
	 * Remplace la constante `_LOG_FILTRE_GRAVITE`.
	 */
	'max_level' => 'notice',
	/**
	 * Active l'ajout du fichier/ligne d'appel dans les entrées de log
	 * (processeur BacktraceProcessor).
	 *
	 * Remplace la constante `_LOG_FILELINE`.
	 */
	'fileline' => false,
	/**
	 * Désactive l'échappement / la mise en forme "brute" des messages
	 * (processeur BrutProcessor).
	 *
	 * Remplace la constante `_LOG_BRUT`.
	 */
	'brut' => false,
	/**
	 * Chemin (relatif à `spip.dirs.project`) du fichier de log, avec
	 * un `%s` pour le nom du canal — résolu par `sprintf()` dans
	 * {@see SpipLeague\Component\Logger\Config::logFile()}.
	 *
	 * Par défaut dérivé de `spip.dirs.log` (lui-même dérivé de
	 * `spip.dirs.tmp`) pour conserver la cohérence avec les autres
	 * répertoires ; le `%%s` est échappé côté ParameterBag Symfony
	 * afin de ne pas être confondu avec un placeholder de paramètre.
	 *
	 * Remplace les constantes `_DIR_LOG`, `_FILE_LOG` et
	 * `_FILE_LOG_SUFFIX`.
	 */
	'log_path' => '%spip.dirs.log%%s.log',
];
