<?php

/**
 * Formulaire de configuration de l'envoi de codes par SMS (API OVHcloud)
 *
 * Réservé aux webmestres : clés API, compte SMS, SMS de test, commande de crédits.
 *
 * @package SPIP\Twofa\Formulaires
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Quantités de crédits SMS proposées à la commande
 *
 * @return int[]
 */
function twofa_quantites_credits(): array {
	return [100, 200, 500, 1000, 5000];
}

/**
 * Chargement du formulaire
 *
 * @return array|false
 *     Environnement du formulaire
 */
function formulaires_configurer_twofa_sms_charger_dist() {
	include_spip('inc/autoriser');
	if (!autoriser('webmestre')) {
		return false;
	}
	include_spip('inc/twofa_ovh');
	$config = twofa_ovh_config();

	$droits = [];
	foreach (twofa_ovh_droits() as $methode => $chemins) {
		foreach ($chemins as $chemin) {
			$droits[] = ['methode' => $methode, 'chemin' => $chemin];
		}
	}

	$valeurs = [
		'application_key' => $config['application_key'],
		'application_secret' => '',
		'consumer_key' => '',
		'service' => $config['service'],
		'sender' => $config['sender'],
		'telephone_test' => '',
		'quantite' => '100',
		'_constantes' => $config['constantes'] ? ' ' : '',
		'_configure' => twofa_ovh_cles_presentes($config) ? ' ' : '',
		'_url_creer_cle' => twofa_ovh_url_creer_cle(),
		'_droits' => $droits,
		'_quantites' => twofa_quantites_credits(),
		'_services' => [],
	];

	if ($valeurs['_configure']) {
		$services = twofa_ovh_lister_services($config);
		if (is_string($services)) {
			$valeurs['_erreur_api'] = $services;
		} else {
			$valeurs['_services'] = $services;
			if ($config['service'] !== '') {
				$credits = twofa_ovh_credits();
				$valeurs['_credits'] = is_string($credits) ? '?' : (string) $credits;
				$valeurs['_url_manager'] = 'https://www.ovh.com/manager/#/telecom/sms/' . rawurlencode($config['service']);
			}
		}
	}

	return $valeurs;
}

/**
 * Vérifications du formulaire
 *
 * À l'enregistrement, les clés sont testées auprès d'OVHcloud.
 *
 * @return array
 *     Erreurs du formulaire
 */
function formulaires_configurer_twofa_sms_verifier_dist() {
	include_spip('inc/twofa_ovh');
	include_spip('inc/twofa');
	$action = (string) _request('twofa_action');
	$config = twofa_ovh_config();

	switch ($action) {
		case 'enregistrer':
			if ($config['constantes']) {
				return ['message_erreur' => _T('twofa:erreur_config_constantes')];
			}
			$erreurs = [];
			$candidate = twofa_config_sms_postee($config);
			foreach (['application_key', 'application_secret', 'consumer_key'] as $champ) {
				if ($candidate[$champ] === '') {
					$erreurs[$champ] = _T('info_obligatoire');
				}
			}
			if ($candidate['sender'] !== '' && !preg_match('/^[A-Za-z0-9 ]{1,11}$/', $candidate['sender'])) {
				$erreurs['sender'] = _T('twofa:erreur_sender');
			}
			if ($erreurs) {
				return $erreurs;
			}
			$services = twofa_ovh_lister_services($candidate);
			if (is_string($services)) {
				return ['message_erreur' => _T('twofa:erreur_cles_refusees', ['erreur' => $services])];
			}
			if (!$services) {
				return ['message_erreur' => _T('twofa:erreur_aucun_compte_sms')];
			}
			return [];

		case 'tester':
			if (!twofa_sms_disponible()) {
				return ['message_erreur' => _T('twofa:erreur_sms_indisponible')];
			}
			if (!twofa_normaliser_telephone((string) _request('telephone_test'))) {
				return ['telephone_test' => _T('twofa:erreur_telephone')];
			}
			return [];

		case 'commander':
			if (!twofa_sms_disponible()) {
				return ['message_erreur' => _T('twofa:erreur_sms_indisponible')];
			}
			if (!in_array((int) _request('quantite'), twofa_quantites_credits(), true)) {
				return ['quantite' => _T('twofa:erreur_quantite')];
			}
			return [];

		case 'effacer':
			return $config['constantes'] ? ['message_erreur' => _T('twofa:erreur_config_constantes')] : [];
	}

	return ['message_erreur' => _T('twofa:erreur_action')];
}

/**
 * Traitements du formulaire
 *
 * @return array
 *     Retours des traitements
 */
function formulaires_configurer_twofa_sms_traiter_dist() {
	include_spip('inc/twofa_ovh');
	include_spip('inc/twofa');
	$action = (string) _request('twofa_action');
	$res = ['editable' => true];

	switch ($action) {
		case 'enregistrer':
			$config = twofa_config_sms_postee(twofa_ovh_config());
			$services = twofa_ovh_lister_services($config);
			if (!in_array($config['service'], $services, true)) {
				$config['service'] = reset($services);
			}
			twofa_ovh_ecrire_config($config);
			$res['message_ok'] = _T('twofa:message_config_enregistree', ['service' => $config['service']]);
			break;

		case 'tester':
			$telephone = twofa_normaliser_telephone((string) _request('telephone_test'));
			$erreur = twofa_ovh_envoyer_sms($telephone, _T('twofa:sms_message_test'));
			$res[$erreur ? 'message_erreur' : 'message_ok'] = $erreur
				? _T('twofa:erreur_sms_test', ['erreur' => $erreur])
				: _T('twofa:message_sms_test', ['telephone' => twofa_masquer_telephone($telephone)]);
			break;

		case 'commander':
			$commande = twofa_ovh_commander_credits((int) _request('quantite'));
			if (is_string($commande)) {
				$res['message_erreur'] = _T('twofa:erreur_commande', ['erreur' => $commande]);
			} else {
				include_spip('inc/filtres');
				$res['message_ok'] = _T('twofa:message_commande', [
					'numero' => $commande['orderId'] ?? '?',
					'prix' => $commande['prices']['withTax']['text'] ?? '?',
				]);
				if (!empty($commande['url'])) {
					$res['message_ok'] .= ' <a class="btn" href="' . attribut_url($commande['url']) . '" target="_blank" rel="noopener">'
						. _T('twofa:bouton_payer_commande') . '</a>';
				}
			}
			break;

		case 'effacer':
			twofa_ovh_effacer_config();
			$res['message_ok'] = _T('twofa:message_config_effacee');
			break;
	}

	// ne jamais réafficher les secrets saisis
	set_request('application_secret', '');
	set_request('consumer_key', '');

	return $res;
}

/**
 * Configuration saisie dans le formulaire, secrets vides = secrets déjà enregistrés
 */
function twofa_config_sms_postee(array $config): array {
	foreach (['application_key', 'application_secret', 'consumer_key', 'service', 'sender'] as $champ) {
		$valeur = trim((string) _request($champ));
		if ($valeur !== '' || !in_array($champ, ['application_secret', 'consumer_key'], true)) {
			$config[$champ] = $valeur;
		}
	}

	return $config;
}
