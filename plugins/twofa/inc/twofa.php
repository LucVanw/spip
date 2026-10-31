<?php

/**
 * API de la double authentification par TOTP (RFC 6238)
 *
 * Compatible avec les applications d'authentification usuelles
 * (Google Authenticator, Microsoft Authenticator, FreeOTP, 1Password, Bitwarden…).
 *
 * @package SPIP\Twofa\API
 */

use Spip\Chiffrer\Chiffrement;
use Spip\Chiffrer\SpipCles;

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/** Durée d'un pas TOTP, en secondes */
if (!defined('_TWOFA_PERIODE')) {
	define('_TWOFA_PERIODE', 30);
}
/** Nombre de chiffres d'un code TOTP */
if (!defined('_TWOFA_CHIFFRES')) {
	define('_TWOFA_CHIFFRES', 6);
}
/** Nombre de pas de tolérance avant et après l'instant courant (décalage d'horloge) */
if (!defined('_TWOFA_FENETRE')) {
	define('_TWOFA_FENETRE', 1);
}
/** Durée de validité d'une connexion en attente du second facteur, en secondes */
if (!defined('_TWOFA_DUREE_ATTENTE')) {
	define('_TWOFA_DUREE_ATTENTE', 300);
}
/** Nombre d'essais de code autorisés pour une connexion en attente */
if (!defined('_TWOFA_ESSAIS_MAX')) {
	define('_TWOFA_ESSAIS_MAX', 5);
}
/** Nombre de codes de secours générés à l'activation */
if (!defined('_TWOFA_NB_CODES_SECOURS')) {
	define('_TWOFA_NB_CODES_SECOURS', 10);
}

/**
 * Champs 2FA de spip_auteurs, à ne jamais exposer en session
 *
 * @return string[]
 */
function twofa_champs(): array {
	return ['twofa_secret', 'twofa_actif', 'twofa_secours', 'twofa_dernier', 'twofa_attente'];
}

/** ----------------------------------------------------------------------------
 * TOTP
 */

/**
 * Encoder en base32 (RFC 4648, sans padding)
 */
function twofa_base32_encoder(string $binaire): string {
	$alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
	$bits = '';
	foreach (str_split($binaire) as $octet) {
		$bits .= str_pad(decbin(ord($octet)), 8, '0', STR_PAD_LEFT);
	}
	$texte = '';
	foreach (str_split($bits, 5) as $morceau) {
		$texte .= $alphabet[bindec(str_pad($morceau, 5, '0'))];
	}

	return $texte;
}

/**
 * Décoder du base32 (RFC 4648), espaces, tirets et padding tolérés
 *
 * @return string Binaire, chaîne vide si l'entrée est invalide
 */
function twofa_base32_decoder(string $texte): string {
	$alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
	$texte = strtoupper((string) preg_replace('/[\s=-]/', '', $texte));
	$bits = '';
	foreach (str_split($texte) as $caractere) {
		$position = strpos($alphabet, $caractere);
		if ($position === false) {
			return '';
		}
		$bits .= str_pad(decbin($position), 5, '0', STR_PAD_LEFT);
	}
	$binaire = '';
	foreach (str_split($bits, 8) as $octet) {
		if (strlen($octet) === 8) {
			$binaire .= chr(bindec($octet));
		}
	}

	return $binaire;
}

/**
 * Générer un secret TOTP (160 bits, taille recommandée par la RFC 4226)
 *
 * @return string Binaire
 */
function twofa_generer_secret(): string {
	return random_bytes(20);
}

/**
 * Numéro du pas TOTP pour un instant donné
 */
function twofa_pas(?int $time = null): int {
	return intdiv($time ?? time(), _TWOFA_PERIODE);
}

/**
 * Calculer le code TOTP (HMAC-SHA1) d'un secret pour un pas donné
 *
 * @param string $secret Secret binaire
 */
function twofa_calculer_code(#[\SensitiveParameter] string $secret, int $pas): string {
	$hash = hash_hmac('sha1', pack('J', $pas), $secret, true);
	$offset = ord($hash[19]) & 0x0f;
	$valeur = unpack('N', substr($hash, $offset, 4))[1] & 0x7fffffff;

	return str_pad((string) ($valeur % 10 ** _TWOFA_CHIFFRES), _TWOFA_CHIFFRES, '0', STR_PAD_LEFT);
}

