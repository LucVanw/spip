<?php

/**
 * Fonction d'initialisation groupée pour compatibilité ascendante
 *
 * @param string $pi Répertoire permanent inaccessible
 * @param string $pa Répertoire permanent accessible
 * @param string $ti Répertoire temporaire inaccessible
 * @param string $ta Répertoire temporaire accessible
 */
function spip_initialisation($pi = null, $pa = null, $ti = null, $ta = null) {
	spip_initialisation_core($pi, $pa, $ti, $ta);
	spip_initialisation_suite();
}

/**
 * Fonction d'initialisation, appellée dans inc_version ou mes_options
 *
 * Elle définit les répertoires et fichiers non partageables
 * et indique dans $test_dirs ceux devant être accessibles en écriture
 * mais ne touche pas à cette variable si elle est déjà définie
 * afin que mes_options.php puisse en spécifier d'autres.
 *
 * Elle définit ensuite les noms des fichiers et les droits.
 * Puis simule un register_global=on sécurisé.
 *
 * @param string $pi Répertoire permanent inaccessible
 * @param string $pa Répertoire permanent accessible
 * @param string $ti Répertoire temporaire inaccessible
 * @param string $ta Répertoire temporaire accessible
 */
function spip_initialisation_core($pi = null, $pa = null, $ti = null, $ta = null) {
	static $too_late = 0;
	if ($too_late++) {
		return;
	}

	// Declaration des repertoires

	// le nom du repertoire plugins/ activables/desactivables
	if (!defined('_DIR_PLUGINS')) {
		define('_DIR_PLUGINS', _DIR_RACINE . 'plugins/');
	}

	// le nom du repertoire des extensions/ permanentes du core, toujours actives
	if (!defined('_DIR_PLUGINS_DIST')) {
		define('_DIR_PLUGINS_DIST', _DIR_RACINE . 'plugins-dist/');
	}

	// le nom du repertoire des librairies
	if (!defined('_DIR_LIB')) {
		define('_DIR_LIB', _DIR_RACINE . 'lib/');
	}

	// répertoire des libs via Composer
	if (!defined('_DIR_VENDOR')) {
		define('_DIR_VENDOR', _DIR_RACINE . 'vendor/');
	}

	if (!defined('_DIR_IMG')) {
		define('_DIR_IMG', $pa);
	}
	if (!defined('_DIR_LOGOS')) {
		define('_DIR_LOGOS', $pa);
	}
	if (!defined('_DIR_IMG_ICONES')) {
		define('_DIR_IMG_ICONES', _DIR_LOGOS . 'icones/');
	}

	if (!defined('_DIR_DUMP')) {
		define('_DIR_DUMP', $ti . 'dump/');
	}
	if (!defined('_DIR_SESSIONS')) {
		define('_DIR_SESSIONS', $ti . 'sessions/');
	}
	if (!defined('_DIR_TRANSFERT')) {
		define('_DIR_TRANSFERT', $ti . 'upload/');
	}
	if (!defined('_DIR_CACHE')) {
		define('_DIR_CACHE', $ti . 'cache/');
	}
	if (!defined('_DIR_CACHE_XML')) {
		define('_DIR_CACHE_XML', _DIR_CACHE . 'xml/');
	}
	if (!defined('_DIR_SKELS')) {
		define('_DIR_SKELS', _DIR_CACHE . 'skel/');
	}
	if (!defined('_DIR_AIDE')) {
		define('_DIR_AIDE', _DIR_CACHE . 'aide/');
	}
	if (!defined('_DIR_TMP')) {
		define('_DIR_TMP', $ti);
	}

	if (!defined('_DIR_VAR')) {
		define('_DIR_VAR', $ta);
	}

	if (!defined('_DIR_ETC')) {
		define('_DIR_ETC', $pi);
	}
	if (!defined('_DIR_CONNECT')) {
		define('_DIR_CONNECT', $pi);
	}
	if (!defined('_DIR_CHMOD')) {
		define('_DIR_CHMOD', $pi);
	}

	if (!isset($GLOBALS['test_dirs'])) {
		// Pas $pi car il est bon de le mettre hors ecriture apres intstall
		// il sera rajoute automatiquement si besoin a l'etape 2 de l'install
		$GLOBALS['test_dirs'] = [$pa, $ti, $ta];
	}

	// Declaration des fichiers

	if (!defined('_CACHE_PLUGINS_PATH')) {
		define('_CACHE_PLUGINS_PATH', _DIR_CACHE . 'charger_plugins_chemins.php');
	}
	if (!defined('_CACHE_PLUGINS_OPT')) {
		define('_CACHE_PLUGINS_OPT', _DIR_CACHE . 'charger_plugins_options.php');
	}
	if (!defined('_CACHE_PLUGINS_FCT')) {
		define('_CACHE_PLUGINS_FCT', _DIR_CACHE . 'charger_plugins_fonctions.php');
	}
	if (!defined('_CACHE_PIPELINES')) {
		define('_CACHE_PIPELINES', _DIR_CACHE . 'charger_pipelines.php');
	}
	if (!defined('_CACHE_CHEMIN')) {
		define('_CACHE_CHEMIN', _DIR_CACHE . 'chemin.txt');
	}

	# attention .php obligatoire pour ecrire_fichier_securise
	if (!defined('_FILE_META')) {
		define('_FILE_META', $ti . 'meta_cache.php');
	}
	if (!defined('_DIR_LOG')) {
		define('_DIR_LOG', _DIR_TMP . 'log/');
	}
	if (!defined('_FILE_LOG')) {
		define('_FILE_LOG', 'spip');
	}
	if (!defined('_FILE_LOG_SUFFIX')) {
		define('_FILE_LOG_SUFFIX', '.log');
	}

	// Le fichier de connexion a la base de donnees
	if (!defined('_FILE_CONNECT_INS')) {
		define('_FILE_CONNECT_INS', 'connect');
	}
	if (!defined('_FILE_CONNECT')) {
		define('_FILE_CONNECT', @is_readable($f = _DIR_CONNECT . _FILE_CONNECT_INS . '.php') ? $f : false);
	}

	// Le fichier de reglages des droits
	if (!defined('_FILE_CHMOD_INS')) {
		define('_FILE_CHMOD_INS', 'chmod');
	}
	if (!defined('_FILE_CHMOD')) {
		define('_FILE_CHMOD', @is_readable($f = _DIR_CHMOD . _FILE_CHMOD_INS . '.php') ? $f : false);
	}

	if (!defined('_FILE_LDAP')) {
		define('_FILE_LDAP', 'ldap.php');
	}

	if (!defined('_FILE_TMP_SUFFIX')) {
		define('_FILE_TMP_SUFFIX', '.tmp.php');
	}
	if (!defined('_FILE_CONNECT_TMP')) {
		define('_FILE_CONNECT_TMP', _DIR_CONNECT . _FILE_CONNECT_INS . _FILE_TMP_SUFFIX);
	}
	if (!defined('_FILE_CHMOD_TMP')) {
		define('_FILE_CHMOD_TMP', _DIR_CHMOD . _FILE_CHMOD_INS . _FILE_TMP_SUFFIX);
	}

	// Definition des droits d'acces en ecriture
	if (!defined('_SPIP_CHMOD') && _FILE_CHMOD) {
		include_once _FILE_CHMOD;
	}

	// Se mefier des fichiers mal remplis!
	if (!defined('_SPIP_CHMOD')) {
		define('_SPIP_CHMOD', 0777);
	}

	if (!defined('_DEFAULT_CHARSET')) {
		/** Le charset par défaut lors de l'installation */
		define('_DEFAULT_CHARSET', 'utf-8');
	}
	if (!defined('_ROOT_PLUGINS')) {
		define('_ROOT_PLUGINS', _ROOT_RACINE . 'plugins' . DIRECTORY_SEPARATOR);
	}
	if (!defined('_ROOT_PLUGINS_DIST')) {
		define('_ROOT_PLUGINS_DIST', _ROOT_RACINE . 'plugins-dist' . DIRECTORY_SEPARATOR);
	}
	if (!defined('_ROOT_PLUGINS_SUPPL') && defined('_DIR_PLUGINS_SUPPL') && _DIR_PLUGINS_SUPPL) {
		define('_ROOT_PLUGINS_SUPPL', _ROOT_RACINE . str_replace(_DIR_RACINE, '', _DIR_PLUGINS_SUPPL));
	}

	// La taille des Log
	if (!defined('_MAX_LOG')) {
		define('_MAX_LOG', 100);
	}

	// Sommes-nous dans l'empire du Mal ?
	// (ou sous le signe du Pingouin, ascendant GNU ?)
	if (isset($_SERVER['SERVER_SOFTWARE']) && str_contains($_SERVER['SERVER_SOFTWARE'], '(Win')) {
		if (!defined('_OS_SERVEUR')) {
			define('_OS_SERVEUR', 'windows');
		}
		if (!defined('_SPIP_LOCK_MODE')) {
			define('_SPIP_LOCK_MODE', 1);
		} // utiliser le flock php
	} else {
		if (!defined('_OS_SERVEUR')) {
			define('_OS_SERVEUR', '');
		}
		if (!defined('_SPIP_LOCK_MODE')) {
			define('_SPIP_LOCK_MODE', 1);
		} // utiliser le flock php
		#if (!defined('_SPIP_LOCK_MODE')) define('_SPIP_LOCK_MODE',2); // utiliser le nfslock de spip mais link() est tres souvent interdite
	}

	// Langue par defaut
	if (!defined('_LANGUE_PAR_DEFAUT')) {
		define('_LANGUE_PAR_DEFAUT', 'fr');
	}

	//
	// Module de lecture/ecriture/suppression de fichiers utilisant flock()
	// (non surchargeable en l'etat ; attention si on utilise include_spip()
	// pour le rendre surchargeable, on va provoquer un reecriture
	// systematique du noyau ou une baisse de perfs => a etudier)
	include_once _ROOT_RESTREINT . 'inc/flock.php';

	// charger le path des plugins
	if (@is_readable(_CACHE_PLUGINS_PATH)) {
		include_once(_CACHE_PLUGINS_PATH);
	}

	// *********** traiter les variables ************

	//
	// Securite
	//

	// Ne pas se faire manger par un bug php qui accepte ?GLOBALS[truc]=toto
	if (isset($_REQUEST['GLOBALS'])) {
		die();
	}
	// nettoyer les magic quotes \' et les caracteres nuls %00
	spip_desinfecte($_GET);
	spip_desinfecte($_POST);
	spip_desinfecte($_COOKIE);
	spip_desinfecte($_REQUEST);

	// appliquer le cookie_prefix
	if ($GLOBALS['cookie_prefix'] != 'spip') {
		include_spip('inc/cookie');
		recuperer_cookies_spip($GLOBALS['cookie_prefix']);
	}

	// Compatibilite avec serveurs ne fournissant pas $REQUEST_URI
	if (isset($_SERVER['REQUEST_URI'])) {
		$GLOBALS['REQUEST_URI'] = $_SERVER['REQUEST_URI'];
	} else {
		$GLOBALS['REQUEST_URI'] = (PHP_SAPI !== 'cli') ? $_SERVER['PHP_SELF'] : '';
		if (
			!empty($_SERVER['QUERY_STRING'])
			&& !strpos($_SERVER['REQUEST_URI'], '?')
		) {
			$GLOBALS['REQUEST_URI'] .= '?' . $_SERVER['QUERY_STRING'];
		}
	}

	// Duree de validite de l'alea pour les cookies et ce qui s'ensuit.
	if (!defined('_RENOUVELLE_ALEA')) {
		define('_RENOUVELLE_ALEA', 12 * 3600);
	}
	if (!defined('_DUREE_COOKIE_ADMIN')) {
		define('_DUREE_COOKIE_ADMIN', 14 * 24 * 3600);
	}

	// charger les meta si possible et renouveller l'alea au besoin
	// charge aussi effacer_meta et ecrire_meta
	$inc_meta = charger_fonction('meta', 'inc');
	$inc_meta();

	// nombre de repertoires depuis la racine
	// on compare a l'adresse de spip.php : $_SERVER["SCRIPT_NAME"]
	// ou a defaut celle donnee en meta ; (mais si celle-ci est fausse
	// le calcul est faux)
	if (!_DIR_RESTREINT) {
		$GLOBALS['profondeur_url'] = 1;
	} else {
		$uri = isset($_SERVER['REQUEST_URI']) ? explode('?', $_SERVER['REQUEST_URI']) : '';
		$uri_ref = $_SERVER['SCRIPT_NAME'];
		if (
			!$uri_ref
			// si on est appele avec un autre ti, on est sans doute en mutu
			// si jamais c'est de la mutu avec sous rep, on est perdu si on se fie
			// a spip.php qui est a la racine du spip, et vue qu'on sait pas se reperer
			// s'en remettre a l'adresse du site. alea jacta est.
			|| $ti !== _NOM_TEMPORAIRES_INACCESSIBLES
		) {
			if (isset($GLOBALS['meta']['adresse_site'])) {
				$uri_ref = parse_url($GLOBALS['meta']['adresse_site']);
				$uri_ref = ($uri_ref['path'] ?? '') . '/';
			} else {
				$uri_ref = '';
			}
		}
		if (!$uri || !$uri_ref) {
			$GLOBALS['profondeur_url'] = 0;
		} else {
			$GLOBALS['profondeur_url'] = max(0, substr_count($uri[0], '/') - substr_count($uri_ref, '/'));
		}
	}
	// s'il y a un cookie ou PHP_AUTH, initialiser visiteur_session
	if (_FILE_CONNECT) {
		if (
			verifier_visiteur() == '0minirezo'
			// si c'est un admin sans cookie admin, il faut ignorer le cache chemin !
			&& !isset($_COOKIE['spip_admin'])
		) {
			clear_path_cache();
		}
	}
}

