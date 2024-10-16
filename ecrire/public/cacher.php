<?php

/**
 * SPIP, Système de publication pour l'internet
 *
 * Copyright © avec tendresse depuis 2001
 * Arnaud Martin, Antoine Pitrou, Philippe Rivière, Emmanuel Saint-James
 *
 * Ce programme est un logiciel libre distribué sous licence GNU/GPL.
 */

use Psr\SimpleCache\CacheInterface;
use SpipLeague\Component\Cache\Adapter\LimitedFilesystem;
use SpipLeague\Component\Hasher\Hash32;
use Spip\Afficher\Minipage\Admin as MinipageAdmin;

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Returns a PSR-16 Simple Cache Instance
 *
 * @internal Temporary fonction until DI in SPIP
 */
function cache_instance(): CacheInterface {
	static $cache = null;
	return $cache ??= new LimitedFilesystem('calcul', _DIR_CACHE);
}

/**
 * Returns a key cache (id) for this data
 */
function cache_key(array $contexte, array $page): string {
	static $hasher = null;
	$hasher ??= new Hash32();
	return $hasher->hash([$contexte, $page]) . '.cache';
}

/**
 * Écrire le cache dans un casier
 */
function ecrire_cache(string $cache_key, array $valeur): bool {
	return cache_instance()->set($cache_key, ['cache_key' => $cache_key, 'valeur' => $valeur]);
}

/**
 * lire le cache depuis un casier
 *
 * @return null|mixed null: probably cache miss
 */
function lire_cache(string $cache_key): mixed {
	return cache_instance()->get($cache_key)['valeur'] ?? null;
}

/**
 * Signature du cache
 *
 * Parano : on signe le cache, afin d'interdire un hack d'injection dans notre memcache
 */
function cache_signature(&$page): string {
	if (!isset($GLOBALS['meta']['cache_signature'])) {
		include_spip('inc/acces');
		ecrire_meta(
			'cache_signature',
			hash('sha256', $_SERVER['DOCUMENT_ROOT'] . ($_SERVER['SERVER_SIGNATURE'] ?? '') . creer_uniqid()),
			'non'
		);
	}

	return (new Hash32())->hash($GLOBALS['meta']['cache_signature'] . $page['texte']);
}

/**
 * Faut-il compresser ce cache ?
 *
 * A partir de 16ko ca vaut le coup
 * (pas de passage par reference car on veut conserver la version non compressee
 * pour l'afficher)
 * on positionne un flag gz si on comprime, pour savoir si on doit decompresser ou pas
 */
function gzip_page(array $page): array {
	if (function_exists('gzcompress') && strlen((string) $page['texte']) > 16 * 1024) {
		$page['gz'] = true;
		$page['texte'] = gzcompress((string) $page['texte']);
	} else {
		$page['gz'] = false;
	}

	return $page;
}

/**
 * Faut-il decompresser ce cache ?
 *
 * (passage par reference pour alleger)
 * on met a jour le flag gz quand on decompresse, pour ne pas risquer
 * de decompresser deux fois de suite un cache (ce qui echoue)
 */
function gunzip_page(array &$page): void {
	if ($page['gz']) {
		$page['texte'] = gzuncompress($page['texte']);
		$page['gz'] = false; // ne pas gzuncompress deux fois une meme page
	}
}

/**
 * Gestion des delais d'expiration du cache...
 *
 * $page passee par reference pour accelerer
 *
 * @return int
 *  - 1 si il faut mettre le cache a jour
 *  - 0 si le cache est valide
 *  - -1 si il faut calculer sans stocker en cache
 */
