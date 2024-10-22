<?php

use function SpipLeague\Component\Kernel\param;

/** Diverses fonctions */

/**
 * Vérifie la présence d'un plugin actif, identifié par son préfixe
 *
 * @param string $plugin
 * @return bool
 */
function test_plugin_actif($plugin) {
	return ($plugin && defined('_DIR_PLUGIN_' . strtoupper($plugin))) ? true : false;
}

/**
 * Retourne un joli chemin de répertoire
 *
 * Pour afficher `ecrire/action/` au lieu de `action/` dans les messages
 * ou `tmp/` au lieu de `../tmp/`
 *
 * @param string $rep Chemin d’un répertoire
 */
function joli_repertoire(?string $rep): string {
	if ($rep === '' || $rep === null) {
		return '';
	}
	$a = substr($rep, 0, 1);
	if ($a <> '.' && $a <> '/') {
		$rep = (_DIR_RESTREINT ? '' : param('spip.dirs.core')) . $rep;
	}
	$rep = preg_replace(',(^\.\.\/),', '', $rep);

	return $rep;
}

/**
 * Débute ou arrête un chronomètre et retourne sa valeur
 *
 * On exécute 2 fois la fonction, la première fois pour démarrer le chrono,
 * la seconde fois pour l’arrêter et récupérer la valeur
 *
 * @example
 *     ```
 *     spip_timer('papoter');
 *     // actions
 *     $duree = spip_timer('papoter');
 *     ```
 *
 * @param string $t
 *     Nom du chronomètre
 * @param bool $raw
 *     - false : retour en texte humainement lisible
 *     - true : retour en millisecondes
 * @return float|int|string|void
 */
function spip_timer($t = 'rien', $raw = false) {
	static $time;
	$a = time();
	$b = microtime();
	// microtime peut contenir les microsecondes et le temps
	$b = explode(' ', $b);
	if (count($b) == 2) {
		$a = end($b);
	} // plus precis !
	$b = reset($b);
	if (!isset($time[$t])) {
		$time[$t] = $a + $b;
	} else {
		$p = ($a + $b - $time[$t]) * 1000;
		unset($time[$t]);
		#			echo "'$p'";exit;
		if ($raw) {
			return $p;
		}
		if ($p < 1000) {
			$s = '';
		} else {
			$s = sprintf('%d ', $x = floor($p / 1000));
			$p -= ($x * 1000);
		}

		return $s . sprintf($s ? '%07.3f ms' : '%.3f ms', $p);
	}
}

// Renvoie False si un fichier n'est pas plus vieux que $duree secondes,
// sinon renvoie True et le date sauf si ca n'est pas souhaite
function spip_touch($fichier, $duree = 0, $touch = true) {
	if ($duree) {
		clearstatcache();
		if (($f = @filemtime($fichier)) && $f >= time() - $duree) {
			return false;
		}
	}
	if ($touch !== false) {
		if (!@touch($fichier)) {
			spip_unlink($fichier);
			@touch($fichier);
		}
		@chmod($fichier, _SPIP_CHMOD & ~0111);
	}

	return true;
}

/**
 * Produit une balise `<script>` valide
 *
 * @example
 *     ```
 *     echo http_script('alert("ok");');
 *     echo http_script('','js/jquery.js');
 *     ```
 *
 * @param string $script
 *     Code source du script
 * @param string $src
 *     Permet de faire appel à un fichier javascript distant
 * @param string $noscript
 *     Contenu de la balise  `<noscript>`
 * @return string
 *     Balise HTML `<script>` et son contenu
 */
function http_script($script, $src = '', $noscript = '') {
	static $done = [];

	if ($src && !isset($done[$src])) {
		$done[$src] = true;
		$src = find_in_path($src, _JAVASCRIPT);
		$src = " src='$src'";
	} else {
		$src = '';
	}
	if ($script) {
		$script = preg_replace(',</([^>]*)>,', '<\/\1>', $script);
	}
	if ($noscript) {
		$noscript = "<noscript>\n\t$noscript\n</noscript>\n";
	}

	return ($src || $script || $noscript)
		? "<script$src>$script</script>$noscript"
		: '';
}

