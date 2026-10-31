<?php

/**
 * Utilisations de pipelines par le plugin Double authentification
 *
 * Le formulaire de login de SPIP devient un formulaire en deux étapes :
 *
 * 1. login + mot de passe : si l'auteur a la 2FA active, autoriser('loger')
 *    refuse l'ouverture de session ; on crée alors une connexion en attente
 *    et le formulaire affiche la saisie du code ;
 * 2. jeton + code : si le code est valide, on ouvre la session avec auth_loger()
 *    puis le traiter() du formulaire de login redirige vers la cible.
 *
 * @package SPIP\Twofa\Pipelines
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Déclarer le jeton de connexion en attente dans l'environnement du login
 *
 * @pipeline formulaire_charger
 * @param array $flux
 * @return array
 */
function twofa_formulaire_charger($flux) {
	if (($flux['args']['form'] ?? '') === 'login' && is_array($flux['data'])) {
		$flux['data']['twofa_jeton'] = '';
	}

	return $flux;
}

/**
 * Seconde étape du formulaire de login
 *
 * @pipeline formulaire_verifier
 * @param array $flux
 * @return array
 */
function twofa_formulaire_verifier($flux) {
	if (($flux['args']['form'] ?? '') !== 'login') {
		return $flux;
	}
	include_spip('inc/twofa');

	// étape 2 : saisie du code
	$jeton = (string) Spip::input('twofa_jeton');
	if ($jeton !== '') {
		$flux['data'] = twofa_verifier_login($jeton, (string) Spip::input('twofa_code'), $flux['args']['args'] ?? []);
		return $flux;
	}

	// étape 1 : mot de passe correct, mais session refusée par autoriser('loger')
	if ($auteur = twofa_connexion_bloquee()) {
		Spip::setInput('twofa_jeton', twofa_creer_attente($auteur));
		// erreurs non vides pour bloquer traiter(), sans message à afficher
		$flux['data'] = ['twofa_code' => '', 'message_erreur' => ''];
	}

	return $flux;
}

/**
 * Vérifier le code de la seconde étape et ouvrir la session
 *
 * @param string $jeton Jeton de la connexion en attente
 * @param string $code Code TOTP ou code de secours
 * @param array $args Arguments du formulaire de login ($cible, $options, $deprecated)
 * @return array Erreurs du formulaire de login
 */
function twofa_verifier_login(#[\SensitiveParameter] string $jeton, #[\SensitiveParameter] string $code, array $args): array {
	if (!$attente = twofa_lire_attente($jeton)) {
		Spip::setInput('twofa_jeton', '');
		return ['message_erreur' => _T('twofa:erreur_attente_expiree')];
	}
	[$auteur, $etat] = $attente;
	$id_auteur = (int) $auteur['id_auteur'];

	if (!twofa_valider_code($auteur, $code)) {
		if (!twofa_compter_echec($auteur, $etat)) {
			twofa_effacer_attente($id_auteur);
			Spip::setInput('twofa_jeton', '');
			return ['message_erreur' => _T('twofa:erreur_trop_essais')];
		}
		return ['twofa_code' => _T('twofa:erreur_code'), 'message_erreur' => ''];
	}

	twofa_effacer_attente($id_auteur);
	Spip::setInput('twofa_jeton', '');
	twofa_connexion_validee($id_auteur);

	$auteur['auth'] = $auteur['source'] ?: 'spip';
	if ($etat['cookie'] === 'oui') {
		$auteur['cookie'] = 'oui';
	}
	include_spip('inc/auth');
	if (auth_loger($auteur) === false) {
		return ['message_erreur' => _T('login_erreur_pass')];
	}
	spip_logger('twofa')->info("connexion de l'auteur $id_auteur validée par le second facteur");

	// mêmes contrôles que formulaires_login_verifier_dist() après auth_loger()
	include_spip('formulaires/login');
	[$cible, $options, $deprecated] = array_pad($args, 3, null);
	if (!is_array($options)) {
		$options = [
			'login' => $options,
			'prive' => $deprecated,
		];
	}
	$prive = (empty($options['prive']) ? null : $options['prive']);

	return ($prive ?? is_url_prive((string) $cible))
		? login_autoriser() : [];
}

/**
 * Afficher la saisie du code à la place du formulaire de login
 * quand une connexion est en attente du second facteur
 *
 * @pipeline formulaire_fond
 * @param array $flux
 * @return array
 */
function twofa_formulaire_fond($flux) {
	if (
		($flux['args']['form'] ?? '') === 'login'
		&& !empty($flux['args']['contexte']['twofa_jeton'])
	) {
		$flux['data'] = recuperer_fond('formulaires/inc-twofa_login', $flux['args']['contexte']);
	}

	return $flux;
}

/**
 * Ne jamais garder les champs 2FA dans la session en mémoire
 *
 * @pipeline preparer_visiteur_session
 * @param array $flux
 * @return array
 */
function twofa_preparer_visiteur_session($flux) {
	include_spip('inc/twofa');
	foreach (twofa_champs() as $champ) {
		unset($flux['data'][$champ]);
	}

	return $flux;
}

/**
 * Ne jamais écrire les champs 2FA dans les fichiers de session
 *
 * @pipeline preparer_fichier_session
 * @param array $flux
 * @return array
 */
function twofa_preparer_fichier_session($flux) {
	include_spip('inc/twofa');
	foreach (twofa_champs() as $champ) {
		unset($flux['data'][$champ]);
	}

	return $flux;
}

/**
 * Bloc 2FA sur la fiche d'un auteur
 *
 * - sa propre fiche : formulaire d'activation / désactivation ;
 * - la fiche d'un autre auteur, pour un webmestre : état et réinitialisation.
 *
 * @pipeline affiche_milieu
 * @param array $flux
 * @return array
 */
function twofa_affiche_milieu($flux) {
	if (($flux['args']['exec'] ?? '') !== 'auteur' || !($id_auteur = (int) ($flux['args']['id_auteur'] ?? 0))) {
		return $flux;
	}

	include_spip('inc/autoriser');
	if (autoriser('configurertwofa', 'auteur', $id_auteur)) {
		$flux['data'] .= recuperer_fond('prive/squelettes/inclure/twofa_auteur', ['id_auteur' => $id_auteur]);
	} elseif (autoriser('reinitialisertwofa', 'auteur', $id_auteur)) {
		$flux['data'] .= recuperer_fond('prive/squelettes/inclure/twofa_auteur_admin', ['id_auteur' => $id_auteur]);
	}

	return $flux;
}
