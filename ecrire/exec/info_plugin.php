<?php

/**
 * SPIP, Système de publication pour l'internet
 *
 * Copyright © avec tendresse depuis 2001
 * Arnaud Martin, Antoine Pitrou, Philippe Rivière, Emmanuel Saint-James
 *
 * Ce programme est un logiciel libre distribué sous licence GNU/GPL.
 */

use Spip\Afficher\Minipage\Admin as MinipageAdmin;

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

include_spip('inc/actions');

/**
 * Affichage de la description d'un plugin (en ajax)
 *
 * @uses plugins_get_infos_dist()
 * @uses plugins_afficher_plugin_dist()
 * @uses affiche_bloc_plugin()
 * @uses ajax_retour()
 */
function exec_info_plugin_dist() {
	if (!autoriser('configurer', '_plugins')) {
		$minipage = new MinipageAdmin();
		echo $minipage->page();
	} else {
		$plug = _DIR_RACINE . htmlspecialchars((string) _request('plugin'));
		$get_infos = charger_fonction('get_infos', 'plugins');
		$dir = '';
		if (str_starts_with($plug, (string) _DIR_PLUGINS)) {
			$dir = _DIR_PLUGINS;
		} elseif (str_starts_with($plug, (string) _DIR_PLUGINS_DIST)) {
			$dir = _DIR_PLUGINS_DIST;
		}
		if ($dir) {
			$plug = substr($plug, strlen((string) $dir));
		}
		$info = $get_infos($plug, false, $dir);
		$afficher_plugin = charger_fonction('afficher_plugin', 'plugins');
		ajax_retour(affiche_bloc_plugin($plug, $info, $dir));
	}
}
