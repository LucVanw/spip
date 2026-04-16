<?php

/**
 * Paramètres par défaut du logger SPIP.
 *
 * Chaque clé de ce fichier devient un paramètre du conteneur préfixé
 * par "spip.logger." (cf. SpipLeague\Component\Kernel\ConfigLoader).
 *
 * ⚠ Ce fichier fait partie de la distribution SPIP et peut être
 * réécrit lors d'une mise à jour. Pour surcharger une valeur sur une
 * installation, utilisez `config/custom.php` avec la clé pleine :
 *
 *     return [
 *         'spip.logger.max_files' => 10,
 *     ];
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
];