/**
 * Complements d'initialisation non critiques pouvant etre realises
 * par les plugins
 */
function spip_initialisation_suite() {
	static $too_late = 0;
	if ($too_late++) {
		return;
	}

	// taille mini des login
	if (!defined('_LOGIN_TROP_COURT')) {
		define('_LOGIN_TROP_COURT', 4);
	}

	// la taille maxi des logos (0 : pas de limite) (pas de define par defaut, ce n'est pas utile)
	#if (!defined('_LOGO_MAX_SIZE')) define('_LOGO_MAX_SIZE', 0); # poids en ko
	#if (!defined('_LOGO_MAX_WIDTH')) define('_LOGO_MAX_WIDTH', 0); # largeur en pixels
	#if (!defined('_LOGO_MAX_HEIGHT')) define('_LOGO_MAX_HEIGHT', 0); # hauteur en pixels

	// la taille maxi des images (0 : pas de limite) (pas de define par defaut, ce n'est pas utile)
	#if (!defined('_DOC_MAX_SIZE')) define('_DOC_MAX_SIZE', 0); # poids en ko
	#if (!defined('_IMG_MAX_SIZE')) define('_IMG_MAX_SIZE', 0); # poids en ko
	#if (!defined('_IMG_MAX_WIDTH')) define('_IMG_MAX_WIDTH', 0); # largeur en pixels
	#if (!defined('_IMG_MAX_HEIGHT')) define('_IMG_MAX_HEIGHT', 0); # hauteur en pixels

	if (!defined('_PASS_LONGUEUR_MINI')) {
		define('_PASS_LONGUEUR_MINI', 6);
	}

	// largeur maximale des images dans l'administration
	if (!defined('_IMG_ADMIN_MAX_WIDTH')) {
		define('_IMG_ADMIN_MAX_WIDTH', 768);
	}

	// Qualite des images calculees automatiquement. C'est un nombre entre 0 et 100
	if (!defined('_IMG_QUALITE')) {
		define('_IMG_QUALITE', 85);
	} # valeur par defaut
	if (!defined('_IMG_GD_QUALITE')) {
		define('_IMG_GD_QUALITE', _IMG_QUALITE);
	} # surcharge pour la lib GD

	if (!defined('_COPIE_LOCALE_MAX_SIZE')) {
		define('_COPIE_LOCALE_MAX_SIZE', 33_554_432);
	} // poids en octet

	// qq chaines standard
	if (!defined('_ACCESS_FILE_NAME')) {
		define('_ACCESS_FILE_NAME', '.htaccess');
	}
	if (!defined('_AUTH_USER_FILE')) {
		define('_AUTH_USER_FILE', '.htpasswd');
	}
	if (!defined('_SPIP_DUMP')) {
		define('_SPIP_DUMP', 'dump@nom_site@@stamp@.xml');
	}
	if (!defined('_CACHE_RUBRIQUES')) {
		/** Fichier cache pour le navigateur de rubrique du bandeau */
		define('_CACHE_RUBRIQUES', _DIR_TMP . 'menu-rubriques-cache.txt');
	}
	if (!defined('_CACHE_RUBRIQUES_MAX')) {
		/** Nombre maxi de rubriques enfants affichées pour chaque rubrique du navigateur de rubrique du bandeau */
		define('_CACHE_RUBRIQUES_MAX', 500);
	}

	if (!defined('_CACHE_CONTEXTES_AJAX_SUR_LONGUEUR')) {
		/**
		 * Basculer les contextes ajax en fichier si la longueur d’url est trop grande
		 * @var int Nombre de caractères */
		define('_CACHE_CONTEXTES_AJAX_SUR_LONGUEUR', 2000);
	}

	if (!defined('_EXTENSION_SQUELETTES')) {
		define('_EXTENSION_SQUELETTES', 'html');
	}

	if (!defined('_DOCTYPE_ECRIRE')) {
		/** Définit le doctype de l’espace privé */
		define('_DOCTYPE_ECRIRE', "<!DOCTYPE html>\n");
	}
	if (!defined('_DOCTYPE_AIDE')) {
		/** Définit le doctype de l’aide en ligne */
		define(
			'_DOCTYPE_AIDE',
			"<!DOCTYPE html PUBLIC '-//W3C//DTD HTML 4.01 Frameset//EN' 'http://www.w3.org/TR/1999/REC-html401-19991224/frameset.dtd'>"
		);
	}

	if (!defined('_SPIP_SCRIPT')) {
		/** L'adresse de base du site ; on peut mettre '' si la racine est gerée par
		 * le script de l'espace public, alias index.php */
		define('_SPIP_SCRIPT', 'spip.php');
	}
	if (!defined('_SPIP_PAGE')) {
		/** Argument page, personalisable en cas de conflit avec un autre script */
		define('_SPIP_PAGE', 'page');
	}

	// le script de l'espace prive
	// Mettre a "index.php" si DirectoryIndex ne le fait pas ou pb connexes:
	// les anciens IIS n'acceptent pas les POST sur ecrire/ (#419)
	// meme pb sur thttpd cf. https://forum.spip.net/fr_184153.html
	if (!defined('_SPIP_ECRIRE_SCRIPT')) {
		if (!empty($_SERVER['SERVER_SOFTWARE']) && preg_match(',IIS|thttpd,', $_SERVER['SERVER_SOFTWARE'])) {
			define('_SPIP_ECRIRE_SCRIPT', 'index.php');
		} else {
			define('_SPIP_ECRIRE_SCRIPT', '');
		}
	}

	if (!defined('_SPIP_AJAX')) {
		define('_SPIP_AJAX', ((!isset($_COOKIE['spip_accepte_ajax']))
			? 1
			: (($_COOKIE['spip_accepte_ajax'] != -1) ? 1 : 0)));
	}

	// La requete est-elle en ajax ?
	if (!defined('_AJAX')) {
		define(
			'_AJAX',
			(
				isset($_SERVER['HTTP_X_REQUESTED_WITH']) # ajax jQuery
				|| !empty($_REQUEST['var_ajax_redir']) # redirection 302 apres ajax jQuer
				|| !empty($_REQUEST['var_ajaxcharset']) # compat ascendante pour plugins
				|| !empty($_REQUEST['var_ajax']) # forms ajax & inclure ajax de spip
			)
			&& empty($_REQUEST['var_noajax']) # horrible exception, car c'est pas parce que la requete est ajax jquery qu'il faut tuer tous les formulaires ajax qu'elle contient
		);
	}

	# nombre de pixels maxi pour calcul de la vignette avec gd
	# au dela de 5500000 on considere que php n'est pas limite en memoire pour cette operation
	# les configurations limitees en memoire ont un seuil plutot vers 1MPixel
	if (!defined('_IMG_GD_MAX_PIXELS')) {
		define(
			'_IMG_GD_MAX_PIXELS',
			(isset($GLOBALS['meta']['max_taille_vignettes']) && $GLOBALS['meta']['max_taille_vignettes'])
			? $GLOBALS['meta']['max_taille_vignettes']
			: 0
		);
	}

	// Protocoles a normaliser dans les chaines de langues
	if (!defined('_PROTOCOLES_STD')) {
		define('_PROTOCOLES_STD', 'http|https|ftp|mailto|webcal');
	}

	init_var_mode();
}

