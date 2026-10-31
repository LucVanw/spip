<?php

/**
 * Formulaire d'activation / désactivation de la double authentification d'un auteur
 *
 * Utilisable uniquement par l'auteur lui-même, depuis sa fiche dans l'espace privé.
 *
 * @package SPIP\Twofa\Formulaires
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Chargement du formulaire
 *
 * Si la 2FA n'est pas active, un secret candidat est généré (ou repris)
 * et affiché avec son URI otpauth:// pour l'application d'authentification.
 *
 * @param int $id_auteur
 * @return array|false
 *     Environnement du formulaire
 */
function formulaires_configurer_twofa_charger_dist($id_auteur) {
	$id_auteur = (int) $id_auteur;
	include_spip('inc/autoriser');
	if (!autoriser('configurertwofa', 'auteur', $id_auteur)) {
		return false;
	}
	include_spip('inc/twofa');
	if (!$auteur = twofa_lire_auteur($id_auteur)) {
		return false;
	}

	$valeurs = [
		'code' => '',
		'_actif' => twofa_est_actif($auteur) ? ' ' : '',
	];
	if ($valeurs['_actif']) {
		$valeurs['_nb_codes_secours'] = twofa_nb_codes_secours($auteur);
	} else {
		$secret = twofa_dechiffrer_secret((string) $auteur['twofa_secret']);
		$secret_base32 = $secret ? twofa_base32_encoder($secret) : twofa_preparer_activation($id_auteur);
		$valeurs['_secret'] = trim(chunk_split($secret_base32, 4, ' '));
		$valeurs['_otpauth'] = twofa_uri_otpauth($auteur, $secret_base32);
	}

	return $valeurs;
}

/**
 * Vérifications du formulaire
 *
 * - 2FA inactive : le code doit correspondre au secret candidat (preuve que l'application est bien configurée) ;
 * - 2FA active : toute action demande un code valide (TOTP ou code de secours).
 *
 * @param int $id_auteur
 * @return array
 *     Erreurs du formulaire
 */
function formulaires_configurer_twofa_verifier_dist($id_auteur) {
	include_spip('inc/twofa');
	$auteur = twofa_lire_auteur((int) $id_auteur);
	$action = (string) Spip::input('twofa_action');
	$code = trim((string) Spip::input('code'));

	if (!twofa_est_actif($auteur)) {
		if ($action === 'regenerer') {
			return [];
		}
		if ($code === '') {
			return ['code' => _T('info_obligatoire')];
		}
		$secret = twofa_dechiffrer_secret((string) $auteur['twofa_secret']);
		if (!$secret || twofa_verifier_totp($secret, $code) === null) {
			return ['code' => _T('twofa:erreur_code_activation')];
		}
		return [];
	}

	if (!in_array($action, ['codes', 'desactiver'], true)) {
		return ['message_erreur' => _T('twofa:erreur_action')];
	}
	if ($code === '') {
		return ['code' => _T('info_obligatoire')];
	}
	if (!twofa_valider_code($auteur, $code)) {
		return ['code' => _T('twofa:erreur_code')];
	}

	return [];
}

/**
 * Traitements du formulaire
 *
 * @param int $id_auteur
 * @return array
 *     Retours des traitements
 */
function formulaires_configurer_twofa_traiter_dist($id_auteur) {
	$id_auteur = (int) $id_auteur;
	include_spip('inc/twofa');
	$auteur = twofa_lire_auteur($id_auteur);
	$action = (string) Spip::input('twofa_action');
	$res = ['editable' => true];

	if (!twofa_est_actif($auteur)) {
		if ($action === 'regenerer') {
			twofa_preparer_activation($id_auteur);
		} else {
			$secret = twofa_dechiffrer_secret((string) $auteur['twofa_secret']);
			$pas = twofa_verifier_totp($secret, (string) Spip::input('code')) ?? twofa_pas();
			$codes = twofa_activer($id_auteur, $pas);
			$res['message_ok'] = _T('twofa:message_active') . twofa_html_codes_secours($codes);
		}
	} elseif ($action === 'desactiver') {
		twofa_desactiver($id_auteur);
		$res['message_ok'] = _T('twofa:message_desactive');
	} elseif ($action === 'codes') {
		$codes = twofa_generer_codes_secours($id_auteur);
		$res['message_ok'] = _T('twofa:message_codes_regeneres') . twofa_html_codes_secours($codes);
	}

	// ne pas réafficher le code saisi
	Spip::setInput('code', '');

	return $res;
}

/**
 * Liste HTML des codes de secours, à afficher une seule fois
 *
 * @param string[] $codes
 */
function twofa_html_codes_secours(array $codes): string {
	$items = '';
	foreach ($codes as $code) {
		$items .= '<li><code>' . $code . '</code></li>';
	}

	return '<p>' . _T('twofa:explication_codes_secours') . '</p><ul class="twofa_codes_secours">' . $items . '</ul>';
}
