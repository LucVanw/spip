<?php

/**
 * Options du plugin Double authentification
 *
 * @package SPIP\Twofa\Options
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Désactiver l'authentification HTTP (PHP_AUTH_USER / PHP_AUTH_PW)
 *
 * Elle ne permet pas de saisir un second facteur, et verifier_visiteur()
 * identifie le visiteur de l'espace public sans passer par autoriser('loger') :
 * la laisser active permettrait de contourner la 2FA avec le seul mot de passe.
 *
 * Les options des plugins sont chargées après mes_options.php : ce réglage l'emporte.
 */
$GLOBALS['ignore_auth_http'] = true;

/**
 * Authentification de l'espace privé
 *
 * Surcharge de inc_auth_dist() : quand autoriser('loger') refuse l'auteur
 * (second facteur non validé), auth_init_droits() renvoie false, que
 * ecrire/backoffice.php traiterait comme un succès. On renvoie alors
 * vers le formulaire de login.
 *
 * @return array|int|string
 */
function inc_auth() {
	// charger_fonction() n'inclut pas inc/auth.php quand inc_auth() existe déjà
	include_spip('inc/auth');
	$res = inc_auth_dist();
	if ($res === false) {
		$GLOBALS['visiteur_session'] = [];
		return auth_a_loger();
	}

	return $res;
}
