<?php

/***************************************************************************\
 *  SPIP, Système de publication pour l'internet                           *
 *                                                                         *
 *  Copyright © avec tendresse depuis 2001                                 *
 *  Arnaud Martin, Antoine Pitrou, Philippe Rivière, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribué sous licence GNU/GPL.     *
\***************************************************************************/

use Spip\Compilateur\Noeud\Boucle;
use Spip\Compilateur\Noeud\Champ;
use Spip\Compilateur\Noeud\Critere;
use Spip\Compilateur\Noeud\Idiome;
use Spip\Compilateur\Noeud\Inclure;
use Spip\Compilateur\Noeud\Polyglotte;
use Spip\Compilateur\Noeud\Texte;

/**
 * Phraseur d'un squelette ayant une syntaxe SPIP/HTML
 *
 * Ce fichier transforme un squelette en un tableau d'objets de classe Boucle
 * il est chargé par un include calculé pour permettre différentes syntaxes en entrée
 *
 * @package SPIP\Core\Compilateur\Phraseur
 **/

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/** Début de la partie principale d'une boucle */
define('BALISE_BOUCLE', '<BOUCLE');
/** Fin de la partie principale d'une boucle */
define('BALISE_FIN_BOUCLE', '</BOUCLE');
/** Début de la partie avant non optionnelle d'une boucle (toujours affichee)*/
define('BALISE_PREAFF_BOUCLE', '<BB');
/** Début de la partie optionnelle avant d'une boucle */
define('BALISE_PRECOND_BOUCLE', '<B');
/** Fin de la partie optionnelle après d'une boucle */
define('BALISE_POSTCOND_BOUCLE', '</B');
/** Fin de la partie après non optionnelle d'une boucle (toujours affichee) */
define('BALISE_POSTAFF_BOUCLE', '</BB');
/** Fin de la partie alternative après d'une boucle */
define('BALISE_ALT_BOUCLE', '<//B');

/** Indique un début de boucle récursive */
define('TYPE_RECURSIF', 'boucle');
/** Expression pour trouver le type de boucle (TABLE autre_table ?) */
define('SPEC_BOUCLE', '/\s*\(\s*([^\s?)]+)(\s*[^)?]*)([?]?)\)/');
/** Expression pour trouver un identifiant de boucle */
define('NOM_DE_BOUCLE', '[0-9]+|[-_][-_.a-zA-Z0-9]*');
/**
 * Nom d'une balise #TOTO
 *
 * Écriture alambiquée pour rester compatible avec les hexadecimaux des vieux squelettes */
define('NOM_DE_CHAMP', '#((' . NOM_DE_BOUCLE . "):)?(([A-F]*[G-Z_][A-Z_0-9]*)|[A-Z_]+)\b(\*{0,2})");
/** Balise complète [...(#TOTO) ... ] */
define('CHAMP_ETENDU', '/\[([^]\[]*)\(' . NOM_DE_CHAMP . '([^[)]*\)[^]\[]*)\]/S');

define('BALISE_INCLURE', '/<INCLU[DR]E[[:space:]]*(\(([^)]*)\))?/S');
define('BALISE_POLYGLOTTE', ',<multi>(.*)</multi>,Uims');
define('BALISE_IDIOMES', ',<:(([a-z0-9_]+):)?([a-z0-9_]*)({([^\|=>]*=[^\|>]*)})?((\|[^>]*)?:/?>),iS');
define('BALISE_IDIOMES_ARGS', '@^\s*([^= ]*)\s*=\s*((' . NOM_DE_CHAMP . '[{][^}]*})?[^,]*)\s*,?\s*@s');

/** Champ sql dans parenthèse ex: (id_article) */
define('SQL_ARGS', '(\([^)]*\))');
/** Fonction SQL sur un champ ex: SUM(visites) */
define('CHAMP_SQL_PLUS_FONC', '`?([A-Z_\/][A-Z_\/0-9.]*)' . SQL_ARGS . '?`?');

function phraser_inclure($texte, $ligne, $result) {

	while (preg_match(BALISE_INCLURE, $texte, $match)) {
		$match = array_pad($match, 3, null);
		$p = strpos($texte, (string) $match[0]);
		$debut = substr($texte, 0, $p);
		if ($p) {
			$result = phraser_champs($debut, $ligne, $result);
		}
		$ligne += substr_count($debut, "\n");
		$champ = new Inclure();
		$champ->ligne = $ligne;
		$ligne += substr_count($match[0], "\n");
		$fichier = $match[2];
		# assurer ici la migration .php3 => .php
		# et de l'ancienne syntaxe INCLURE(page.php3) devenue surperflue
		if ($fichier and preg_match(',^(.*[.]php)3$,', $fichier, $r)) {
			$fichier = $r[1];
		}
		$champ->texte = ($fichier !== 'page.php') ? $fichier : '';
		$texte = substr($texte, $p + strlen($match[0]));
		// on assimile {var=val} a une liste de un argument sans fonction
		$pos_apres = 0;
		phraser_args($texte, '/>', '', $result, $champ, $pos_apres);
		if (!$champ->texte or (is_countable($champ->param) ? count($champ->param) : 0) > 1) {
			if (!function_exists('normaliser_inclure')) {
				include_spip('public/normaliser');
			}
			normaliser_inclure($champ);
		}
		$texte = substr($texte, strpos($texte, '>', $pos_apres) + 1);
		$texte = preg_replace(',^</INCLU[DR]E>,', '', $texte);
		$result[] = $champ;
	}

	if ($texte != '') {
		$result = phraser_champs($texte, $ligne, $result);
	}

	return $result;
}

function phraser_polyglotte($texte, $ligne, $result) {

	if (preg_match_all(BALISE_POLYGLOTTE, $texte, $m, PREG_SET_ORDER)) {
		foreach ($m as $match) {
			$p = strpos($texte, (string) $match[0]);
			$debut = substr($texte, 0, $p);
			if ($p) {
				$champ = new Texte();
				$champ->texte = $debut;
				$champ->ligne = $ligne;
				$result[] = $champ;
				$ligne += substr_count($champ->texte, "\n");
			}

			$champ = new Polyglotte();
			$champ->ligne = $ligne;
			$ligne += substr_count($match[0], "\n");
			$lang = '';
			$bloc = $match[1];
			$texte = substr($texte, $p + strlen($match[0]));
			while (preg_match('/^[[:space:]]*([^[{]*)[[:space:]]*[[{]([a-z_]+)[]}](.*)$/si', $bloc, $regs)) {
				$trad = $regs[1];
				if ($trad or $lang) {
					$champ->traductions[$lang] = $trad;
				}
				$lang = $regs[2];
				$bloc = $regs[3];
			}
			$champ->traductions[$lang] = $bloc;
			$result[] = $champ;
		}
	}
	if ($texte !== '') {
		$champ = new Texte();
		$champ->texte = $texte;
		$champ->ligne = $ligne;
		$result[] = $champ;
	}

	return $result;
}


