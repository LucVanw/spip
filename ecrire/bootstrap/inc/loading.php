<?php

use Spip\Afficher\Minipage\Admin as MinipageAdmin;

use function SpipLeague\Component\Kernel\app;

/**
 * Cherche une fonction surchargeable et en retourne le nom exact,
 * après avoir chargé le fichier la contenant si nécessaire.
 *
 * Charge un fichier (suivant les chemins connus) et retourne si elle existe
 * le nom de la fonction homonyme `$dir_$nom`, ou suffixé `$dir_$nom_dist`
 *
 * Peut être appelé plusieurs fois, donc optimisé.
 *
 * @api
 * @uses include_spip() Pour charger le fichier
 * @example
 *     ```
 *     $envoyer_mail = charger_fonction('envoyer_mail', 'inc');
 *     $envoyer_mail($email, $sujet, $texte);
 *     ```
 *
 * @param string $nom
 *     Nom de la fonction (et du fichier)
 * @param string $dossier
 *     Nom du dossier conteneur
 * @param bool $continue
 *     true pour ne pas râler si la fonction n'est pas trouvée
 * @return string
 *     Nom de la fonction, ou false.
 */
function charger_fonction($nom, $dossier = 'exec', $continue = false) {
	static $echecs = [];

	if (strlen($dossier) && !str_ends_with($dossier, '/')) {
		$dossier .= '/';
	}
	$f = str_replace('/', '_', $dossier) . $nom;

	if (function_exists($f)) {
		return $f;
	}
	if (function_exists($g = $f . '_dist')) {
		return $g;
	}

	if (isset($echecs[$f])) {
		return $echecs[$f];
	}
	// Sinon charger le fichier de declaration si plausible

	if (!preg_match(',^\w+$,', $f)) {
		if ($continue) {
			return false;
		} //appel interne, on passe
		$minipage = new MinipageAdmin();
		echo $minipage->page();
		exit;
	}

	// passer en minuscules (cf les balises de formulaires)
	// et inclure le fichier
	if (
		!($inc = include_spip($dossier . ($d = strtolower($nom))))
		&& strlen(dirname($dossier))
		&& dirname($dossier) != '.'
	) {
		include_spip(substr($dossier, 0, -1));
	}
	if (function_exists($f)) {
		return $f;
	}
	if (function_exists($g)) {
		return $g;
	}

	if ($continue) {
		return $echecs[$f] = false;
	}

	// Echec : message d'erreur
	spip_logger()
		->info("fonction $nom ($f ou $g) indisponible" . ($inc ? '' : " (fichier $d absent de $dossier)"));

	include_spip('inc/filtres_mini');
	$minipage = new MinipageAdmin();
	echo $minipage->page(
		$inc ?
			_T('fonction_introuvable', ['fonction' => '<code>' . spip_htmlentities($f) . '</code>'])
			. '<br />'
			. _T('fonction_introuvable', ['fonction' => '<code>' . spip_htmlentities($g) . '</code>'])
			:
			_T('fichier_introuvable', ['fichier' => '<code>' . spip_htmlentities($d) . '</code>']),
		['titre' => _T('forum_titre_erreur'), 'all_inline' => true, 'status' => 404]
	);
	exit;
}

/**
 * Inclusion unique avec verification d'existence du fichier + log en crash sinon
 *
 * @param string $file
 * @return bool
 */
function include_once_check($file) {
	if (file_exists($file)) {
		include_once $file;

		return true;
	}
	$crash = (isset($GLOBALS['meta']['message_crash_plugins']) ? unserialize(
		$GLOBALS['meta']['message_crash_plugins']
	) : '');
	$crash = ($crash ?: []);
	$crash[$file] = true;
	ecrire_meta('message_crash_plugins', serialize($crash));

	return false;
}