/**
 * Sécurise du texte à écrire dans du PHP ou du Javascript.
 *
 * Transforme n'importe quel texte en une chaîne utilisable
 * en PHP ou Javascript en toute sécurité, à l'intérieur d'apostrophes
 * simples (`'` uniquement ; pas `"`)
 *
 * Utile particulièrement en filtre dans un squelettes
 * pour écrire un contenu dans une variable JS ou PHP.
 *
 * Échappe les apostrophes (') du contenu transmis.
 *
 * @link https://www.spip.net/4281
 * @example
 *     PHP dans un squelette
 *     ```
 *     $x = '[(#TEXTE|texte_script)]';
 *     ```
 *
 *     JS dans un squelette (transmettre une chaîne de langue)
 *     ```
 *     $x = '<:afficher_calendrier|texte_script:>';
 *     ```
 *
 * @filtre
 * @param string|null $texte
 *     texte à échapper
 * @return string
 *     texte échappé
 */
function texte_script(?string $texte): string {
	if ($texte === null || $texte === '') {
		return '';
	}
	return str_replace('\'', '\\\'', str_replace('\\', '\\\\', $texte));
}

/**
 * Tester qu'une variable d'environnement est active
 *
 * Sur certains serveurs, la valeur 'Off' tient lieu de false dans certaines
 * variables d'environnement comme `$_SERVER['HTTPS']` ou `ini_get('display_errors')`
 *
 * @param string|bool $truc
 *     La valeur de la variable d'environnement
 * @return bool
 *     true si la valeur est considérée active ; false sinon.
 */
function test_valeur_serveur($truc) {
	if (!$truc) {
		return false;
	}

	return strtolower($truc) !== 'off';
}

/**
 * Page `exec=info` : retourne le contenu de la fonction php `phpinfo()`
 *
 * Si l’utiliseur est un webmestre.
 */
function exec_info_dist() {

	include_spip('inc/autoriser');
	if (autoriser('phpinfos')) {
		$cookies_masques = ['spip_session', 'PHPSESSID'];
		$cookies_backup = [];
		$server_backup = ['HTTP_COOKIE' => $_SERVER['HTTP_COOKIE'] ?? []];
		$env_backup = ['HTTP_COOKIE' => $_ENV['HTTP_COOKIE'] ?? []];
		$mask = '******************************';
		foreach ($cookies_masques as $k) {
			if (!empty($_COOKIE[$k])) {
				$cookies_backup[$k] = $_COOKIE[$k];
				$_SERVER['HTTP_COOKIE'] = str_replace("$k=" . $_COOKIE[$k], "$k=$mask", $_SERVER['HTTP_COOKIE'] ?? []);
				$_ENV['HTTP_COOKIE'] = str_replace("$k=" . $_COOKIE[$k], "$k=$mask", $_ENV['HTTP_COOKIE'] ?? []);
				$_COOKIE[$k] = $mask;
			}
		}
		phpinfo();
		foreach ($cookies_backup as $k => $v) {
			$_COOKIE[$k] = $v;
		}
		foreach ($server_backup as $k => $v) {
			$_SERVER[$k] = $v;
		}
		foreach ($env_backup as $k => $v) {
			$_ENV[$k] = $v;
		}
	} else {
		include_spip('inc/filtres');
		sinon_interdire_acces();
	}
}

/**
 * Indique si le code HTML5 est permis sur le site public
 *
 * @return bool
 *     true si la constante _VERSION_HTML n'est pas définie ou égale à html5
 */
function html5_permis() {
	return !defined('_VERSION_HTML') || _VERSION_HTML !== 'html4';
}

/**
 * Lister les formats image acceptes par les lib et fonctions images
 */
function formats_image_acceptables(?bool $gd = null, bool $svg_allowed = true): array {
	$formats = null;
	if ($gd !== null) {
		$config = ($gd ? 'gd_formats' : 'formats_graphiques');
		if (isset($GLOBALS['meta'][$config])) {
			$formats = $GLOBALS['meta'][$config];
			$formats = explode(',', $formats);
			$formats = array_filter($formats);
			$formats = array_map('trim', $formats);
		}
	}
	if ($formats === null) {
		include_spip('inc/filtres_images_lib_mini');
		$formats = _image_extensions_acceptees_en_entree();
	}

	if ($svg_allowed) {
		if (!in_array('svg', $formats)) {
			$formats[] = 'svg';
		}
	} else {
		$formats = array_diff($formats, ['svg']);
	}
	return $formats;
}