/**
 * Repérer les balises de traduction (idiomes)
 *
 * Phrase les idiomes tel que
 * - `<:chaine:>`
 * - `<:module:chaine:>`
 * - `<:module:chaine{arg1=texte1,arg2=#BALISE}|filtre1{texte2,#BALISE}|filtre2:>`
 * - `<:module:chaine{arg1=texte1,arg2=[(#BALISE)]}|filtre1{texte2,#BALISE}|filtre2:>`
 * - `<:module:{=#VAL{chaine},arg1=texte1,arg2=[(#BALISE)]}|filtre1{texte2,#BALISE}|filtre2:>`
 * - `<:{=#VAL{module:chaine},arg1=texte1,arg2=[(#BALISE)]}|filtre1{texte2,#BALISE}|filtre2:>`
 *
 * Pour permettre une syntaxe complète dans les arguments, on transforme la syntaxe <:..:> en une syntaxe standard de balises
 * `[(#TRAD_IDIOME{module:chaine,arg1=texte1,arg2=[(#BALISE)]}|filtre1{texte2,#BALISE}|filtre2)]`
 *
 * qui est ensuite parsée comme une balise standard
 *
 * @note
 *    `chaine` peut etre vide si `=texte1` est present et `arg1` est vide
 *    sinon ce n'est pas un idiome
 *
 * @return string
 **/
function phraser_preparer_idiomes(string $texte, int $ligne, string $sep, array $result) {
	if (empty($texte) || !str_contains($texte, '<:')) {
		return $texte;
	}

	// definir un placholder pour les idiomes dont on est certain d'avoir aucune occurence dans le squelette
	$idiomes_placeholder = phraser_placeholder_hash($texte, 'PLACEHOLDER_IDIOME_');

	$search_pos = 0;
	$nbl = $ligne;
	while ((($p = strpos($texte, '<:', $search_pos)) !== false)
		&& preg_match(BALISE_IDIOMES, $texte, $match, PREG_OFFSET_CAPTURE, $p)) {

		$poss = array_column($match, 1);
		$match = array_column($match, 0);

		$module = $match[2];
		$chaine = $match[3];
		$idiome = $match[1] . $match[3];
		$args = $match[5];
		$filtres = $match[7] ?? '';

		// faux idiome de la forme <:module:{xxx=..}:> ou <:module::>
		if (!$chaine && (empty($args) || !str_starts_with($args, '='))) {
			// neutraliser le faux idiome pour eviter de revenir là encore et encore
			$texte = substr_replace($texte, '#VAL{<}', $poss[0], 1);
			$search_pos = $poss[0] + 7;
			continue;
		}

		// gerer les cas implicites :
		// si pas de module défini, injecter le par défaut, sauf si c'est une chaine de langue dynamique
		if (!$module and $chaine) {
			$idiome = "'" . MODULES_IDIOMES . ":" . $idiome . "'";
		}

		$nbl += public_compte_ligne($texte, $search_pos, $poss[0]);

		$placeholder = '';
		if (!empty($idiome) && empty($filtres) && (empty($args) || strpbrk($args, '[]') === false)) {
			// cas simple, on gere directement ici pour aller vite
			$champ = new Champ();
			$champ->ligne = $nbl;
			$champ->nom_champ = 'TRAD_IDIOME';

			// arguments de la balise
			$suite = '{' . $idiome . ($args ? ',' . $args : '') . '}';
			phraser_arg($suite, '', [], $champ);
		} else {
			// sinon on fait un parsing propre de la balise pour en déduire le $champ qui correspond
			$texte_idiome = '[(#TRAD_IDIOME{' . ($idiome ?: "''") . ($args ? "," . $args : '') . '}'.$filtres.')]';
			$champs = phraser_champs_interieurs($texte_idiome, $nbl, $sep);
			// on doit trouver un et un seul champ, celui de notre balise #IDIOME
			if (count($champs) !== 1) {
				erreur_squelette("Echec analyse idiome " . htmlentities($match[0] . " / " . $texte_idiome));
				// fallback, on insere le texte_idiome plutot que le placeholder, il sera parsé plus tard, en contexte
				$placeholder = $texte_idiome;
			}
			else {
				$champ = reset($champs);
			}
		}

		if (!$placeholder) {
			$placeholder = public_placeholder_generer($match[0], $champ, $idiomes_placeholder, 0);
		}
		$texte = substr_replace($texte, $placeholder, $poss[0], strlen($match[0]));

		$search_pos = $poss[0] + 1;
	}
	return $texte;
}

/**
 * Repère et phrase les balises SPIP tel que `#NOM` dans un texte
 *
 * Phrase également ses arguments si la balise en a (`#NOM{arg, ...}`)
 *
 * @uses phraser_polyglotte()
 * @uses phraser_args()
 * @uses phraser_vieux()
 *
 * @param string $texte
 * @param int $ligne
 * @param array $result
 * @return array
 **/
function phraser_champs($texte, $ligne, $result) {
	while (preg_match('/' . NOM_DE_CHAMP . '/S', $texte, $match)) {
		$p = strpos($texte, (string) $match[0]);
		// texte après la balise
		$suite = substr($texte, $p + strlen($match[0]));

		$debut = substr($texte, 0, $p);
		if ($p) {
			$result = phraser_polyglotte($debut, $ligne, $result);
		}
		$ligne += substr_count($debut, "\n");
		$champ = new Champ();
		$champ->ligne = $ligne;
		$ligne += substr_count($match[0], "\n");
		$champ->nom_boucle = $match[2];
		$champ->nom_champ = $match[3];
		$champ->etoile = $match[5];

		if ($suite and $suite[0] == '{') {
			phraser_arg($suite, '', [], $champ);
			// ce ltrim est une ereur de conception
			// mais on le conserve par souci de compatibilite
			$texte = ltrim($suite);
			// Il faudrait le normaliser dans l'arbre de syntaxe abstraite
			// pour faire sauter ce cas particulier a la decompilation.
			/* Ce qui suit est malheureusement incomplet pour cela:
			if ($n = (strlen($suite) - strlen($texte))) {
				$champ->apres = array(new Texte);
				$champ->apres[0]->texte = substr($suite,0,$n);
			}
			*/
		} else {
			$texte = $suite;
		}

		// reinjecter la chaine de langue si c'est un placeholder
		phraser_placeholder_reinjecter($champ);

		phraser_vieux($champ);
		$result[] = $champ;
	}
	if ($texte !== '') {
		$result = phraser_polyglotte($texte, $ligne, $result);
	}

	return $result;
}

// Gestion des imbrications:
// on cherche les [..] les plus internes et on les remplace par une chaine
// %###N@ ou N indexe un tableau comportant le resultat de leur analyse
// on recommence tant qu'il y a des [...] en substituant a l'appel suivant

function phraser_champs_etendus($texte, $ligne, $result) {
	if ($texte === '') {
		return $result;
	}

	$sep = '##';
	while (strpos($texte, (string) $sep) !== false) {
		$sep .= '#';
	}

	// avant de commencer on repere et convertit les idiomes historiques <:xxx:xxxx{...}:> en #PLACEHOLDER_IDIOME_xxx
	$texte = phraser_preparer_idiomes($texte, $ligne, $sep, $result);


	$champs = phraser_champs_interieurs($texte, $ligne, $sep);
	return array_merge($result, $champs);
}

