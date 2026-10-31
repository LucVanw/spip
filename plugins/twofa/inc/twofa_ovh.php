<?php

/**
 * Envoi des codes de double authentification par SMS via l'API OVHcloud
 *
 * La configuration se fait dans l'espace privé (Configuration > Double authentification),
 * stockée dans la meta `twofa_ovh` avec les secrets chiffrés par la clé du site.
 *
 * Elle peut aussi être imposée dans config/mes_options.php (prioritaire) :
 *
 * ```
 * define('_TWOFA_OVH_APPLICATION_KEY', '…');
 * define('_TWOFA_OVH_APPLICATION_SECRET', '…');
 * define('_TWOFA_OVH_CONSUMER_KEY', '…');
 * define('_TWOFA_OVH_SMS_SERVICE', 'sms-xx12345-1');
 * // optionnels
 * define('_TWOFA_OVH_ENDPOINT', 'ovh-eu'); // ovh-eu, ovh-ca ou ovh-us
 * define('_TWOFA_OVH_SMS_SENDER', 'MONSITE'); // expéditeur déclaré chez OVH, sinon numéro court
 * ```
 *
 * @package SPIP\Twofa\SMS
 */

use Spip\Chiffrer\Chiffrement;
use Spip\Chiffrer\SpipCles;

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Droits à donner à la clé API OVH : méthode => chemins
 */
function twofa_ovh_droits(): array {
	return [
		'GET' => ['/sms', '/sms/*', '/order/sms/*'],
		'POST' => ['/sms/*/jobs', '/order/sms/*/credits'],
	];
}

/**
 * Lien de création de la clé API chez OVHcloud, droits pré-remplis
 */
function twofa_ovh_url_creer_cle(): string {
	$hotes = [
		'ovh-eu' => 'https://eu.api.ovh.com',
		'ovh-ca' => 'https://ca.api.ovh.com',
		'ovh-us' => 'https://api.us.ovhcloud.com',
	];
	$endpoint = defined('_TWOFA_OVH_ENDPOINT') ? _TWOFA_OVH_ENDPOINT : 'ovh-eu';
	$params = [];
	foreach (twofa_ovh_droits() as $methode => $chemins) {
		foreach ($chemins as $chemin) {
			$params[] = $methode . '=' . $chemin;
		}
	}

	return ($hotes[$endpoint] ?? $hotes['ovh-eu']) . '/createToken/?' . implode('&', $params);
}

/**
 * Configuration de l'API : constantes de mes_options.php, sinon réglages de l'espace privé
 *
 * @return array{application_key: string, application_secret: string, consumer_key: string, service: string, sender: string, constantes: bool}
 */
function twofa_ovh_config(): array {
	if (defined('_TWOFA_OVH_APPLICATION_KEY')) {
		return [
			'application_key' => (string) _TWOFA_OVH_APPLICATION_KEY,
			'application_secret' => defined('_TWOFA_OVH_APPLICATION_SECRET') ? (string) _TWOFA_OVH_APPLICATION_SECRET : '',
			'consumer_key' => defined('_TWOFA_OVH_CONSUMER_KEY') ? (string) _TWOFA_OVH_CONSUMER_KEY : '',
			'service' => defined('_TWOFA_OVH_SMS_SERVICE') ? (string) _TWOFA_OVH_SMS_SERVICE : '',
			'sender' => defined('_TWOFA_OVH_SMS_SENDER') ? (string) _TWOFA_OVH_SMS_SENDER : '',
			'constantes' => true,
		];
	}

	$meta = json_decode((string) ($GLOBALS['meta']['twofa_ovh'] ?? ''), true) ?: [];
	$cle = SpipCles::secret_du_site();

	return [
		'application_key' => (string) ($meta['application_key'] ?? ''),
		'application_secret' => ($cle && !empty($meta['application_secret'])) ? (string) Chiffrement::dechiffrer($meta['application_secret'], $cle) : '',
		'consumer_key' => ($cle && !empty($meta['consumer_key'])) ? (string) Chiffrement::dechiffrer($meta['consumer_key'], $cle) : '',
		'service' => (string) ($meta['service'] ?? ''),
		'sender' => (string) ($meta['sender'] ?? ''),
		'constantes' => false,
	];
}

/**
 * Enregistrer la configuration de l'API (secrets chiffrés avec la clé du site)
 */
function twofa_ovh_ecrire_config(array $config): void {
	$cle = SpipCles::secret_du_site();
	if (!$cle) {
		throw new RuntimeException('twofa : secret du site indisponible, impossible de chiffrer');
	}
	include_spip('inc/meta');
	ecrire_meta('twofa_ovh', json_encode([
		'application_key' => $config['application_key'],
		'application_secret' => Chiffrement::chiffrer($config['application_secret'], $cle),
		'consumer_key' => Chiffrement::chiffrer($config['consumer_key'], $cle),
		'service' => $config['service'] ?? '',
		'sender' => $config['sender'] ?? '',
	]), 'non');
}

/**
 * Oublier la configuration de l'API
 */
function twofa_ovh_effacer_config(): void {
	include_spip('inc/meta');
	effacer_meta('twofa_ovh');
}

/**
 * Les clés de l'API sont-elles renseignées ?
 */
function twofa_ovh_cles_presentes(?array $config = null): bool {
	$config ??= twofa_ovh_config();

	return $config['application_key'] !== '' && $config['application_secret'] !== '' && $config['consumer_key'] !== '';
}

/**
 * L'envoi de SMS est-il configuré ?
 */
function twofa_sms_disponible(): bool {
	$config = twofa_ovh_config();

	return twofa_ovh_cles_presentes($config) && $config['service'] !== '';
}