/**
 * Extension de la fonction getimagesize pour supporter aussi les images SVG
 * @param string $fichier
 * @return array|bool
 */
function spip_getimagesize($fichier) {
	if (file_exists($fichier) && ($imagesize = @getimagesize($fichier))) {
		include_spip('inc/exif');

		// Si l'image possède un EXIF d'orientation et que ce dernier correspond à un mode portrait,
		// alors on inverse les dimensions renvoyées.
		if (
			($orientation = exif_obtenir_orientation($fichier))
			&& exif_determiner_si_portrait($orientation)
		) {
			$x = $imagesize[0];
			$imagesize[0] = $imagesize[1];
			$imagesize[1] = $x;

		}

		return $imagesize;
	}

	include_spip('inc/svg');
	if ($attrs = svg_lire_attributs($fichier)) {
		[$width, $height, $viewbox] = svg_getimagesize_from_attr($attrs);
		$imagesize = [
			$width,
			$height,
			IMAGETYPE_SVG,
			"width=\"{$width}\" height=\"{$height}\"",
			'mime' => 'image/svg+xml',
		];
		return $imagesize;
	}

	return false;
}

/**
 * Poser une alerte qui sera affiche aux auteurs de bon statut ('' = tous)
 * au prochain passage dans l'espace prive
 * chaque alerte doit avoir un nom pour eviter duplication a chaque hit
 * les alertes affichees une fois sont effacees
 *
 * @param string $nom
 * @param string $message
 * @param string $statut
 */
function avertir_auteurs($nom, $message, $statut = '') {
	$alertes = $GLOBALS['meta']['message_alertes_auteurs'];
	if (
		!$alertes || !is_array($alertes = unserialize($alertes))
	) {
		$alertes = [];
	}

	if (!isset($alertes[$statut])) {
		$alertes[$statut] = [];
	}
	$alertes[$statut][$nom] = $message;
	ecrire_meta('message_alertes_auteurs', serialize($alertes));
}

/**
 * Compare 2 numéros de version entre elles.
 *
 * Cette fonction est identique (arguments et retours) a la fonction PHP
 * version_compare() qu'elle appelle. Cependant, cette fonction reformate
 * les numeros de versions pour ameliorer certains usages dans SPIP ou bugs
 * dans PHP. On permet ainsi de comparer 3.0.4 à 3.0.* par exemple.
 *
 * @param string $v1
 *    Numero de version servant de base a la comparaison.
 *    Ce numero ne peut pas comporter d'etoile.
 * @param string $v2
 *    Numero de version a comparer.
 *    Il peut posseder des etoiles tel que 3.0.*
 * @param string $op
 *    Un operateur eventuel (<, >, <=, >=, =, == ...)
 * @return int|bool
 *    Sans operateur : int. -1 pour inferieur, 0 pour egal, 1 pour superieur
 *    Avec operateur : bool.
 */
function spip_version_compare($v1, $v2, $op = null) {
	$v1 = strtolower(preg_replace(',([0-9])[\s.-]?(dev|alpha|a|beta|b|rc|pl|p),i', '\\1.\\2', $v1));
	$v2 = strtolower(preg_replace(',([0-9])[\s.-]?(dev|alpha|a|beta|b|rc|pl|p),i', '\\1.\\2', $v2));
	$v1 = str_replace('rc', 'RC', $v1); // certaines versions de PHP ne comprennent RC qu'en majuscule
	$v2 = str_replace('rc', 'RC', $v2); // certaines versions de PHP ne comprennent RC qu'en majuscule

	$v1 = explode('.', $v1);
	$v2 = explode('.', $v2);
	// $v1 est toujours une version, donc sans etoile
	while (count($v1) < count($v2)) {
		$v1[] = '0';
	}

	// $v2 peut etre une borne, donc accepte l'etoile
	$etoile = false;
	foreach ($v1 as $k => $v) {
		if (!isset($v2[$k])) {
			$v2[] = ($etoile && (is_numeric($v) || $v == 'pl' || $v == 'p')) ? $v : '0';
		} else {
			if ($v2[$k] == '*') {
				$etoile = true;
				$v2[$k] = $v;
			}
		}
	}
	$v1 = implode('.', $v1);
	$v2 = implode('.', $v2);

	return $op ? version_compare($v1, $v2, $op) : version_compare($v1, $v2);
}