function cache_valide(array &$page, int $date): int {
	$now = $_SERVER['REQUEST_TIME'];

	// Apparition d'un nouvel article post-date ?
	if (
		isset($GLOBALS['meta']['post_dates'])
		&& $GLOBALS['meta']['post_dates'] == 'non'
		&& isset($GLOBALS['meta']['date_prochain_postdate'])
		&& $now > $GLOBALS['meta']['date_prochain_postdate']
	) {
		spip_logger()->info('Un article post-date invalide le cache');
		include_spip('inc/rubriques');
		calculer_prochain_postdate(true);
	}

	if (defined('_VAR_NOCACHE') && _VAR_NOCACHE) {
		return -1;
	}
	if (isset($GLOBALS['meta']['cache_inhib']) && $_SERVER['REQUEST_TIME'] < $GLOBALS['meta']['cache_inhib']) {
		return -1;
	}
	if (defined('_NO_CACHE')) {
		return (_NO_CACHE == 0 && !isset($page['texte'])) ? 1 : _NO_CACHE;
	}

	// pas de cache ? on le met a jour, sauf pour les bots (on leur calcule la page sans mise en cache)
	if (!$page || !isset($page['texte']) || !isset($page['entetes']['X-Spip-Cache'])) {
		return _IS_BOT ? -1 : 1;
	}

	// controle de la signature
	if ($page['sig'] !== cache_signature($page)) {
		return _IS_BOT ? -1 : 1;
	}

	// #CACHE{n,statique} => on n'invalide pas avec derniere_modif
	// cf. ecrire/public/balises.php, balise_CACHE_dist()
	// Cache invalide par la meta 'derniere_modif'
	// sauf pour les bots, qui utilisent toujours le cache
	if (
		(!isset($page['entetes']['X-Spip-Statique']) || $page['entetes']['X-Spip-Statique'] !== 'oui')
		&& (
			!_IS_BOT
			&& $GLOBALS['derniere_modif_invalide']
			&& isset($GLOBALS['meta']['derniere_modif'])
			&& $date < $GLOBALS['meta']['derniere_modif']
		)
	) {
		return 1;
	}

	// Sinon comparer l'age du fichier a sa duree de cache
	$duree = (int) $page['entetes']['X-Spip-Cache'];
	$cache_mark = max($GLOBALS['meta']['cache_mark'] ?? 0, $GLOBALS['meta']['cache_bot_invalide'] ?? 0);
	if ($duree == 0) {  #CACHE{0}
		return -1;
	} // sauf pour les bots, qui utilisent toujours le cache

	if (
		!_IS_BOT && $date + $duree < $now
		|| $date < $cache_mark
	) {
		return _IS_BOT ? -1 : 1;
	}
	return 0;

}

/**
 * Creer le fichier cache
 *
 * Passage par reference de $page par souci d'economie
 *
 * @param array $page
 * @param string $cache_key
 */
function creer_cache(&$page, &$cache_key) {

	// Ne rien faire si on est en preview, debug, ou si une erreur
	// grave s'est presentee (compilation du squelette, MySQL, etc)
	// le cas var_nocache ne devrait jamais arriver ici (securite)
	// le cas spip_interdire_cache correspond a une ereur SQL grave non anticipable
	if (
		defined('_VAR_NOCACHE') && _VAR_NOCACHE
		|| defined('spip_interdire_cache')
	) {
		return;
	}

	// Si la page a un invalideur de session, utiliser un cache_key spécifique
	if (
		isset($page['invalideurs'])
		&& isset($page['invalideurs']['session'])
	) {
		// on verifie que le contenu du chemin cache indique seulement
		// "cache sessionne" ; sa date indique la date de validite
		// des caches sessionnes
		if (!$tmp = lire_cache($cache_key)) {
			spip_logger()->info('Creation cache sessionne ' . $cache_key);
			$tmp = [
				'invalideurs' => ['session' => ''],
				'lastmodified' => $_SERVER['REQUEST_TIME'],
			];
			ecrire_cache($cache_key, $tmp);
		}
		$cache_key = cache_key(['cache_key' => $cache_key], ['session' => $page['invalideurs']['session']]);
	}

	// ajouter la date de production dans le cache lui meme
	// (qui contient deja sa duree de validite)
	$page['lastmodified'] = $_SERVER['REQUEST_TIME'];

	// compresser le contenu si besoin
	$pagez = gzip_page($page);

	// signer le contenu
	$pagez['sig'] = cache_signature($pagez);

	// l'enregistrer, compresse ou non...
	$ok = ecrire_cache($cache_key, $pagez);

	spip_logger()
		->info((_IS_BOT ? 'Bot:' : '') . "Creation du cache $cache_key pour "
				. $page['entetes']['X-Spip-Cache'] . ' secondes' . ($ok ? '' : ' (erreur!)'));

	// Inserer ses invalideurs
	include_spip('inc/invalideur');
	maj_invalideurs($cache_key, $page);
}

/**
 * Interface du gestionnaire de cache
 *
 * Si son 3e argument est non vide, elle passe la main a creer_cache
 * Sinon, elle recoit un contexte (ou le construit a partir de REQUEST_URI)
 * et affecte les 4 autres parametres recus par reference:
 * - use_cache qui vaut
 *     -1 s'il faut calculer la page sans la mettre en cache
 *      0 si on peut utiliser un cache existant
 *      1 s'il faut calculer la page et la mettre en cache
 * - cache_key est un identifiant pour ce cache, ou vide si pas cachable
 * - page qui est le tableau decrivant la page, si le cache la contenait
 * - lastmodified qui vaut la date de derniere modif du fichier.
 * Elle retourne '' si tout va bien
 * un message d'erreur si le calcul de la page est totalement impossible
 *
 * @param array $contexte
 * @param int $use_cache
 * @param string $cache_key
 * @param array $page
 * @param int $lastmodified
 * @return string|void
 */
