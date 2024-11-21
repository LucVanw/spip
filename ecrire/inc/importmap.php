<?php

define('_MARQUEUR_POST_IMPORTMAP', '<!--.importmap-->');
define('_MARQUEUR_POST_INIT', '<!--.initjs-->');

/**
 * Cherche un fichier module.js (statique ou dynamique)
 * présent dans un dossier javascript/ du path,
 * ou bien copie localement une ressource distante,
 * et renvoie son url locale
 *
 * @uses find_in_path
 * @uses copie_locale
 * @uses produire_fond_statique
 * @uses importmap_contexte_produire_fond_statique
 *
 * @param string $fichier
 *     Nom du fichier
 * @return string
 *     URL absolue du fichier local
 *     sinon chaîne vide.
 **/
function chemin_import_js($fichier) {

	$chemin = '';
	$fichier = supprimer_timestamp($fichier); //si distant

	if (
		!strlen($fichier)
		or (!preg_match(',[.]js(.html)?$,i', $fichier))
	) {
		return '';
	}

	if (tester_url_absolue($fichier)) {
		$chemin = copie_locale($fichier);
	} else {
		$chemin = test_espace_prive()
			? find_in_path($fichier, _DIR_JAVASCRIPT)
			: sinon(find_in_path($fichier, _JAVASCRIPT), find_in_path($fichier, _DIR_JAVASCRIPT));

		// si toujours rien on tente depuis le répertoire lib/
		if (!$chemin) {
			$chemin = find_in_path($fichier, _DIR_LIB);
		}
		// cas d'un fichier dynamique
		if (
			$chemin
			and (substr($fichier, -5) === '.html')
		) {
			$contexte = importmap_contexte_produire_fond_statique();
			// NB : $chemin a déjà été trouvé, mais c'est $fichier que l'on passe à
			// à la fonction, car cela renviendrait à faire une double passe de find_in_path() /!\
			$skel = substr($fichier, 0, -5);
			$chemin = produire_fond_statique(_JAVASCRIPT . $skel, $contexte);
		}
	}

	if (
		// version minifiée disponible dans le même dossier ?
		$chemin
		and !defined('_INTERDIRE_COMPACTE_HEAD')
		and (
				(
					!test_espace_prive()
					and !empty($GLOBALS['meta']['auto_compress_js'])
					and $GLOBALS['meta']['auto_compress_js'] == 'oui'
				)
				or (
					test_espace_prive()
					and !defined('_INTERDIRE_COMPACTE_HEAD_ECRIRE')
				)
		)
		and ($f = preg_replace(',\.(js)$,i', '.min.\\1', $chemin))
		and file_exists($f)
	) {
		$chemin = $f;
	}

	$chemin = protocole_implicite(url_absolue(timestamp($chemin))) ;

	return $chemin;
}


/**
 * Liste des paramètres utiles à la compilation des fonds dynamiques
 * pour les fichiers `_initjs` ou la balise `#IMPORT_JS{}`
 * 
 * @param  [array]
 * @return [array]
 */
function importmap_contexte_produire_fond_statique($contexte = []) {

	return array_merge(
		[
			'prive' => test_espace_prive(),
			'lang' => $GLOBALS['spip_lang']
		],
		$contexte
	);
}


/**
 * Insertion de la balise <script type="importmap"></script>,
 * au plus tôt dans le <head>, côté privé, et côté public,
 * pour en faire bénéficier les <script type="module"></script>
 *
 * Insertion du module d'initialisation, côté privé, et côté public
 *
 * @uses importmap_contexte_produire_fond_statique
 * 
 * @param  [string] $flux
 * @return [string] $flux
 */
function importmap_insert_head($flux) {

	$init_loader = test_espace_prive()
		? 'prive/' . _JAVASCRIPT . '_init.js'
		: _JAVASCRIPT . '_init.js'
	;

	$contexte = importmap_contexte_produire_fond_statique();

	if (_request('var_mode') === 'debug_js') {
		if ($init_loader = recuperer_fond($init_loader, $contexte)) {
			$flux = "\n<script type=\"module\">\n$init_loader\n</script>" . _MARQUEUR_POST_INIT . "\n" . $flux;
		}
	} else {
		if ($init_loader = timestamp(produire_fond_statique($init_loader, $contexte))) {
			$flux = "\n<script type=\"module\" src=\"$init_loader\"></script>" . _MARQUEUR_POST_INIT . "\n" . $flux;
		}
	}

	if (
		$importmap =
			recuperer_fond(
				'prive/squelettes/inclure/importmap'
			)
	) {
		$flux = "\n" . $importmap . _MARQUEUR_POST_IMPORTMAP . "\n" . $flux; // le plus haut possible
	}

	return $flux;
}