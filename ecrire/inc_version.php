<?php

/**
 * SPIP, Système de publication pour l'internet
 *
 * Copyright © avec tendresse depuis 2001
 * Arnaud Martin, Antoine Pitrou, Philippe Rivière, Emmanuel Saint-James
 *
 * Ce programme est un logiciel libre distribué sous licence GNU/GPL.
 */

use Psr\Log\LogLevel;
use Spip\ErrorHandler;
use Spip\Afficher\Minipage\Admin as MinipageAdmin;

/**
 * Initialisation de SPIP
 *
 * @package SPIP\Core\Chargement
 */

if (defined('_ECRIRE_INC_VERSION')) {
	return;
}

require_once __DIR__ . '/bootstrap/config/initial.php';

// inclure l'ecran de securite si il n'a pas été inclus en prepend php
if (
	!defined('_ECRAN_SECURITE')
	&& @file_exists($f = _ROOT_RACINE . _NOM_PERMANENTS_INACCESSIBLES . 'ecran_securite.php')
) {
	include $f;
}

require_once __DIR__ . '/bootstrap/config/globals.php';
require_once __DIR__ . '/bootstrap/proxy.php';
require_once __DIR__ . '/bootstrap/mitigation.php';

// numero de branche, utilise par les plugins
// pour specifier les versions de SPIP necessaires
// il faut s'en tenir a un nombre de decimales fixe
// ex : 2.0.0, 2.0.0-dev, 2.0.0-beta, 2.0.0-beta2
$spip_version_branche = '5.0.0-dev';
define('_SPIP_VERSION_ID', 50000);
define('_SPIP_EXTRA_VERSION', '-dev');

/** version PHP minimum exigee (cf. inc/utils) */
define('_PHP_MIN', '8.2.0');
define('_PHP_MAX', '8.4.99');

// cette version dev accepte tous les plugins compatible avec la version ci-dessous
// a supprimer en phase beta/rc/release
# define('_DEV_VERSION_SPIP_COMPAT', '4.4.99');
// version des signatures de fonctions PHP
// (= date de leur derniere modif cassant la compatibilite et/ou necessitant un recalcul des squelettes)
$spip_version_code = 2024_08_01;
// version de la base SQL (= Date + numero incremental a 2 chiffres YYYYMMDDXX)
$spip_version_base = 2022_02_23_03;

// version de spip en chaine
// 1.xxyy : xx00 versions stables publiees, xxyy versions de dev
// (ce qui marche pour yy ne marchera pas forcement sur une version plus ancienne)
$spip_version_affichee = "$spip_version_branche";

// Droits d'acces maximum par defaut
@umask(0);

//
// Charger les fonctions liees aux serveurs Http et Sql.
//
require_once __DIR__ . '/bootstrap/functions.php';

// Definition personnelles eventuelles

if (_FILE_OPTIONS) {
	include_once _FILE_OPTIONS;
}

if (!defined('SPIP_ERREUR_REPORT')) {
	/** Masquer les warning */
	define('SPIP_ERREUR_REPORT', E_ALL ^ E_NOTICE ^ E_DEPRECATED);
}

ErrorHandler::setup(SPIP_ERREUR_REPORT);

// Initialisations critiques non surchargeables par les plugins
// INITIALISER LES REPERTOIRES NON PARTAGEABLES ET LES CONSTANTES
// (charge aussi inc/flock)
//
// mais l'inclusion precedente a peut-etre deja appele cette fonction
// ou a defini certaines des constantes que cette fonction doit definir
// ===> on execute en neutralisant les messages d'erreur

spip_initialisation_core(
	(_DIR_RACINE . _NOM_PERMANENTS_INACCESSIBLES),
	(_DIR_RACINE . _NOM_PERMANENTS_ACCESSIBLES),
	(_DIR_RACINE . _NOM_TEMPORAIRES_INACCESSIBLES),
	(_DIR_RACINE . _NOM_TEMPORAIRES_ACCESSIBLES)
);

// timezone
include_spip('inc/config');
if ($timezone = lire_config('timezone')) {
	date_default_timezone_set($timezone);
}