/**
 * Analyse les filtres d'un champ etendu et affecte le resultat
 * renvoie la liste des lexemes d'origine augmentee
 * de ceux trouves dans les arguments des filtres (rare)
 * sert aussi aux arguments des includes et aux criteres de boucles
 * Tres chevelu
 *
 * @param string $texte
 * @param string $fin
 * @param string $sep
 * @param $result
 * @param $pointeur_champ
 * @param int $pos_debut
 * @return array
 */
function phraser_args(string $texte, $fin, $sep, $result, &$pointeur_champ, &$pos_debut) {
	$length = strlen($texte);
	while ($pos_debut < $length and trim($texte[$pos_debut]) === '') {
		$pos_debut++;
	}
	while (($pos_debut < $length) && !str_contains($fin, $texte[$pos_debut])) {
		// phraser_arg modifie directement le $texte, on fait donc avec ici en passant par une sous chaine
		$st = substr($texte, $pos_debut);
		$result = phraser_arg($st, $sep, $result, $pointeur_champ);
		$pos_debut = $length - strlen($st);
		while ($pos_debut < $length and trim($texte[$pos_debut]) === '') {
			$pos_debut++;
		}
	}

	return $result;
}

function phraser_arg(&$texte, $sep, $result, &$pointeur_champ) {
	preg_match(',^(\|?[^}{)|]*)(.*)$,ms', $texte, $match);
	$suite = ltrim($match[2]);
	$fonc = trim($match[1]);
	if ($fonc && $fonc[0] == '|') {
		$fonc = ltrim(substr($fonc, 1));
	}
	$res = [$fonc];
	$err_f = '';
	// cas du filtre sans argument ou du critere /
	if (($suite && ($suite[0] != '{')) || ($fonc && $fonc[0] == '/')) {
		// si pas d'argument, alors il faut une fonction ou un double |
		if (!$match[1]) {
			$err_f = ['zbug_erreur_filtre', ['filtre' => $texte]];
			erreur_squelette($err_f, $pointeur_champ);
			$texte = '';
		} else {
			$texte = $suite;
		}
		if ($err_f) {
			$pointeur_champ->param = false;
		} elseif ($fonc !== '') {
			$pointeur_champ->param[] = $res;
		}
		// pour les balises avec faux filtres qui boudent ce dur larbeur
		$pointeur_champ->fonctions[] = [$fonc, ''];

		return $result;
	}
	$args = ltrim(substr($suite, 1)); // virer le '(' initial
	$collecte = [];
	while ($args && $args[0] != '}') {
		if ($args[0] == '"') {
			preg_match('/^(")([^"]*)(")(.*)$/ms', $args, $regs);
		} elseif ($args[0] == "'") {
			preg_match("/^(')([^']*)(')(.*)$/ms", $args, $regs);
		} else {
			preg_match('/^([[:space:]]*)([^,([{}]*([(\[{][^])}]*[])}])?[^,}]*)([,}].*)$/ms', $args, $regs);
			if (!isset($regs[2]) or !strlen($regs[2])) {
				$err_f = ['zbug_erreur_filtre', ['filtre' => $args]];
				erreur_squelette($err_f, $pointeur_champ);
				$champ = new Texte();
				$champ->apres = $champ->avant = $args = '';
				break;
			}
		}
		$arg = $regs[2];
		if (trim($regs[1])) {
			$champ = new Texte();
			$champ->texte = $arg;
			$champ->apres = $champ->avant = $regs[1];
			$result[] = $champ;
			$collecte[] = $champ;
			$args = ltrim($regs[count($regs) - 1]);
		} else {
			if (!preg_match('/' . NOM_DE_CHAMP . '([{|])/', $arg, $r)) {
				// 0 est un aveu d'impuissance. A completer
				$arg = phraser_champs_exterieurs($arg, 0, $sep, $result);

				$args = ltrim($regs[count($regs) - 1]);
				$collecte = array_merge($collecte, $arg);
				$result = array_merge($result, $arg);
			} else {
				$n = strpos($args, (string) $r[0]);
				$pred = substr($args, 0, $n);
				$par = ',}';
				if (preg_match('/^(.*)\($/', $pred, $m)) {
					$pred = $m[1];
					$par = ')';
				}
				if ($pred) {
					$champ = new Texte();
					$champ->texte = $pred;
					$champ->apres = $champ->avant = '';
					$result[] = $champ;
					$collecte[] = $champ;
				}
				$rec = substr($args, $n + strlen($r[0]) - 1);
				$champ = new Champ();
				$champ->nom_boucle = $r[2];
				$champ->nom_champ = $r[3];
				$champ->etoile = $r[5];
				$next = $r[6];
				while ($next == '{') {
					phraser_arg($rec, $sep, $result, $champ);
					$args = ltrim((string) $rec);
					$next = $args[0] ?? '';
				}
				while ($next == '|') {
					$pos_apres = 0;
					phraser_args($rec, $par, $sep, $result, $champ, $pos_apres);
					$args = substr((string) $rec, $pos_apres);
					$next = $args[0] ?? '';
				}
				// Si erreur de syntaxe dans un sous-argument, propager.
				if ($champ->param === false) {
					$err_f = true;
				} else {
					phraser_vieux($champ);
				}
				if ($par == ')') {
					$args = substr($args, 1);
				}
				$collecte[] = $champ;
				$result[] = $champ;
			}
		}
		if (isset($args[0]) and $args[0] == ',') {
			$args = ltrim(substr($args, 1));
			if ($collecte) {
				$res[] = $collecte;
				$collecte = [];
			}
		}
	}
	if ($collecte) {
		$res[] = $collecte;
		$collecte = [];
	}
	$texte = substr($args, 1);
	$source = substr($suite, 0, strlen($suite) - strlen($texte));
	// propager les erreurs, et ignorer les param vides
	if ($pointeur_champ->param !== false) {
		if ($err_f) {
			$pointeur_champ->param = false;
		} elseif ($fonc !== '' || count($res) > 1) {
			$pointeur_champ->param[] = $res;
		}
	}
	// pour les balises avec faux filtres qui boudent ce dur larbeur
	$pointeur_champ->fonctions[] = [$fonc, $source];

	return $result;
}


function phraser_champs_exterieurs($texte, $ligne, $sep, $nested) {
	$res = [];
	while (($p = strpos($texte, (string) "%$sep")) !== false) {
		if (!preg_match(',^%' . preg_quote($sep) . '([0-9]+)@,', substr($texte, $p), $m)) {
			break;
		}
		$debut = substr($texte, 0, $p);
		$texte = substr($texte, $p + strlen($m[0]));
		if ($p) {
			$res = phraser_inclure($debut, $ligne, $res);
		}
		$ligne += substr_count($debut, "\n");
		$res[] = $nested[$m[1]];
	}

	return (($texte === '') ? $res : phraser_inclure($texte, $ligne, $res));
}