/**
 * URL de base de l'API OVHcloud
 */
function twofa_ovh_endpoint(): string {
	$endpoints = [
		'ovh-eu' => 'https://eu.api.ovh.com/1.0',
		'ovh-ca' => 'https://ca.api.ovh.com/1.0',
		'ovh-us' => 'https://api.us.ovhcloud.com/1.0',
	];
	$endpoint = defined('_TWOFA_OVH_ENDPOINT') ? _TWOFA_OVH_ENDPOINT : 'ovh-eu';

	return $endpoints[$endpoint] ?? $endpoints['ovh-eu'];
}

/**
 * Heure du serveur de l'API, pour signer les requêtes malgré une horloge locale décalée
 */
function twofa_ovh_heure(): int {
	static $decalage = null;
	if ($decalage === null) {
		$decalage = 0;
		include_spip('inc/distant');
		$res = recuperer_url(twofa_ovh_endpoint() . '/auth/time', ['follow_location' => 0]);
		if ($res && $res['status'] === 200 && is_numeric(trim($res['page']))) {
			$decalage = (int) trim($res['page']) - time();
		}
	}

	return time() + $decalage;
}

/**
 * Appeler l'API OVHcloud avec une requête signée
 *
 * @param string $methode GET, POST, PUT ou DELETE
 * @param string $chemin Chemin de l'API, ex. `/sms/sms-xx12345-1/jobs`
 * @param array|null $corps Corps JSON
 * @param array|null $config Clés à utiliser (par défaut la configuration enregistrée)
 * @return array|string Réponse décodée, ou message d'erreur
 */
function twofa_ovh_appel(string $methode, string $chemin, ?array $corps = null, ?array $config = null) {
	include_spip('inc/distant');
	$config ??= twofa_ovh_config();
	$url = twofa_ovh_endpoint() . $chemin;
	$body = ($corps === null) ? '' : json_encode($corps, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	$heure = twofa_ovh_heure();
	$signature = '$1$' . sha1(implode('+', [
		$config['application_secret'],
		$config['consumer_key'],
		$methode,
		$url,
		$body,
		$heure,
	]));

	$options = [
		'methode' => $methode,
		'follow_location' => 0,
		'headers' => [
			'X-Ovh-Application' => $config['application_key'],
			'X-Ovh-Consumer' => $config['consumer_key'],
			'X-Ovh-Timestamp' => $heure,
			'X-Ovh-Signature' => $signature,
		],
	];
	if ($body !== '') {
		$options['datas'] = "Content-Type: application/json\n\n" . $body;
	}

	$res = recuperer_url($url, $options);
	if (!$res || !$res['status']) {
		return 'connexion impossible à ' . twofa_ovh_endpoint();
	}
	$reponse = json_decode((string) $res['page'], true);
	if ($res['status'] >= 300) {
		return 'HTTP ' . $res['status'] . ' : ' . ($reponse['message'] ?? substr((string) $res['page'], 0, 200));
	}

	return is_array($reponse) ? $reponse : [];
}

/**
 * Lister les comptes SMS accessibles avec ces clés
 *
 * @return string[]|string Noms des comptes SMS, ou message d'erreur
 */
function twofa_ovh_lister_services(?array $config = null) {
	return twofa_ovh_appel('GET', '/sms', null, $config);
}

/**
 * Crédits SMS restants
 *
 * @return float|string Crédits, ou message d'erreur
 */
function twofa_ovh_credits() {
	$config = twofa_ovh_config();
	$res = twofa_ovh_appel('GET', '/sms/' . rawurlencode($config['service']), null, $config);

	return is_string($res) ? $res : (float) ($res['creditsLeft'] ?? 0);
}

/**
 * Créer un bon de commande de crédits SMS (à payer ensuite sur OVHcloud)
 *
 * @return array|string Commande (orderId, url, prices), ou message d'erreur
 */
function twofa_ovh_commander_credits(int $quantite) {
	$config = twofa_ovh_config();
	$res = twofa_ovh_appel('POST', '/order/sms/' . rawurlencode($config['service']) . '/credits', ['quantity' => $quantite], $config);
	if (is_array($res)) {
		spip_log('bon de commande OVH ' . ($res['orderId'] ?? '?') . " créé pour $quantite crédits SMS", 'twofa.' . _LOG_INFO_IMPORTANTE);
	}

	return $res;
}

/**
 * Envoyer un SMS
 *
 * @param string $telephone Numéro au format international (+33…)
 * @return string|null Message d'erreur, null si envoyé
 */
function twofa_ovh_envoyer_sms(string $telephone, string $message): ?string {
	if (!twofa_sms_disponible()) {
		return 'envoi de SMS non configuré';
	}
	$config = twofa_ovh_config();

	$corps = [
		'message' => $message,
		'receivers' => [$telephone],
		'charset' => 'UTF-8',
		'noStopClause' => true,
		'priority' => 'high',
		'validityPeriod' => 15,
	];
	if ($config['sender'] !== '') {
		$corps['sender'] = $config['sender'];
	} else {
		$corps['senderForResponse'] = true;
	}

	$res = twofa_ovh_appel('POST', '/sms/' . rawurlencode($config['service']) . '/jobs', $corps, $config);
	if (is_string($res)) {
		spip_log("échec de l'envoi du SMS : $res", 'twofa.' . _LOG_ERREUR);
		return $res;
	}
	if (empty($res['validReceivers'])) {
		spip_log('SMS refusé par OVH, destinataire invalide', 'twofa.' . _LOG_ERREUR);
		return 'destinataire invalide';
	}
	spip_log('SMS envoyé, crédits restants : ' . ($res['creditsLeft'] ?? '?'), 'twofa.' . _LOG_INFO);

	return null;
}
