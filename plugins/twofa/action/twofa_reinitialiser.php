<?php

/**
 * Action de réinitialisation de la double authentification d'un auteur
 *
 * Utilisée par un webmestre quand un auteur a perdu son application
 * d'authentification et ses codes de secours.
 *
 * @package SPIP\Twofa\Actions
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Désactiver la 2FA d'un auteur
 *
 * @param int|null $arg Identifiant de l'auteur (sinon pris dans l'action sécurisée)
 */
function action_twofa_reinitialiser_dist($arg = null) {
	if ($arg === null) {
		$securiser_action = charger_fonction('securiser_action', 'inc');
		$arg = $securiser_action();
	}

	if (
		($id_auteur = (int) $arg)
		&& autoriser('reinitialisertwofa', 'auteur', $id_auteur)
	) {
		include_spip('inc/twofa');
		twofa_desactiver($id_auteur);
		spip_log('2FA de l\'auteur ' . $id_auteur . ' réinitialisée par l\'auteur ' . $GLOBALS['visiteur_session']['id_auteur'], 'twofa.' . _LOG_INFO_IMPORTANTE);
	}
}
