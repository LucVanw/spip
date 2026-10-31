<?php

/**
 * Formulaire d'activation / désactivation de la double authentification d'un auteur
 *
 * Utilisable uniquement par l'auteur lui-même, depuis sa fiche dans l'espace privé.
 * Deux méthodes : application d'authentification (TOTP) ou SMS (si l'API OVH est configurée).
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
	include_spip('inc/twofa_ovh');
	if (!$auteur = twofa_lire_auteur($id_auteur)) {
		return false;
	}

	$valeurs = [
		'code' => '',
		'telephone' => '',
		'code_sms' => '',
		'_actif' => twofa_est_actif($auteur) ? ' ' : '',
		'_sms_disponible' => twofa_sms_disponible() ? ' ' : '',
	];
	if ($valeurs['_actif']) {
		$valeurs['_methode'] = twofa_methode($auteur);
		$valeurs['_telephone'] = twofa_masquer_telephone((string) $auteur['twofa_telephone']);
		$valeurs['_nb_codes_secours'] = twofa_nb_codes_secours($auteur);
		$valeurs['_label_code'] = _T($valeurs['_methode'] === 'sms' ? 'twofa:label_code_confirmation_sms' : 'twofa:label_code_confirmation');
	} else {
		$valeurs['_label_code'] = _T('twofa:label_code_activation');
		$secret = twofa_dechiffrer_secret((string) $auteur['twofa_secret']);
		$secret_base32 = $secret ? twofa_base32_encoder($secret) : twofa_preparer_activation($id_auteur);
		$valeurs['_secret'] = trim(chunk_split($secret_base32, 4, ' '));
		$valeurs['_otpauth'] = twofa_uri_otpauth($auteur, $secret_base32);

		$enrolement = twofa_lire_json($auteur['twofa_enrolement']);
		if (!empty($enrolement['telephone'])) {
			$valeurs['telephone'] = $enrolement['telephone'];
			$valeurs['_sms_envoye'] = empty($enrolement['sms']) ? '' : ' ';
		}
	}

	return $valeurs;
}

/**
 * Vérifications du formulaire
 *
 * - 2FA inactive : le code doit correspondre au secret candidat ou au code SMS envoyé
 *   (preuve que l'application ou le téléphone fonctionne) ;
 * - 2FA active : toute action demande un code valide (application, SMS ou code de secours).
 *
 * @param int $id_auteur
 * @return array
 *     Erreurs du formulaire
 */
function formulaires_configurer_twofa_verifier_dist($id_auteur) {
	include_spip('inc/twofa');
	include_spip('inc/twofa_ovh');
	$auteur = twofa_lire_auteur((int) $id_auteur);
	$action = (string) _request('twofa_action');
	$code = trim((string) _request('code'));

	if (!twofa_est_actif($auteur)) {
		switch ($action) {
			case 'regenerer':
				return [];

			case 'envoyer_sms':
				if (!twofa_sms_disponible()) {
					return ['message_erreur' => _T('twofa:erreur_sms_indisponible')];
				}
				if (!twofa_normaliser_telephone((string) _request('telephone'))) {
					return ['telephone' => _T('twofa:erreur_telephone')];
				}
				return [];

			case 'activer_sms':
				$enrolement = twofa_lire_json($auteur['twofa_enrolement']);
				if (empty($enrolement['telephone'])) {
					return ['telephone' => _T('twofa:erreur_telephone')];
				}
				if (!twofa_sms_verifier_code($enrolement, (string) _request('code_sms'))) {
					twofa_compter_echec_enrolement((int) $id_auteur, $enrolement);
					return ['code_sms' => _T('twofa:erreur_code')];
				}
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

	if ($action === 'envoyer_sms' && twofa_methode($auteur) === 'sms') {
		return [];
	}
	if (!in_array($action, ['codes', 'desactiver'], true)) {
		return ['message_erreur' => _T('twofa:erreur_action')];
	}
	if ($code === '') {
		return ['code' => _T('info_obligatoire')];
	}
	$enrolement = twofa_lire_json($auteur['twofa_enrolement']);
	if (!twofa_valider_code($auteur, $code, $enrolement)) {
		if (twofa_methode($auteur) === 'sms') {
			twofa_compter_echec_enrolement((int) $id_auteur, $enrolement);
		}
		return ['code' => _T('twofa:erreur_code')];
	}
	// le code SMS éventuel est à usage unique
	twofa_ecrire_enrolement((int) $id_auteur, []);

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
	$action = (string) _request('twofa_action');
	$res = ['editable' => true];

	if (!twofa_est_actif($auteur)) {
		switch ($action) {
			case 'regenerer':
				twofa_preparer_activation($id_auteur);
				break;

			case 'envoyer_sms':
				$telephone = twofa_normaliser_telephone((string) _request('telephone'));
				$enrolement = twofa_lire_json($auteur['twofa_enrolement']);
				if (($enrolement['telephone'] ?? '') !== $telephone) {
					$enrolement = ['telephone' => $telephone];
				}
				$res = array_merge($res, twofa_envoyer_sms_enrolement($auteur, $telephone, $enrolement));
				break;

			case 'activer_sms':
				$enrolement = twofa_lire_json($auteur['twofa_enrolement']);
				$codes = twofa_activer_sms($id_auteur, $enrolement['telephone']);
				$res['message_ok'] = _T('twofa:message_active_sms') . twofa_html_codes_secours($codes);
				break;

			default:
				$secret = twofa_dechiffrer_secret((string) $auteur['twofa_secret']);
				$pas = twofa_verifier_totp($secret, (string) _request('code')) ?? twofa_pas();
				$codes = twofa_activer($id_auteur, $pas);
				$res['message_ok'] = _T('twofa:message_active') . twofa_html_codes_secours($codes);
		}
	} elseif ($action === 'envoyer_sms') {
		$enrolement = twofa_lire_json($auteur['twofa_enrolement']);
		$res = array_merge($res, twofa_envoyer_sms_enrolement($auteur, (string) $auteur['twofa_telephone'], $enrolement));
	} elseif ($action === 'desactiver') {
		twofa_desactiver($id_auteur);
		$res['message_ok'] = _T('twofa:message_desactive');
	} elseif ($action === 'codes') {
		$codes = twofa_generer_codes_secours($id_auteur);
		$res['message_ok'] = _T('twofa:message_codes_regeneres') . twofa_html_codes_secours($codes);
	}

	// ne pas réafficher les codes saisis
	set_request('code', '');
	set_request('code_sms', '');

	return $res;
}

/**
 * Envoyer un code par SMS hors login (enrôlement ou confirmation d'une action)
 *
 * @param array $enrolement État de l'envoi précédent
 * @return array message_ok ou message_erreur
 */
function twofa_envoyer_sms_enrolement(array $auteur, string $telephone, array $enrolement): array {
	// repartir de zéro après une heure (compteur d'envois)
	if (($enrolement['dernier_envoi'] ?? 0) < time() - 3600) {
		$enrolement = array_intersect_key($enrolement, ['telephone' => true]);
	}
	if ($erreur = twofa_sms_envoyer_code($auteur, $telephone, $enrolement)) {
		return ['message_erreur' => $erreur];
	}
	twofa_ecrire_enrolement((int) $auteur['id_auteur'], $enrolement);

	return ['message_ok' => _T('twofa:message_sms_envoye', ['telephone' => twofa_masquer_telephone($telephone)])];
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