/**
 * Vérifier un code TOTP
 *
 * Un pas déjà utilisé (inférieur ou égal à `$dernier_pas`) est refusé
 * pour empêcher le rejeu d'un code intercepté.
 *
 * @param string $secret Secret binaire
 * @return int|null Pas accepté, null si le code est invalide
 */
function twofa_verifier_totp(#[\SensitiveParameter] string $secret, string $code, int $dernier_pas = 0, ?int $time = null): ?int {
	$code = (string) preg_replace('/\s+/', '', $code);
	if (!preg_match('/^\d{' . _TWOFA_CHIFFRES . '}$/', $code)) {
		return null;
	}
	$courant = twofa_pas($time);
	for ($i = -_TWOFA_FENETRE; $i <= _TWOFA_FENETRE; $i++) {
		$pas = $courant + $i;
		if ($pas > $dernier_pas && hash_equals(twofa_calculer_code($secret, $pas), $code)) {
			return $pas;
		}
	}

	return null;
}

/**
 * URI otpauth:// à scanner (QR code) ou ouvrir dans l'application d'authentification
 *
 * @param string $secret_base32
 */
function twofa_uri_otpauth(array $auteur, #[\SensitiveParameter] string $secret_base32): string {
	include_spip('inc/filtres');
	$emetteur = textebrut($GLOBALS['meta']['nom_site'] ?? '') ?: 'SPIP';
	$compte = $auteur['email'] ?: $auteur['login'];

	return 'otpauth://totp/' . rawurlencode($emetteur . ':' . $compte) . '?' . http_build_query([
		'secret' => $secret_base32,
		'issuer' => $emetteur,
		'algorithm' => 'SHA1',
		'digits' => _TWOFA_CHIFFRES,
		'period' => _TWOFA_PERIODE,
	], '', '&', PHP_QUERY_RFC3986);
}

/** ----------------------------------------------------------------------------
 * Stockage du secret
 */

/**
 * Chiffrer un secret avec le secret du site (config/cles.php)
 *
 * @param string $secret Secret binaire
 */
function twofa_chiffrer_secret(#[\SensitiveParameter] string $secret): string {
	$cle = SpipCles::secret_du_site();
	if (!$cle) {
		throw new RuntimeException('twofa : secret du site indisponible, impossible de chiffrer');
	}

	return (string) Chiffrement::chiffrer(twofa_base32_encoder($secret), $cle);
}

/**
 * Déchiffrer le secret stocké d'un auteur
 *
 * @return string|null Secret binaire, null si absent ou illisible
 */
function twofa_dechiffrer_secret(string $chiffre): ?string {
	if ($chiffre === '' || !($cle = SpipCles::secret_du_site())) {
		return null;
	}
	$base32 = Chiffrement::dechiffrer($chiffre, $cle);

	return $base32 ? twofa_base32_decoder($base32) : null;
}

/** ----------------------------------------------------------------------------
 * État d'un auteur
 */

/**
 * Lire un auteur (hors poubelle) avec ses champs 2FA
 */
function twofa_lire_auteur(int $id_auteur): ?array {
	$auteur = sql_fetsel('*', 'spip_auteurs', 'id_auteur=' . $id_auteur . " AND statut<>'5poubelle'");

	return $auteur ?: null;
}

/**
 * La double authentification est-elle active pour cet auteur ?
 *
 * @param array $auteur Ligne spip_auteurs (le champ est relu en base s'il est absent)
 */
function twofa_est_actif(array $auteur): bool {
	// champs pas encore créés (hit d'activation du plugin)
	if (empty($GLOBALS['meta']['twofa_base_version'])) {
		return false;
	}
	if (!array_key_exists('twofa_actif', $auteur)) {
		$auteur['twofa_actif'] = sql_getfetsel('twofa_actif', 'spip_auteurs', 'id_auteur=' . (int) ($auteur['id_auteur'] ?? 0));
	}

	return $auteur['twofa_actif'] === 'oui';
}

/**
 * Préparer l'enrôlement : générer un secret candidat, pas encore actif
 *
 * @return string Secret en base32, à afficher à l'auteur
 */
function twofa_preparer_activation(int $id_auteur): string {
	$secret = twofa_generer_secret();
	sql_updateq('spip_auteurs', [
		'twofa_secret' => twofa_chiffrer_secret($secret),
		'twofa_actif' => '',
		'twofa_secours' => '',
		'twofa_dernier' => 0,
		'twofa_attente' => '',
	], 'id_auteur=' . $id_auteur);

	return twofa_base32_encoder($secret);
}