// chargement des plugins : doit arriver en dernier
// car dans les plugins on peut inclure inc-version
// qui ne sera pas execute car _ECRIRE_INC_VERSION est defini
// donc il faut avoir tout fini ici avant de charger les plugins

if (@is_readable(_CACHE_PLUGINS_OPT) && @is_readable(_CACHE_PLUGINS_PATH)) {
	// chargement optimise precompile
	include_once(_CACHE_PLUGINS_OPT);
} else {
	spip_initialisation_suite();
	include_spip('inc/plugin');
	// generer les fichiers php precompiles
	// de chargement des plugins et des pipelines
	actualise_plugins_actifs();
}

// Initialisations non critiques surchargeables par les plugins
spip_initialisation_suite();

if (!defined('_LOG_FILTRE_GRAVITE')) {
	/**
	 * Niveau maxi d'enregistrement des logs
	 * @var LogLevel::*
	 */
	define('_LOG_FILTRE_GRAVITE', LogLevel::NOTICE);
}

if (!defined('_OUTILS_DEVELOPPEURS')) {
	/** Activer des outils pour développeurs ? */
	define('_OUTILS_DEVELOPPEURS', false);
}

// charger systematiquement inc/autoriser dans l'espace restreint
if (test_espace_prive()) {
	include_spip('inc/autoriser');
}

//
// Installer Spip si pas installe... sauf si justement on est en train
//
if (
	!(_FILE_CONNECT
	|| autoriser_sans_cookie(_request('exec'))
	|| _request('action') == 'cookie'
	|| _request('action') == 'converser'
	|| _request('action') == 'test_dirs')
) {
	// Si on peut installer, on lance illico
	if (test_espace_prive()) {
		include_spip('inc/headers');
		redirige_url_ecrire('install');
	} else {
		// Si on est dans le site public, dire que qq s'en occupe
		include_spip('inc/lang');
		utiliser_langue_visiteur();
		$minipage = new MinipageAdmin();
		echo $minipage->page(
			"<p style='text-align: center;'>" . _T('info_travaux_texte') . '</p>',
			[
				'titre' => _T('info_travaux_titre'),
				'status' => 503
			]
		);
		exit;
	}
	// autrement c'est une install ad hoc (spikini...), on sait pas faire
}

// memoriser un tri sessionne eventuel
if (
	isset($_REQUEST['var_memotri'])
	&& ($t = $_REQUEST['var_memotri'])
	&& (str_starts_with((string) $t, 'trisession') || str_starts_with((string) $t, 'senssession'))
) {
	if (!function_exists('session_set')) {
		include_spip('inc/session');
	}
	$t = preg_replace(',\W,', '_', (string) $t);
	if ($v = _request($t)) {
		session_set($t, $v);
	}
}

/**
 * Header "Composed-By"
 *
 * Vanter notre art de la composition typographique
 * La globale $spip_header_silencieux permet de rendre le header minimal pour raisons de securite
 */
if (!defined('_HEADER_COMPOSED_BY')) {
	define('_HEADER_COMPOSED_BY', 'Composed-By: SPIP');
}
if (!headers_sent() && _HEADER_COMPOSED_BY) {
	if (!defined('_HEADER_VARY')) {
		define('_HEADER_VARY', 'Vary: Cookie, Accept-Encoding');
	}
	if (_HEADER_VARY) {
		header(_HEADER_VARY);
	}
	if (!isset($GLOBALS['spip_header_silencieux']) || !$GLOBALS['spip_header_silencieux']) {
		include_spip('inc/filtres_mini');
		header(_HEADER_COMPOSED_BY . " $spip_version_affichee @ www.spip.net + " . url_absolue(_DIR_VAR . 'config.txt'));
	} else {
		// header minimal
		header(_HEADER_COMPOSED_BY . ' @ www.spip.net');
	}
}

$methode = ($_SERVER['REQUEST_METHOD'] ?? ((PHP_SAPI == 'cli') ? 'cli' : ''));
spip_logger()
	->debug($methode . ' ' . self() . ' - ' . _FILE_CONNECT);