function phraser_champs_interieurs($texte, $ligne, $sep, $result) {
	$i = 0; // en fait count($result)
	$x = '';

	while (true) {
		$j = $i;
		$n = $ligne;
		while (preg_match(CHAMP_ETENDU, $texte, $match)) {
			$p = strpos($texte, (string) $match[0]);
			$debut = substr($texte, 0, $p);
			if ($p) {
				$result[$i] = $debut;
				$i++;
			}
			$nom = $match[4];
			$champ = new Champ();
			// ca ne marche pas encore en cas de champ imbrique
			$champ->ligne = $x ? 0 : ($n + substr_count($debut, "\n"));
			$champ->nom_boucle = $match[3];
			$champ->nom_champ = $nom;
			$champ->etoile = $match[6];
			// phraser_args indiquera ou commence apres
			$pos_apres = 0;
			$result = phraser_args($match[7], ')', $sep, $result, $champ, $pos_apres);
			phraser_vieux($champ);
			$champ->avant =	phraser_champs_exterieurs($match[1], $n, $sep, $result);
			$debut = substr($match[7], $pos_apres + 1);
			if (!empty($debut)) {
				$n += substr_count(substr($texte, 0, strpos($texte, $debut)), "\n");
			}
			$champ->apres = phraser_champs_exterieurs($debut, $n, $sep, $result);

			// reinjecter la boucle ou la chaine de langie si c'est un placeholder
			phraser_placeholder_reinjecter($champ);

			$result[$i] = $champ;
			$i++;
			$texte = substr($texte, $p + strlen($match[0]));
		}
		if ($texte !== '') {
			$result[$i] = $texte;
			$i++;
		}
		$x = '';

		while ($j < $i) {
			$z = $result[$j];
			// j'aurais besoin de connaitre le nombre de lignes...
			if (is_object($z)) {
				$x .= "%$sep$j@";
			} else {
				$x .= $z;
			}
			$j++;
		}
		if (preg_match(CHAMP_ETENDU, $x)) {
			$texte = $x;
		} else {
			return phraser_champs_exterieurs($x, $ligne, $sep, $result);
		}
	}
}

function phraser_vieux(&$champ) {
	$nom = $champ->nom_champ;
	if ($nom == 'EMBED_DOCUMENT') {
		if (!function_exists('phraser_vieux_emb')) {
			include_spip('public/normaliser');
		}
		phraser_vieux_emb($champ);
	} elseif ($nom == 'EXPOSER') {
		if (!function_exists('phraser_vieux_exposer')) {
			include_spip('public/normaliser');
		}
		phraser_vieux_exposer($champ);
	} elseif ($champ->param) {
		if ($nom == 'FORMULAIRE_RECHERCHE') {
			if (!function_exists('phraser_vieux_recherche')) {
				include_spip('public/normaliser');
			}
			phraser_vieux_recherche($champ);
		} elseif (preg_match(',^LOGO_[A-Z]+,', $nom)) {
			if (!function_exists('phraser_vieux_logos')) {
				include_spip('public/normaliser');
			}
			phraser_vieux_logos($champ);
		} elseif ($nom == 'MODELE') {
			if (!function_exists('phraser_vieux_modele')) {
				include_spip('public/normaliser');
			}
			phraser_vieux_modele($champ);
		} elseif ($nom == 'INCLURE' or $nom == 'INCLUDE') {
			if (!function_exists('phraser_vieux_inclu')) {
				include_spip('public/normaliser');
			}
			phraser_vieux_inclu($champ);
		}
	}
}


/**
 * Analyse les critères de boucle
 *
 * Chaque paramètre de la boucle (tel que {id_article>3}) est analysé
 * pour construire un critère (objet Critere) de boucle.
 *
 * Un critère a une description plus fine que le paramètre original
 * car on en extrait certaines informations tel que la négation et l'opérateur
 * utilisé s'il y a.
 *
 * La fonction en profite pour déclarer des modificateurs de boucles
 * en présence de certains critères (tout, plat) ou initialiser des
 * variables de compilation (doublons)...
 *
 * @param array $params
 *     Tableau de description des paramètres passés à la boucle.
 *     Chaque paramètre deviendra un critère
 * @param Boucle $result
 *     Description de la boucle
 *     Elle sera complété de la liste de ses critères
 * @return void
 **/