/**
 * Inclut un fichier PHP (en le cherchant dans les chemins)
 *
 * @api
 * @uses find_in_path()
 * @example
 *     ```
 *     include_spip('inc/texte');
 *     ```
 *
 * @param string $f
 *     Nom du fichier (sans l'extension)
 * @param bool $include
 *     - true pour inclure le fichier,
 *     - false ne fait que le chercher
 * @return string|bool
 *     - false : fichier introuvable
 *     - string : chemin du fichier trouvé
 */
function include_spip($f, $include = true) {
	return find_in_path($f . '.php', '', $include);
}

/**
 * Requiert un fichier PHP (en le cherchant dans les chemins)
 *
 * @uses find_in_path()
 * @see  include_spip()
 * @example
 *     ```
 *     require_spip('inc/texte');
 *     ```
 *
 * @param string $f
 *     Nom du fichier (sans l'extension)
 * @return string|bool
 *     - false : fichier introuvable
 *     - string : chemin du fichier trouvé
 */
function require_spip($f) {
	return find_in_path($f . '.php', '', 'required');
}

/**
 * Raccourci pour inclure mes_fonctions.php et tous les fichiers _fonctions.php des plugin
 * quand on a besoin dans le PHP de filtres/fonctions qui y sont definis
 */
function include_fichiers_fonctions() {
	static $done = false;
	if (!$done) {
		include_spip('inc/lang');

		// NB: mes_fonctions peut initialiser $dossier_squelettes (old-style)
		// donc il faut l'inclure "en globals"
		if ($f = find_in_path('mes_fonctions.php')) {
			global $dossier_squelettes;
			include_once(app()->getCwd() . DIRECTORY_SEPARATOR . $f);
		}

		if (@is_readable(_CACHE_PLUGINS_FCT)) {
			// chargement optimise precompile
			include_once(_CACHE_PLUGINS_FCT);
		}
		if (test_espace_prive()) {
			include_spip('inc/filtres_ecrire');
		}
		include_spip('public/fonctions'); // charger les fichiers fonctions associes aux criteres, balises..
		$done = true;
	}
}

/**
 * Charger la fonction de gestion des urls si elle existe
 * @param string $quoi
 *     'page' 'objet' 'decoder' ou objet spip pour lequel on cherche la fonction url par defaut (si type==='defaut')
 * @param string $type
 * 		type des urls (par defaut la meta type_urls) ou 'defaut' pour trouver la fonction par defaut d'un type d'objet
 * @return string
 */
function charger_fonction_url(string $quoi, string $type = '') {
	if ($type === 'defaut') {
		$objet = objet_type($quoi);
		if (
			($f = charger_fonction('generer_' . $objet . '_url', 'urls', true))
			|| ($f = charger_fonction('generer_url_' . $objet, 'urls', true)) // deprecated
		) {
			return $f;
		}
		return '';
	}

	$url_type = $type;
	if (!$url_type) {
		$url_type = $GLOBALS['type_urls'] ?? $GLOBALS['meta']['type_urls'] ?? 'page'; // sinon type "page" par défaut
	}

	// inclure le module d'url
	include_spip('urls/' . $url_type);

	switch ($quoi) {
		case 'page':
			if (
				function_exists($f = "urls_{$url_type}_generer_url_page")
				|| function_exists($f .= '_dist')
				// ou une fonction custom utilisateur independante du type d'url
				|| function_exists($f = 'generer_url_page')
				|| function_exists($f .= '_dist')
			) {
				return $f;
			}
			// pas de compat ancienne version ici, c'est une nouvelle feature
			return '';
		case 'objet':
		case 'decoder':
		default:
			$fquoi = ($quoi === 'objet' ? 'generer_url_objet' : 'decoder_url');
			if (
				function_exists($f = "urls_{$url_type}_{$fquoi}")
				|| function_exists($f .= '_dist')
			) {
				return $f;
			}
			// est-ce qu'on a une ancienne fonction urls_xxx_dist() ?
			// c'est un ancien module d'url, on appelle l'ancienne fonction qui fait tout
			if ($f = charger_fonction($url_type, 'urls', true)) {
				return $f;
			}
			// sinon on se rabat sur les urls page si ce n'est pas un type demande explicitement
			if (!$type && $url_type !== 'page') {
				return charger_fonction_url($quoi, 'page');
			}
			// si on arrive ici c'est qu'il manque une fonction de traitement : lever une erreur
			throw new \Exception(sprintf('Missing a url function for type %s : %s', $url_type, "urls_{$url_type}_{$fquoi}()"));
	}
}