/**
 * Activer la double authentification après validation d'un premier code
 *
 * @param int $pas Pas TOTP du code de validation (anti-rejeu)
 * @return string[] Codes de secours en clair, à afficher une seule fois
 */
function twofa_activer(int $id_auteur, int $pas): array {
	sql_updateq('spip_auteurs', ['twofa_actif' => 'oui', 'twofa_dernier' => $pas], 'id_auteur=' . $id_auteur);
	spip_logger('twofa')->notice("2FA activée pour l'auteur $id_auteur");

	return twofa_generer_codes_secours($id_auteur);
}

/**
 * Désactiver la double authentification et oublier le secret
 */
function twofa_desactiver(int $id_auteur): void {
	sql_updateq('spip_auteurs', [
		'twofa_secret' => '',
		'twofa_actif' => '',
		'twofa_secours' => '',
		'twofa_dernier' => 0,
		'twofa_attente' => '',
	], 'id_auteur=' . $id_auteur);
	spip_logger('twofa')->notice("2FA désactivée pour l'auteur $id_auteur");
}

/**
 * Vérifier un code saisi (TOTP ou code de secours) et le consommer
 */
function twofa_valider_code(array $auteur, string $code): bool {
	$secret = twofa_dechiffrer_secret((string) $auteur['twofa_secret']);
	if (
		$secret !== null
		&& ($pas = twofa_verifier_totp($secret, $code, (int) $auteur['twofa_dernier'])) !== null
	) {
		sql_updateq('spip_auteurs', ['twofa_dernier' => $pas], 'id_auteur=' . (int) $auteur['id_auteur']);
		return true;
	}

	return twofa_consommer_code_secours($auteur, $code);
}

/** ----------------------------------------------------------------------------
 * Codes de secours
 */

/**
 * Générer de nouveaux codes de secours (remplace les précédents)
 *
 * @return string[] Codes en clair
 */
function twofa_generer_codes_secours(int $id_auteur): array {
	// sans caractères ambigus (0/o, 1/l/i)
	$alphabet = 'abcdefghjkmnpqrstuvwxyz23456789';
	$codes = $hashes = [];
	for ($i = 0; $i < _TWOFA_NB_CODES_SECOURS; $i++) {
		$code = '';
		for ($j = 0; $j < 10; $j++) {
			$code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
		}
		$code = substr($code, 0, 5) . '-' . substr($code, 5);
		$codes[] = $code;
		$hashes[] = password_hash($code, PASSWORD_DEFAULT);
	}
	sql_updateq('spip_auteurs', ['twofa_secours' => json_encode($hashes)], 'id_auteur=' . $id_auteur);

	return $codes;
}

/**
 * Nombre de codes de secours encore utilisables
 */
function twofa_nb_codes_secours(array $auteur): int {
	$hashes = json_decode((string) ($auteur['twofa_secours'] ?? ''), true);

	return is_array($hashes) ? count($hashes) : 0;
}

/**
 * Vérifier un code de secours et le rayer s'il est valide (usage unique)
 */
function twofa_consommer_code_secours(array $auteur, string $code): bool {
	$code = strtolower((string) preg_replace('/[\s-]/', '', $code));
	if (!preg_match('/^[a-z0-9]{10}$/', $code)) {
		return false;
	}
	$code = substr($code, 0, 5) . '-' . substr($code, 5);
	$hashes = json_decode((string) $auteur['twofa_secours'], true);
	if (!is_array($hashes)) {
		return false;
	}
	foreach ($hashes as $k => $hash) {
		if (password_verify($code, $hash)) {
			unset($hashes[$k]);
			sql_updateq(
				'spip_auteurs',
				['twofa_secours' => json_encode(array_values($hashes))],
				'id_auteur=' . (int) $auteur['id_auteur']
			);
			spip_logger('twofa')->notice('code de secours utilisé par l\'auteur ' . $auteur['id_auteur'] . ', reste ' . count($hashes));
			return true;
		}
	}

	return false;
}

/** ----------------------------------------------------------------------------
 * Connexion en deux étapes
 */

/**
 * Mémoriser (pour le hit courant) l'auteur dont le second facteur vient d'être validé
 *
 * @param int|null $id_auteur Auteur à mémoriser, null pour lire
 * @return int Auteur validé, 0 sinon
 */
function twofa_connexion_validee(?int $id_auteur = null): int {
	static $valide = 0;
	if ($id_auteur !== null) {
		$valide = $id_auteur;
	}

	return $valide;
}

