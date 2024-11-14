<?php

if (!defined('_ECRIRE_INC_VERSION')) {
    return;
}

/**
 * Compile la balise `#IMPORT_JS` qui cherche une ressource js locale ou distante
 * et renvoie son chemin absolu, voire celui de sa version minifiée si la compression est activée.
 *
 * Signature : `#IMPORT_JS{module.esm.js}`
 *
 * Retourne une chaîne vide si le fichier n'est pas trouvé.
 *
 * @balise
 * @see chemin_import_js()
 * @example
 *     ```
 *     import {default as spip} from '#IMPORT_JS{config.js.html}';
 *     ```
 *
 * @param Champ $p
 *     Pile au niveau de la balise
 * @return Champ
 *     Pile complétée par le code à générer
 **/
function balise_IMPORT_JS_dist($p) {
	$arg = interprete_argument_balise(1, $p);
	if (!$arg) {
		$msg = ['zbug_balise_sans_argument', ['balise' => ' IMPORT_JS']];
		erreur_squelette($msg, $p);
	} else {
		$p->code = '(include_spip(\'inc/importmap\') ? chemin_import_js((string)' . $arg . ') : \'\')';
	}

	$p->interdire_scripts = false;
	return $p;
}

