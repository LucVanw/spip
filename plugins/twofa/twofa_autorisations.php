<?php

/**
 * Autorisations du plugin Double authentification
 *
 * @package SPIP\Twofa\Autorisations
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Fonction d'appel pour le pipeline
 *
 * @pipeline autoriser
 */
function twofa_autoriser() {
}

/**
 * Autorisation d'ouvrir une session
 *
 * Surcharge de autoriser_loger_dist() : refuse l'ouverture d'une session
 * tant que le second facteur n'a pas été validé, pour les auteurs ayant la 2FA active.
 *
 * @see twofa_autoriser_connexion()
 *
 * @param string $faire Action demandée
 * @param string $type Type d'objet ou élément
 * @param int|string|null $id Identifiant
 * @param array $qui Description de l'auteur demandant l'autorisation
 * @param array $opt Options de cette autorisation
 * @return bool true s'il a le droit, false sinon
 */
function autoriser_loger(string $faire, string $type, $id, array $qui, array $opt): bool {
	if (!autoriser_loger_dist($faire, $type, $id, $qui, $opt)) {
		return false;
	}
	include_spip('inc/twofa');

	return twofa_autoriser_connexion($qui);
}

/**
 * Autorisation de gérer sa propre double authentification
 *
 * Uniquement l'auteur lui-même, connecté.
 *
 * @param string $faire Action demandée
 * @param string $type Type d'objet ou élément
 * @param int|string|null $id Identifiant
 * @param array $qui Description de l'auteur demandant l'autorisation
 * @param array $opt Options de cette autorisation
 * @return bool true s'il a le droit, false sinon
 */
function autoriser_auteur_configurertwofa_dist(string $faire, string $type, $id, array $qui, array $opt): bool {
	return (int) $id > 0 && (int) $id === (int) $qui['id_auteur'];
}

/**
 * Autorisation de réinitialiser la double authentification d'un autre auteur
 *
 * Réservé aux webmestres (cas du téléphone perdu sans code de secours).
 *
 * @param string $faire Action demandée
 * @param string $type Type d'objet ou élément
 * @param int|string|null $id Identifiant
 * @param array $qui Description de l'auteur demandant l'autorisation
 * @param array $opt Options de cette autorisation
 * @return bool true s'il a le droit, false sinon
 */
function autoriser_auteur_reinitialisertwofa_dist(string $faire, string $type, $id, array $qui, array $opt): bool {
	return (int) $id > 0
		&& (int) $id !== (int) $qui['id_auteur']
		&& autoriser('webmestre', '', 0, $qui);
}