function phraser_criteres($params, &$result) {

	$err_ci = ''; // indiquera s'il y a eu une erreur
	$args = [];
	$type = $result->type_requete;
	$doublons = [];
	foreach ($params as $v) {
		$var = $v[1][0];
		$param = ($var->type != 'texte') ? '' : $var->texte;
		if (((is_countable($v) ? count($v) : 0) > 2) && (!preg_match(',[^A-Za-z]IN[^A-Za-z],i', $param))) {
			// plus d'un argument et pas le critere IN:
			// detecter comme on peut si c'est le critere implicite LIMIT debut, fin
			if (
				$var->type != 'texte'
				or preg_match('/^(n|n-|(n-)?\d+)$/S', $param)
			) {
				$op = ',';
				$not = false;
				$cond = false;
			} else {
				// Le debut du premier argument est l'operateur
				preg_match('/^([!]?)([a-zA-Z][a-zA-Z0-9_]*)[[:space:]]*(\??)[[:space:]]*(.*)$/ms', $param, $m);
				$op = $m[2];
				$not = (bool) $m[1];
				$cond = (bool) $m[3];
				// virer le premier argument,
				// et mettre son reliquat eventuel
				// Recopier pour ne pas alterer le texte source
				// utile au debusqueur
				if (strlen($m[4])) {
					// une maniere tres sale de supprimer les "' autour de {critere "xxx","yyy"}
					if (preg_match(',^(["\'])(.*)\1$,', $m[4])) {
						$c = null;
						eval('$c = ' . $m[4] . ';');
						if (isset($c)) {
							$m[4] = $c;
						}
					}
					$texte = new Texte();
					$texte->texte = $m[4];
					$v[1][0] = $texte;
				} else {
					array_shift($v[1]);
				}
			}
			array_shift($v); // $v[O] est vide
			$crit = new Critere();
			$crit->op = $op;
			$crit->not = $not;
			$crit->cond = $cond;
			$crit->exclus = '';
			$crit->param = $v;
			$args[] = $crit;
		} else {
			if ($var->type != 'texte') {
				// cas 1 seul arg ne commencant pas par du texte brut:
				// erreur ou critere infixe "/"
				if (($v[1][1]->type != 'texte') || (trim($v[1][1]->texte) != '/')) {
					$err_ci = [
						'zbug_critere_inconnu',
						['critere' => $var->nom_champ]
					];
					erreur_squelette($err_ci, $result);
				} else {
					$crit = new Critere();
					$crit->op = '/';
					$crit->not = false;
					$crit->exclus = '';
					$crit->param = [[$v[1][0]], [$v[1][2]]];
					$args[] = $crit;
				}
			} else {
				// traiter qq lexemes particuliers pour faciliter la suite
				// les separateurs
				if ($var->apres) {
					$result->separateur[] = $param;
				} elseif (($param == 'tout') or ($param == 'tous')) {
					$result->modificateur['tout'] = true;
				} elseif ($param == 'plat') {
					$result->modificateur['plat'] = true;
				}

				// Boucle hierarchie, analyser le critere id_rubrique
				// et les autres critères {id_x} pour forcer {tout} sur
				// ceux-ci pour avoir la rubrique mere...
				// Les autres critères de la boucle hierarchie doivent être
				// traités normalement.
				elseif (
					strcasecmp($type, 'hierarchie') == 0
					and !preg_match(",^id_rubrique\b,", $param)
					and preg_match(',^id_\w+\s*$,', $param)
				) {
					$result->modificateur['tout'] = true;
				} elseif (strcasecmp($type, 'hierarchie') == 0 and $param == 'id_rubrique') {
					// rien a faire sur {id_rubrique} tout seul
				} else {
					// pas d'emplacement statique, faut un dynamique
					// mais il y a 2 cas qui ont les 2 !
					if (($param == 'unique') || (preg_match(',^!?doublons *,', $param))) {
						// cette variable sera inseree dans le code
						// et son nom sert d'indicateur des maintenant
						$result->doublons = '$doublons_index';
						if ($param == 'unique') {
							$param = 'doublons';
						}
					} elseif ($param == 'recherche') {
						// meme chose (a cause de #nom_de_boucle:URL_*)
						$result->hash = ' ';
					}

					if (preg_match(',^ *([0-9-]+) *(/) *(.+) *$,', $param, $m)) {
						$crit = phraser_critere_infixe($m[1], $m[3], $v, '/', '', '');
					} elseif (
						preg_match(',^([!]?)(' . CHAMP_SQL_PLUS_FONC .
						')[[:space:]]*(\??)(!?)(<=?|>=?|==?|\b(?:IN|LIKE)\b)(.*)$,is', $param, $m)
					) {
						$a2 = trim($m[8]);
						if ($a2 and ($a2[0] == "'" or $a2[0] == '"') and ($a2[0] == substr($a2, -1))) {
							$a2 = substr($a2, 1, -1);
						}
						$crit = phraser_critere_infixe(
							$m[2],
							$a2,
							$v,
							(($m[2] == 'lang_select') ? $m[2] : $m[7]),
							$m[6],
							$m[5]
						);
						$crit->exclus = $m[1];
					} elseif (
						preg_match('/^([!]?)\s*(' .
						CHAMP_SQL_PLUS_FONC .
						')\s*(\??)(.*)$/is', $param, $m)
					) {
						// contient aussi les comparaisons implicites !
						// Comme ci-dessus:
						// le premier arg contient l'operateur
						array_shift($v);
						if ($m[6]) {
							$v[0][0] = new Texte();
							$v[0][0]->texte = $m[6];
						} else {
							array_shift($v[0]);
							if (!$v[0]) {
								array_shift($v);
							}
						}
						$crit = new Critere();
						$crit->op = $m[2];
						$crit->param = $v;
						$crit->not = (bool) $m[1];
						$crit->cond = (bool) $m[5];
					} else {
						$err_ci = [
							'zbug_critere_inconnu',
							['critere' => $param]
						];
						erreur_squelette($err_ci, $result);
					}

					if ((!preg_match(',^!?doublons *,', $param)) || $crit->not) {
						$args[] = $crit;
					} else {
						$doublons[] = $crit;
					}
				}
			}
		}
	}

	// les doublons non nies doivent etre le dernier critere
	// pour que la variable $doublon_index ait la bonne valeur
	// cf critere_doublon
	if ($doublons) {
		$args = [...$args, ...$doublons];
	}

	// Si erreur, laisser la chaine dans ce champ pour le HTTP 503
	if (!$err_ci) {
		$result->criteres = $args;
	}
}

function phraser_critere_infixe($arg1, $arg2, $args, $op, $not, $cond) {
	$args[0] = new Texte();
	$args[0]->texte = $arg1;
	$args[0] = [$args[0]];
	$args[1][0] = new Texte();
	$args[1][0]->texte = $arg2;
	$crit = new Critere();
	$crit->op = $op;
	$crit->not = $not;
	$crit->cond = $cond;
	$crit->param = $args;

	return $crit;
}

/**
 * Compter le nombre de lignes dans une partie texte
 * @param $texte
 * @param int $debut
 * @param null $fin
 * @return int
 */
function public_compte_ligne($texte, $debut = 0, $fin = null) {
	if (is_null($fin)) {
		return substr_count($texte, "\n", $debut);
	}
	else {
		return substr_count($texte, "\n", $debut, $fin - $debut);
	}
}


/**
 * Trouver la boucle qui commence en premier dans un texte
 * On repere les boucles via <BOUCLE_xxx(
 * et ensuite on regarde son vrai debut soit <B_xxx> soit <BB_xxx>
 *
 * @param $texte
 * @param $id_parent
 * @param $descr
 * @param int $pos_debut_texte
 * @return array|null
 */
function public_trouver_premiere_boucle($texte, $id_parent, $descr, $pos_debut_texte = 0) {
	$premiere_boucle = null;
	$pos_derniere_boucle_anonyme = $pos_debut_texte;

	$current_pos = $pos_debut_texte;
	while (($pos_boucle = strpos($texte, BALISE_BOUCLE, $current_pos)) !== false) {
		$current_pos = $pos_boucle + 1;
		$pos_parent = strpos($texte, '(', $pos_boucle);

		$id_boucle = '';
		if ($pos_parent !== false) {
			$id_boucle = trim(substr($texte, $pos_boucle + strlen(BALISE_BOUCLE), $pos_parent - $pos_boucle - strlen(BALISE_BOUCLE)));
		}
		if (
			$pos_parent === false
			or (strlen($id_boucle) and !(is_numeric($id_boucle) or strpos($id_boucle, '_') === 0))
		) {
			$result = new Boucle();
			$result->id_parent = $id_parent;
			$result->descr = $descr;

			// un id_boucle pour l'affichage de l'erreur
			if (!strlen($id_boucle)) {
				$id_boucle = substr($texte, $pos_boucle + strlen(BALISE_BOUCLE), 15);
			}
			$result->id_boucle = $id_boucle;
			$err_b = ['zbug_erreur_boucle_syntaxe', ['id' => $id_boucle]];
			erreur_squelette($err_b, $result);

			continue;
		}
		else {
			$boucle = [
				'id_boucle' => $id_boucle,
				'id_boucle_err' => $id_boucle,
				'debut_boucle' => $pos_boucle,
				'pos_boucle' => $pos_boucle,
				'pos_parent' => $pos_parent,
				'pos_precond' => false,
				'pos_precond_inside' => false,
				'pos_preaff' => false,
				'pos_preaff_inside' => false,
			];

			// un id_boucle pour l'affichage de l'erreur sur les boucle anonymes
			if (!strlen($id_boucle)) {
				$boucle['id_boucle_err'] = substr($texte, $pos_boucle + strlen(BALISE_BOUCLE), 15);
			}

			// trouver sa position de depart reelle : au <Bxx> ou au <BBxx>
			$precond_boucle = BALISE_PRECOND_BOUCLE . $id_boucle . '>';
			$pos_precond = strpos($texte, $precond_boucle, $id_boucle ? $pos_debut_texte : $pos_derniere_boucle_anonyme);
			if (
				$pos_precond !== false
				and $pos_precond < $boucle['debut_boucle']
			) {
				$boucle['debut_boucle'] = $pos_precond;
				$boucle['pos_precond'] = $pos_precond;
				$boucle['pos_precond_inside'] = $pos_precond + strlen($precond_boucle);
			}

			$preaff_boucle = BALISE_PREAFF_BOUCLE . $id_boucle . '>';
			$pos_preaff = strpos($texte, $preaff_boucle, $id_boucle ? $pos_debut_texte : $pos_derniere_boucle_anonyme);
			if (
				$pos_preaff !== false
				and $pos_preaff < $boucle['debut_boucle']
			) {
				$boucle['debut_boucle'] = $pos_preaff;
				$boucle['pos_preaff'] = $pos_preaff;
				$boucle['pos_preaff_inside'] = $pos_preaff + strlen($preaff_boucle);
			}
			if (!strlen($id_boucle)) {
				$pos_derniere_boucle_anonyme = $pos_boucle;
			}

			if (is_null($premiere_boucle) or $premiere_boucle['debut_boucle'] > $boucle['debut_boucle']) {
				$premiere_boucle = $boucle;
			}
		}
	}

	return $premiere_boucle;
}