/**
 * Repérer les variables d'URL spéciales `var_mode` qui conditionnent
 * la validité du cache ou certains affichages spéciaux.
 *
 * Le paramètre d'URL `var_mode` permet de
 * modifier la pérennité du cache, recalculer des urls
 * ou d'autres petit caches (trouver_table, css et js compactes ...),
 * d'afficher un écran de débug ou des traductions non réalisées.
 *
 * En fonction de ces paramètres dans l'URL appelante, on définit
 * da constante `_VAR_MODE` qui servira ensuite à SPIP.
 *
 * Le paramètre `var_mode` accepte ces valeurs :
 *
 * - `calcul` : force un calcul du cache de la page (sans forcément recompiler les squelettes)
 * - `recalcul` : force un calcul du cache de la page en recompilant au préabable les squelettes
 * - `inclure` : modifie l'affichage en ajoutant visuellement le nom de toutes les inclusions qu'elle contient
 * - `debug` :  modifie l'affichage activant le mode "debug"
 * - `preview` : modifie l'affichage en ajoutant aux boucles les éléments prévisualisables
 * - `traduction` : modifie l'affichage en affichant des informations sur les chaînes de langues utilisées
 * - `urls` : permet de recalculer les URLs des objets appelés dans la page par les balises `#URL_xx`
 * - `images` : permet de recalculer les filtres d'images utilisés dans la page
 *
 * En dehors des modes `calcul` et `recalcul`, une autorisation 'previsualiser' ou 'debug' est testée.
 *
 * @note
 *     Il éxiste également le paramètre `var_profile` qui modifie l'affichage pour incruster
 *     le nombre de requêtes SQL utilisées dans la page, qui peut se compléter avec le paramètre
 * `   var_mode` (calcul ou recalcul).
 */