/**
 * Trouve un squelette dans le repertoire modeles/
 *
 * @return string
 */
function trouve_modele($nom) {
	return trouver_fond($nom, 'modeles/');
}

/**
 * Trouver un squelette dans le chemin
 * on peut specifier un sous-dossier dans $dir
 * si $pathinfo est a true, retourne un tableau avec
 * les composantes du fichier trouve
 * + le chemin complet sans son extension dans fond
 *
 * @param string $nom
 * @param string $dir
 * @param bool $pathinfo
 * @return array|string
 */
function trouver_fond($nom, $dir = '', $pathinfo = false) {
	$f = find_in_path($nom . '.' . _EXTENSION_SQUELETTES, $dir ? rtrim($dir, '/') . '/' : '');
	if (!$pathinfo) {
		return $f;
	}
	// renvoyer un tableau detaille si $pathinfo==true
	if ($f === null) {
		return [
			'extension' => _EXTENSION_SQUELETTES,
			'filename' => '',
			'fond' => '',
		];
	}
	$p = pathinfo($f);
	if (!isset($p['extension']) || !$p['extension']) {
		$p['extension'] = _EXTENSION_SQUELETTES;
	}
	if (!isset($p['extension']) || !$p['filename']) {
		$p['filename'] = ($p['basename'] ? substr($p['basename'], 0, -strlen($p['extension']) - 1) : '');
	}
	$p['fond'] = ($f ? substr($f, 0, -strlen($p['extension']) - 1) : '');

	return $p;
}

/**
 * Retourne un lien vers une aide
 *
 * Aide, aussi depuis l'espace privé à présent.
 * Surchargeable mais pas d'erreur fatale si indisponible.
 *
 * @param string $aide
 *    Cle d'identification de l'aide desiree
 * @param bool $distante
 *    Generer une url locale (par defaut)
 *    ou une url distante [directement sur spip.net]
 * @return
 *    Lien sur une icone d'aide
 */
function aider($aide = '', $distante = false) {
	$aider = charger_fonction('aide', 'inc', true);

	return $aider ? $aider($aide, '', [], $distante) : '';
}

/**
 * Teste, pour un nom de page de l'espace privé, s'il est possible
 * de générer son contenu.
 *
 * Dans ce cas, on retourne la fonction d'exécution correspondante à utiliser
 * (du répertoire `ecrire/exec`). Deux cas particuliers et prioritaires :
 * `fond` est retourné si des squelettes existent.
 *
 * - `fond` : pour des squelettes de `prive/squelettes/contenu`
 *          ou pour des objets éditoriaux dont les squelettes seront échaffaudés
 *
 * @param string $nom
 *     Nom de la page
 * @return string
 *     Nom de l'exec, sinon chaîne vide.
 */
function tester_url_ecrire($nom) {
	static $exec = [];
	if (isset($exec[$nom])) {
		return $exec[$nom];
	}
	// tester si c'est une page en squelette
	if (trouver_fond($nom, 'prive/squelettes/contenu/')) {
		return $exec[$nom] = 'fond';
	} // echafaudage d'un fond !
	elseif (include_spip('public/styliser_par_z') && z_echafaudable($nom)) {
		return $exec[$nom] = 'fond';
	}
	// attention, il ne faut pas inclure l'exec ici
	// car sinon #URL_ECRIRE provoque des inclusions
	// et des define intrusifs potentiels
	return $exec[$nom] = ((find_in_path("{$nom}.php", 'exec/') || charger_fonction($nom, 'exec', true)) ? $nom : '');
}