/**
 * Trouver la fin de la  boucle (balises </B <//B </BB)
 * en faisant attention aux boucles anonymes qui ne peuvent etre imbriquees
 *
 * @param $texte
 * @param $id_parent
 * @param $boucle
 * @param $pos_debut_texte
 * @param $result
 * @return mixed
 */
function public_trouver_fin_boucle($texte, $id_parent, $boucle, $pos_debut_texte, $result) {
	$id_boucle = $boucle['id_boucle'];
	$pos_courante = $pos_debut_texte;

	$boucle['pos_postcond'] = false;
	$boucle['pos_postcond_inside'] = false;
	$boucle['pos_altern'] = false;
	$boucle['pos_altern_inside'] = false;
	$boucle['pos_postaff'] = false;
	$boucle['pos_postaff_inside'] = false;

	$pos_anonyme_next = null;
	// si c'est une boucle anonyme, chercher la position de la prochaine boucle anonyme
	if (!strlen($id_boucle)) {
		$pos_anonyme_next = strpos($texte, BALISE_BOUCLE . '(', $pos_courante);
	}

	//
	// 1. Recuperer la partie conditionnelle apres
	//
	$apres_boucle = BALISE_POSTCOND_BOUCLE . $id_boucle . '>';
	$pos_apres = strpos($texte, $apres_boucle, $pos_courante);
	if (
		$pos_apres !== false
		and (!$pos_anonyme_next or $pos_apres < $pos_anonyme_next)
	) {
		$boucle['pos_postcond'] = $pos_apres;
		$pos_apres += strlen($apres_boucle);
		$boucle['pos_postcond_inside'] = $pos_apres;
		$pos_courante = $pos_apres ;
	}

	//
	// 2. Récuperer la partie alternative apres
	//
	$altern_boucle = BALISE_ALT_BOUCLE . $id_boucle . '>';
	$pos_altern = strpos($texte, $altern_boucle, $pos_courante);
	if (
		$pos_altern !== false
		and (!$pos_anonyme_next or $pos_altern < $pos_anonyme_next)
	) {
		$boucle['pos_altern'] = $pos_altern;
		$pos_altern += strlen($altern_boucle);
		$boucle['pos_altern_inside'] = $pos_altern;
		$pos_courante = $pos_altern;
	}

	//
	// 3. Recuperer la partie footer non alternative
	//
	$postaff_boucle = BALISE_POSTAFF_BOUCLE . $id_boucle . '>';
	$pos_postaff = strpos($texte, $postaff_boucle, $pos_courante);
	if (
		$pos_postaff !== false
		and (!$pos_anonyme_next or $pos_postaff < $pos_anonyme_next)
	) {
		$boucle['pos_postaff'] = $pos_postaff;
		$pos_postaff += strlen($postaff_boucle);
		$boucle['pos_postaff_inside'] = $pos_postaff;
		$pos_courante = $pos_postaff ;
	}

	return $boucle;
}
/**
 * Définir un placeholder avec un hash unique pour substitution dans un texte donné
 * on s'assure de n'avoir aucune occurence existante dans le texte
 * @param string $texte
 * @param string $placeholder_prefixe
 * @return string
 */
function phraser_placeholder_hash(string &$texte, string $placeholder_prefixe) {
	do {
		$placeholder = $placeholder_prefixe . strtoupper(substr(md5(uniqid()),0,8));
	} while (str_contains($texte, $placeholder));
	return $placeholder;
}


/**
 * Mémorise un morceau de code du squelette en le remplaçant par un placeholder, ou reinjecte les morceaux de codes à la place des placeholders.
 * @param object|string $champ
 * @param ?string $nom_champ_placeholder
 * @param ?object $boucle_ou_champ
 * @internal
 * @see phraser_placeholder_memoriser()
 * @see phraser_placeholder_reinjecter()
 */
function phraser_placeholder_memoriser_ou_reinjecter(&$champ, ?string $nom_champ_placeholder = null, $boucle_ou_champ = null) {
	static $placeholder_connus = [];
	// si c'est un appel pour memoriser une boucle, memorisons la
	if (is_string($champ) && !empty($nom_champ_placeholder) && !empty($boucle_ou_champ)) {
		$placeholder_connus[$nom_champ_placeholder][$champ] = &$boucle_ou_champ;
	} else {
		if (!empty($champ->nom_champ) && !empty($placeholder_connus[$champ->nom_champ])) {
			$nom_champ_placeholder = $champ->nom_champ;
			$id = '';
			if (!empty($champ->param[0][1])) {
				$id = reset($champ->param[0][1]);
				$id = $id->texte;
			}
			if (!empty($placeholder_connus[$nom_champ_placeholder][$id])) {
				$champ = $placeholder_connus[$nom_champ_placeholder][$id];
			}
		}
	}
}

/**
 * @deprecated
 * @see phraser_placeholder_memoriser_ou_reinjecter()
 */
function phraser_boucle_placeholder(&$champ, $boucle_placeholder = null, $boucle = null) {
	phraser_placeholder_memoriser_ou_reinjecter($champ, $boucle_placeholder, $boucle);
}

/**
 * Memoriser un champ ou une boucle associé a son placeholder
 * @param string $champ
 * @param string $nom_champ_placeholder
 * @param $boucle_ou_champ
 * @uses phraser_placeholder_memoriser_ou_reinjecter()
 */