/**
 * Mémoriser (pour le hit courant) l'auteur identifié par mot de passe
 * dont la connexion a été bloquée en attente du second facteur
 *
 * @param array|null $auteur Auteur à mémoriser, null pour lire
 */
function twofa_connexion_bloquee(?array $auteur = null): ?array {
	static $bloque = null;
	if ($auteur !== null) {
		$bloque = $auteur;
	}

	return $bloque;
}

/**
 * Un auteur peut-il ouvrir une session ?
 *
 * Appelé par autoriser('loger') depuis auth_init_droits(), c'est-à-dire :
 * - à chaque hit de l'espace privé (session déjà ouverte) ;
 * - à chaque ouverture de session via auth_loger() (formulaire de login,
 *   auth HTTP, SSO, retour de mot de passe oublié…), qui annule au préalable
 *   le cookie de session.
 *
 * Toute ouverture de session pour un auteur ayant la 2FA active est donc
 * refusée tant que son second facteur n'a pas été validé dans ce hit.
 */
function twofa_autoriser_connexion(array $auteur): bool {
	$id_auteur = (int) ($auteur['id_auteur'] ?? 0);
	if ($id_auteur <= 0 || !twofa_est_actif($auteur)) {
		return true;
	}
	if (twofa_connexion_validee() === $id_auteur) {
		return true;
	}
	// session déjà ouverte pour cet auteur : cookie validé par auth_mode()
	include_spip('inc/session');
	if (
		($cookie = lire_cookie_session())
		&& (int) $cookie === $id_auteur
		&& (int) ($GLOBALS['visiteur_session']['id_auteur'] ?? 0) === $id_auteur
	) {
		return true;
	}

	twofa_connexion_bloquee($auteur);
	spip_logger('twofa')->info("connexion de l'auteur $id_auteur en attente du second facteur");

	return false;
}

/**
 * Enregistrer une connexion en attente du second facteur
 *
 * @return string Jeton à repasser avec le code (`<id_auteur>-<aléa>`)
 */
function twofa_creer_attente(array $auteur): string {
	$jeton = bin2hex(random_bytes(32));
	$attente = [
		'jeton' => hash('sha256', $jeton),
		'expire' => time() + _TWOFA_DUREE_ATTENTE,
		'essais' => 0,
		'cookie' => (($auteur['cookie'] ?? '') === 'oui') ? 'oui' : '',
	];
	sql_updateq('spip_auteurs', ['twofa_attente' => json_encode($attente)], 'id_auteur=' . (int) $auteur['id_auteur']);

	return (int) $auteur['id_auteur'] . '-' . $jeton;
}

/**
 * Retrouver une connexion en attente à partir de son jeton
 *
 * @return array|null [auteur, attente], null si jeton inconnu, expiré ou épuisé
 */
function twofa_lire_attente(#[\SensitiveParameter] string $jeton): ?array {
	if (!preg_match('/^(\d+)-([0-9a-f]{64})$/', $jeton, $m)) {
		return null;
	}
	$auteur = twofa_lire_auteur((int) $m[1]);
	if (!$auteur || !twofa_est_actif($auteur)) {
		return null;
	}
	$attente = json_decode((string) $auteur['twofa_attente'], true);
	if (
		!is_array($attente)
		|| empty($attente['jeton'])
		|| !hash_equals($attente['jeton'], hash('sha256', $m[2]))
	) {
		return null;
	}
	if (($attente['expire'] ?? 0) < time() || ($attente['essais'] ?? 0) >= _TWOFA_ESSAIS_MAX) {
		twofa_effacer_attente((int) $auteur['id_auteur']);
		return null;
	}

	return [$auteur, $attente];
}

/**
 * Compter un essai de code raté
 *
 * @return int Essais restants
 */
function twofa_compter_echec(array $auteur, array $attente): int {
	$attente['essais'] = ($attente['essais'] ?? 0) + 1;
	sql_updateq('spip_auteurs', ['twofa_attente' => json_encode($attente)], 'id_auteur=' . (int) $auteur['id_auteur']);
	spip_logger('twofa')->notice('code 2FA invalide pour l\'auteur ' . $auteur['id_auteur'] . ' (essai ' . $attente['essais'] . ')');

	return max(0, _TWOFA_ESSAIS_MAX - $attente['essais']);
}

/**
 * Oublier la connexion en attente d'un auteur
 */
function twofa_effacer_attente(int $id_auteur): void {
	sql_updateq('spip_auteurs', ['twofa_attente' => ''], 'id_auteur=' . $id_auteur);
}