function init_var_mode() {
	static $done = false;
	if (!$done) {
		if (isset($_GET['var_mode'])) {
			$var_mode = explode(',', $_GET['var_mode']);
			// tout le monde peut calcul/recalcul
			if (!defined('_VAR_MODE')) {
				if (in_array('recalcul', $var_mode)) {
					define('_VAR_MODE', 'recalcul');
				} elseif (in_array('calcul', $var_mode)) {
					define('_VAR_MODE', 'calcul');
				}
			}
			$var_mode = array_diff($var_mode, ['calcul', 'recalcul']);
			if ($var_mode) {
				include_spip('inc/autoriser');
				// autoriser preview si preview seulement, et sinon autoriser debug
				if (
					autoriser(($_GET['var_mode'] == 'preview') ? 'previsualiser' : 'debug')
				) {
					if (in_array('traduction', $var_mode)) {
						// forcer le calcul pour passer dans traduire
						if (!defined('_VAR_MODE')) {
							define('_VAR_MODE', 'calcul');
						}
						// et ne pas enregistrer de cache pour ne pas trainer les surlignages sur d'autres pages
						if (!defined('_VAR_NOCACHE')) {
							define('_VAR_NOCACHE', true);
						}
						$var_mode = array_diff($var_mode, ['traduction']);
					}
					if (in_array('preview', $var_mode)) {
						// basculer sur les criteres de preview dans les boucles
						if (!defined('_VAR_PREVIEW')) {
							define('_VAR_PREVIEW', true);
						}
						// forcer le calcul
						if (!defined('_VAR_MODE')) {
							define('_VAR_MODE', 'calcul');
						}
						// et ne pas enregistrer de cache
						if (!defined('_VAR_NOCACHE')) {
							define('_VAR_NOCACHE', true);
						}
						$var_mode = array_diff($var_mode, ['preview']);
					}
					if (in_array('inclure', $var_mode)) {
						// forcer le compilo et ignorer les caches existants
						if (!defined('_VAR_MODE')) {
							define('_VAR_MODE', 'calcul');
						}
						if (!defined('_VAR_INCLURE')) {
							define('_VAR_INCLURE', true);
						}
						// et ne pas enregistrer de cache
						if (!defined('_VAR_NOCACHE')) {
							define('_VAR_NOCACHE', true);
						}
						$var_mode = array_diff($var_mode, ['inclure']);
					}
					if (in_array('urls', $var_mode)) {
						// forcer le compilo et ignorer les caches existants
						if (!defined('_VAR_MODE')) {
							define('_VAR_MODE', 'calcul');
						}
						if (!defined('_VAR_URLS')) {
							define('_VAR_URLS', true);
						}
						$var_mode = array_diff($var_mode, ['urls']);
					}
					if (in_array('images', $var_mode)) {
						// forcer le compilo et ignorer les caches existants
						if (!defined('_VAR_MODE')) {
							define('_VAR_MODE', 'calcul');
						}
						// indiquer qu'on doit recalculer les images
						if (!defined('_VAR_IMAGES')) {
							define('_VAR_IMAGES', true);
						}
						$var_mode = array_diff($var_mode, ['images']);
					}
					if (in_array('debug', $var_mode)) {
						if (!defined('_VAR_MODE')) {
							define('_VAR_MODE', 'debug');
						}
						// et ne pas enregistrer de cache
						if (!defined('_VAR_NOCACHE')) {
							define('_VAR_NOCACHE', true);
						}
						$var_mode = array_diff($var_mode, ['debug']);
					}
					if (count($var_mode) && !defined('_VAR_MODE')) {
						define('_VAR_MODE', reset($var_mode));
					}
					if (isset($GLOBALS['visiteur_session']['nom'])) {
						spip_logger()->info($GLOBALS['visiteur_session']['nom'] . ' ' . _VAR_MODE);
					}
				} // pas autorise ?
				else {
					// si on n'est pas connecte on se redirige, si on est pas en cli et pas deja en train de se loger
					if (
						!$GLOBALS['visiteur_session']
						&& !empty($_SERVER['HTTP_HOST'])
						&& !empty($_SERVER['REQUEST_METHOD'])
						&& $_SERVER['REQUEST_METHOD'] === 'GET'
					) {
						$self = self('&', true);
						if (!str_contains($self, 'page=login')) {
							include_spip('inc/headers');
							$redirect = parametre_url(self('&', true), 'var_mode', $_GET['var_mode'], '&');
							redirige_par_entete(generer_url_public('login', 'url=' . rawurlencode($redirect), true));
						}
					}
					// sinon tant pis
				}
			}
		}
		if (!defined('_VAR_MODE')) {
			/**
			 * Indique le mode de calcul ou d'affichage de la page.
			 * @see init_var_mode()
			 */
			define('_VAR_MODE', false);
		}
		$done = true;
	}
}