function phraser_placeholder_memoriser(string $champ, string $nom_champ_placeholder, $boucle_ou_champ) {
	phraser_placeholder_memoriser_ou_reinjecter($champ, $nom_champ_placeholder, $boucle_ou_champ);
}
/**
 * Reinejcter un champ ou une boucle associé a son placeholder
 * @param &$champ
 * @uses phraser_placeholder_memoriser_ou_reinjecter()
 */
function phraser_placeholder_reinjecter(&$champ) {
	phraser_placeholder_memoriser_ou_reinjecter($champ);
}


/**
 * Generer une balise placeholder qui prend la place d'une boucle ou d'un champ pour continuer le parsing des balises
 * utilisé pour remplacer une boucle ou un idiome
 * @param Boucle|Champ $boucle
 * @return string
 */
function public_placeholder_generer(string $nom_structure, &$boucle_ou_champ, string $placeholder_pattern, int $nb_lignes, bool $force_balise_etendue = false): string {
	if ($nb_lignes || $force_balise_etendue) {
		$nom_champ = $placeholder_pattern;
		$placeholder = "[(#{$nom_champ}{" . $nom_structure . '})' . str_pad('', $nb_lignes, "\n") . ']';
		phraser_placeholder_memoriser($nom_structure, $nom_champ, $boucle_ou_champ);
	} else {
		$placeholder_suite = "_" . strtoupper(md5($nom_structure));
		$nom_champ = "{$placeholder_pattern}{$placeholder_suite}";
		$placeholder = "#{$nom_champ}";
		$nom_structure = '';
		//memoriser e champ a reinjecter
		phraser_placeholder_memoriser($nom_structure, $nom_champ, $boucle_ou_champ);
	}
	return $placeholder;
}

/**
 * Generer une balise placeholder qui prend la place de la boucle pour continuer le parsing des balises
 * @deprecated
 * @uses public_placeholder_generer()
 */
function public_generer_boucle_placeholder($id_boucle, &$boucle, $boucle_placeholder, $nb_lignes) {
	return public_placeholder_generer($id_boucle, $boucle, $boucle_placeholder, $nb_lignes);
}

/**
 * Analyseur syntaxique des squelettes HTML SPIP
 * On commence par analyser les boucles, les mémoriser, et les remplacer dans le texte par des placeholder
 * qui ne genent pas la suite de l'analyse des balises et autres
 *
 * @param array<string,Boucle> $boucles
 */
