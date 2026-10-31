<?php

/**
 * Installation et désinstallation du plugin Double authentification
 *
 * @package SPIP\Twofa\Installation
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Installation / mise à jour des champs 2FA de spip_auteurs
 *
 * @param string $nom_meta_base_version
 * @param string $version_cible
 */
function twofa_upgrade($nom_meta_base_version, $version_cible) {
	$maj = [];
	$maj['create'] = [
		['maj_tables', ['spip_auteurs']],
	];

	include_spip('base/upgrade');
	maj_plugin($nom_meta_base_version, $version_cible, $maj);
}

/**
 * Désinstallation : suppression des champs 2FA
 *
 * @param string $nom_meta_base_version
 */
function twofa_vider_tables($nom_meta_base_version) {
	include_spip('inc/twofa');
	foreach (twofa_champs() as $champ) {
		sql_alter("TABLE spip_auteurs DROP $champ");
	}
	effacer_meta($nom_meta_base_version);
}
