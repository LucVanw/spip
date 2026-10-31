<?php

/**
 * Déclarations SQL du plugin Double authentification
 *
 * @package SPIP\Twofa\Pipelines
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Ajouter les champs 2FA à la table spip_auteurs
 *
 * - twofa_secret : secret TOTP chiffré avec le secret du site
 * - twofa_actif : 'oui' quand l'auteur a validé l'enrôlement
 * - twofa_secours : hashes des codes de secours (JSON)
 * - twofa_dernier : dernier pas TOTP accepté (anti-rejeu)
 * - twofa_attente : connexion en attente du second facteur (JSON)
 *
 * @pipeline declarer_tables_objets_sql
 * @param array $tables
 * @return array
 */
function twofa_declarer_tables_objets_sql($tables) {
	$tables['spip_auteurs']['field']['twofa_secret'] = "text DEFAULT '' NOT NULL";
	$tables['spip_auteurs']['field']['twofa_actif'] = "varchar(3) DEFAULT '' NOT NULL";
	$tables['spip_auteurs']['field']['twofa_secours'] = "text DEFAULT '' NOT NULL";
	$tables['spip_auteurs']['field']['twofa_dernier'] = 'bigint(21) DEFAULT 0 NOT NULL';
	$tables['spip_auteurs']['field']['twofa_attente'] = "text DEFAULT '' NOT NULL";

	return $tables;
}