function public_phraser_html_dist(
	string $texte,
	string $id_parent,
	array &$boucles,
	array $descr,
	int $ligne_debut_texte = 1,
	?string $boucle_placeholder = null
): array {

	$all_res = [];
	// definir un placholder pour les boucles dont on est sur d'avoir aucune occurence dans le squelette
	if ($boucle_placeholder === null) {
		$boucle_placeholder = phraser_placeholder_hash($texte, 'PLACEHOLDER_BOUCLE_');
	}

	$ligne_debut_initial = $ligne_debut_texte;
	$pos_debut_texte = 0;
	while ($boucle = public_trouver_premiere_boucle($texte, $id_parent, $descr, $pos_debut_texte)) {
		$err_b = ''; // indiquera s'il y a eu une erreur
		$result = new Boucle();
		$result->id_parent = $id_parent;
		$result->descr = $descr;

		$pos_courante = $boucle['pos_boucle'];
		$pos_parent = $boucle['pos_parent'];
		$id_boucle_search = $id_boucle = $boucle['id_boucle'];

		$ligne_preaff = $ligne_avant = $ligne_milieu = $ligne_debut_texte + public_compte_ligne($texte, $pos_debut_texte, $pos_parent);

		// boucle anonyme ?
		if (!strlen($id_boucle)) {
			$id_boucle = '_anon_L' . $ligne_milieu . '_' . substr(md5('anonyme:' . $id_parent . ':' . json_encode($boucle, JSON_THROW_ON_ERROR)), 0, 8);
		}

		$pos_debut_boucle = $pos_courante;

		$pos_milieu = $pos_parent;

		// Regarder si on a une partie conditionnelle avant <B_xxx>
		if ($boucle['pos_precond'] !== false) {
			$pos_debut_boucle = $boucle['pos_precond'];

			$pos_avant = $boucle['pos_precond_inside'];
			$result->avant = substr($texte, $pos_avant, $pos_courante - $pos_avant);
			$ligne_avant = $ligne_debut_texte +  public_compte_ligne($texte, $pos_debut_texte, $pos_avant);
		}

		// Regarder si on a une partie inconditionnelle avant <BB_xxx>
		if ($boucle['pos_preaff'] !== false) {
			$end_preaff = $pos_debut_boucle;

			$pos_preaff = $boucle['pos_preaff_inside'];
			$result->preaff = substr($texte, $pos_preaff, $end_preaff - $pos_preaff);
			$ligne_preaff = $ligne_debut_texte +  public_compte_ligne($texte, $pos_debut_texte, $pos_preaff);
		}

		$result->id_boucle = $id_boucle;

		if (
			!preg_match(SPEC_BOUCLE, $texte, $match, 0, $pos_milieu)
			or ($pos_match = strpos($texte, (string) $match[0], $pos_milieu)) === false
			or $pos_match > $pos_milieu
		) {
			$err_b = ['zbug_erreur_boucle_syntaxe', ['id' => $id_boucle]];
			erreur_squelette($err_b, $result);

			$ligne_debut_texte += public_compte_ligne($texte, $pos_debut_texte, $pos_courante + 1);
			$pos_debut_texte = $pos_courante + 1;
			continue;
		}

		$result->type_requete = $match[0];
		$pos_milieu += strlen($match[0]);
		$pos_courante = $pos_milieu; // on s'en sert pour compter les lignes plus precisemment

		$type = $match[1];
		$jointures = trim($match[2]);
		$table_optionnelle = ($match[3]);
		if ($jointures) {
			// on affecte pas ici les jointures explicites, mais dans la compilation
			// ou elles seront completees des jointures declarees
			$result->jointures_explicites = $jointures;
		}

		if ($table_optionnelle) {
			$result->table_optionnelle = true;
			$result->type_table_optionnelle = $type;
		}

		// 1ere passe sur les criteres, vu comme des arguments sans fct
		// Resultat mis dans result->param
		$pos_fin_criteres = $pos_milieu;
		phraser_args($texte, '/>', '', $all_res, $result, $pos_fin_criteres);

		// En 2e passe result->criteres contiendra un tableau
		// pour l'instant on met le source (chaine) :
		// si elle reste ici au final, c'est qu'elle contient une erreur
		$pos_courante = $pos_fin_criteres; // on s'en sert pour compter les lignes plus precisemment
		$result->criteres = substr($texte, $pos_milieu, $pos_fin_criteres - $pos_milieu);
		$pos_milieu = $pos_fin_criteres;

		//
		// Recuperer la fin :
		//
		if ($texte[$pos_milieu] === '/') {
			// boucle autofermante : pas de partie conditionnelle apres
			$pos_courante += 2;
			$result->milieu = '';
		} else {
			$pos_milieu += 1;

			$fin_boucle = BALISE_FIN_BOUCLE . $id_boucle_search . '>';
			$pos_fin = strpos($texte, $fin_boucle, $pos_milieu);
			if ($pos_fin === false) {
				$err_b = [
					'zbug_erreur_boucle_fermant',
					['id' => $id_boucle]
				];
				erreur_squelette($err_b, $result);
				$pos_courante += strlen($fin_boucle);
			}
			else {
				// verifier une eventuelle imbrication d'une boucle homonyme
				// (interdite, generera une erreur plus loin, mais permet de signaler la bonne erreur)
				$search_debut_boucle = BALISE_BOUCLE . $id_boucle_search . '(';
				$search_from = $pos_milieu;
				$nb_open = 1;
				$nb_close = 1;
				$maxiter = 0;
				do {
					while (
						$nb_close < $nb_open
						and $p = strpos($texte, $fin_boucle, $pos_fin + 1)
					) {
						$nb_close++;
						$pos_fin = $p;
					}
					// si on a pas trouve assez de boucles fermantes, sortir de la, on a fait de notre mieux
					if ($nb_close < $nb_open) {
						break;
					}
					while (
						$p = strpos($texte, $search_debut_boucle, $search_from)
						and $p < $pos_fin
					) {
						$nb_open++;
						$search_from = $p + 1;
					}
				} while ($nb_close < $nb_open and $maxiter++ < 5);

				$pos_courante = $pos_fin + strlen($fin_boucle);
			}
			$result->milieu = substr($texte, $pos_milieu, $pos_fin - $pos_milieu);
		}

		$ligne_suite = $ligne_apres = $ligne_debut_texte + public_compte_ligne($texte, $pos_debut_texte, $pos_courante);
		$boucle = public_trouver_fin_boucle($texte, $id_parent, $boucle, $pos_courante, $result);

		//
		// 1. Partie conditionnelle apres ?
		//
		if ($boucle['pos_postcond']) {
			$result->apres = substr($texte, $pos_courante, $boucle['pos_postcond'] - $pos_courante);
			$ligne_suite += public_compte_ligne($texte, $pos_courante, $boucle['pos_postcond_inside']);
			$pos_courante = $boucle['pos_postcond_inside'] ;
		}


		//
		// 2. Partie alternative apres ?
		//
		$ligne_altern = $ligne_suite;
		if ($boucle['pos_altern']) {
			$result->altern = substr($texte, $pos_courante, $boucle['pos_altern'] - $pos_courante);
			$ligne_suite += public_compte_ligne($texte, $pos_courante, $boucle['pos_altern_inside']);
			$pos_courante = $boucle['pos_altern_inside'];
		}

		//
		// 3. Partie footer non alternative ?
		//
		$ligne_postaff = $ligne_suite;
		if ($boucle['pos_postaff']) {
			$result->postaff = substr($texte, $pos_courante, $boucle['pos_postaff'] - $pos_courante);
			$ligne_suite += public_compte_ligne($texte, $pos_courante, $boucle['pos_postaff_inside']);
			$pos_courante = $boucle['pos_postaff_inside'];
		}

		$result->ligne = $ligne_preaff;

		if ($p = strpos($type, ':')) {
			$result->sql_serveur = substr($type, 0, $p);
			$type = substr($type, $p + 1);
		}
		$soustype = strtolower($type);

		if (!isset($GLOBALS['table_des_tables'][$soustype])) {
			$soustype = $type;
		}

		$result->type_requete = $soustype;
		// Lancer la 2e passe sur les criteres si la 1ere etait bonne
		if (!is_array($result->param)) {
			$err_b = true;
		} else {
			phraser_criteres($result->param, $result);
			if (strncasecmp($soustype, TYPE_RECURSIF, strlen(TYPE_RECURSIF)) == 0) {
				$result->type_requete = TYPE_RECURSIF;
				$args = $result->param;
				array_unshift(
					$args,
					substr($type, strlen(TYPE_RECURSIF))
				);
				$result->param = $args;
			}
		}

		$descr['id_mere_contexte'] = $id_boucle;
		$result->milieu = public_phraser_html_dist($result->milieu, $id_boucle, $boucles, $descr, $ligne_milieu, $boucle_placeholder);
		// reserver la place dans la pile des boucles pour compiler ensuite dans le bon ordre
		// ie les boucles qui apparaissent dans les partie conditionnelles doivent etre compilees apres cette boucle
		// si il y a deja une boucle de ce nom, cela declenchera une erreur ensuite
		if (empty($boucles[$id_boucle])) {
			$boucles[$id_boucle] = null;
		}
		$result->preaff = public_phraser_html_dist($result->preaff, $id_parent, $boucles, $descr, $ligne_preaff, $boucle_placeholder);
		$result->avant = public_phraser_html_dist($result->avant, $id_parent, $boucles, $descr, $ligne_avant, $boucle_placeholder);
		$result->apres = public_phraser_html_dist($result->apres, $id_parent, $boucles, $descr, $ligne_apres, $boucle_placeholder);
		$result->altern = public_phraser_html_dist($result->altern, $id_parent, $boucles, $descr, $ligne_altern, $boucle_placeholder);
		$result->postaff = public_phraser_html_dist($result->postaff, $id_parent, $boucles, $descr, $ligne_postaff, $boucle_placeholder);

		// Prevenir le generateur de code que le squelette est faux
		if ($err_b) {
			$result->type_requete = false;
		}

		// Verifier qu'il n'y a pas double definition
		// apres analyse des sous-parties (pas avant).
		if (!empty($boucles[$id_boucle])) {
			if ($boucles[$id_boucle]->type_requete !== false) {
				$err_b_d = [
					'zbug_erreur_boucle_double',
					['id' => $id_boucle]
				];
				erreur_squelette($err_b_d, $result);
				// Prevenir le generateur de code que le squelette est faux
				$boucles[$id_boucle]->type_requete = false;
			}
		} else {
			$boucles[$id_boucle] = $result;
		}

		// remplacer la boucle par un placeholder qui compte le meme nombre de lignes
		$placeholder = public_placeholder_generer(
			$id_boucle,
			$boucles[$id_boucle],
			$boucle_placeholder,
			$ligne_suite - $ligne_debut_texte,
			true
		);
		$longueur_boucle = $pos_courante - $boucle['debut_boucle'];
		$texte = substr_replace($texte, $placeholder, $boucle['debut_boucle'], $longueur_boucle);
		$pos_courante = $pos_courante - $longueur_boucle + strlen($placeholder);

		// phraser la partie avant le debut de la boucle
		#$all_res = phraser_champs_etendus(substr($texte, $pos_debut_texte, $boucle['debut_boucle'] - $pos_debut_texte), $ligne_debut_texte, $all_res);
		#$all_res[] = &$boucles[$id_boucle];

		$ligne_debut_texte = $ligne_suite;
		$pos_debut_texte = $pos_courante;
	}

	$all_res = phraser_champs_etendus($texte, $ligne_debut_initial, $all_res);

	return $all_res;
}
