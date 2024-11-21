<?php

/***************************************************************************\
 *  SPIP, Système de publication pour l'internet                           *
 *                                                                         *
 *  Copyright © avec tendresse depuis 2001                                 *
 *  Arnaud Martin, Antoine Pitrou, Philippe Rivière, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribué sous licence GNU/GPL.     *
\***************************************************************************/

use Spip\Afficher\Minipage\Admin as MinipageAdmin;

/**
 * Gestion d'affichage de la page de destruction des tables de SPIP
 *
 * @package SPIP\Core\Exec
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Exec de la page de destruction des tables de SPIP
 **/
function exec_base_delete_all_dist() {
	include_spip('inc/autoriser');
	if (!autoriser('detruire')) {
		$minipage = new MinipageAdmin();
		echo $minipage->page();
	} else {
		include_spip('base/dump');
		$res = base_lister_toutes_tables('', [], [], true);
		if (!$res) {
			spip_log('Erreur base de donnees');
			$minipage = new MinipageAdmin();
			echo $minipage->page(
				_T('titre_probleme_technique') . '<p><code>' . sql_errno() . ' ' . sql_error() . '</code></p>',
				['titre' => _T('info_travaux_titre')]
			);
		} else {
			$res = base_saisie_tables('delete', $res);
			include_spip('inc/headers');
			$res = "\n<ol style='text-align:left'><li>\n" .
				join("</li>\n<li>", $res) .
				'</li></ol>';
			$admin = charger_fonction('admin', 'inc');
			$res = $admin('delete_all', _T('titre_page_delete_all'), $res);
			if (!$res) {
				redirige_url_ecrire('install', '');
			} else {
				echo $res;
			}
		}
	}
}