function public_cacher_dist($contexte, &$use_cache, &$cache_key, &$page, &$lastmodified) {

	# fonction de cache minimale : dire "non on ne met rien en cache"
	# $use_cache = -1; return;

	// Second appel, destine a l'enregistrement du cache sur le disque
	if (isset($cache_key)) {
		creer_cache($page, $cache_key);
		return;
	}

	// Toute la suite correspond au premier appel
	$contexte_implicite = $page['contexte_implicite'];

	// Cas ignorant le cache car completement dynamique
	if (
		!empty($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST'
		|| _request('connect')
	) {
		$use_cache = -1;
		$lastmodified = 0;
		$cache_key = '';
		$page = [];

		return;
	}

	// Controler l'existence d'un cache nous correspondant
	$cache_key = cache_key($contexte, $page);
	$lastmodified = 0;

	// charger le cache s'il existe (et si il a bien le bon hash = anticollision)
	if (!$page = lire_cache($cache_key)) {
		$page = [];
	}

	// s'il est sessionne, charger celui correspondant a notre session
	if (
		isset($page['invalideurs'])
		&& isset($page['invalideurs']['session'])
	) {
		$cache_key_session = cache_key(['cache_key' => $cache_key], ['session' => spip_session()]);
		if (
			($page_session = lire_cache($cache_key_session)) && $page_session['lastmodified'] >= $page['lastmodified']
		) {
			$page = $page_session;
		} else {
			$page = [];
		}
	}

	// Faut-il effacer des pages invalidees (en particulier ce cache-ci) ?
	// ne le faire que si la base est disponible
	$invalider = false;
	if (isset($GLOBALS['meta']['invalider']) && spip_connect()) {
		$invalider = true;
	}

	// Si un calcul, recalcul [ou preview, mais c'est recalcul] est demande,
	// on supprime le cache
	if (
		defined('_VAR_MODE')
		&& _VAR_MODE
		&& (
			isset($_COOKIE['spip_session'])
			|| isset($_COOKIE['spip_admin'])
			|| @file_exists(_ACCESS_FILE_NAME)
		)
	) {
		$page = ['contexte_implicite' => $contexte_implicite]; // ignorer le cache deja lu
		$invalider = true;
	}
	if ($invalider) {
		include_spip('inc/invalideur');
		retire_caches($cache_key); # API invalideur inutile
		cache_instance()
			->delete($cache_key);
		if (isset($cache_key_session) && $cache_key_session) {
			cache_instance()->delete($cache_key_session);
		}
	}

	// $delais par defaut
	// pour toutes les pages sans #CACHE{} hors modeles/ et espace privé
	// qui sont a cache nul par defaut
	if (!isset($GLOBALS['delais'])) {
		if (!defined('_DUREE_CACHE_DEFAUT')) {
			define('_DUREE_CACHE_DEFAUT', 24 * 3600);
		}
		$GLOBALS['delais'] = _DUREE_CACHE_DEFAUT;
	}

	// determiner la validite de la page
	if ($page) {
		$use_cache = cache_valide($page, $page['lastmodified'] ?? 0);
		// le contexte implicite n'est pas stocke dans le cache, mais il y a equivalence
		// par le nom du cache. On le reinjecte donc ici pour utilisation eventuelle au calcul
		$page['contexte_implicite'] = $contexte_implicite;
		if (!$use_cache) {
			// $page est un cache utilisable
			gunzip_page($page);

			return;
		}
	} else {
		$page = ['contexte_implicite' => $contexte_implicite];
		$use_cache = cache_valide($page, 0); // fichier cache absent : provoque le calcul
	}

	// Si pas valide mais pas de connexion a la base, le garder quand meme
	if (!spip_connect()) {
		if (isset($page['texte'])) {
			gunzip_page($page);
			$use_cache = 0;
		} else {
			spip_logger()->info("Erreur base de donnees, impossible utiliser $cache_key");
			$minipage = new MinipageAdmin();
			return $minipage->page(_T('titre_probleme_technique'), ['titre' => _T('info_travaux_titre'), 'status' => 503]);
		}
	}

	if ($use_cache < 0) {
		$cache_key = '';
	}

	return;
}
