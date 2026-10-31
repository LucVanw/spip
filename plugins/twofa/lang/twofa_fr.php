<?php
// This is a SPIP language file  --  Ceci est un fichier langue de SPIP

return [

	// A
	'aide_cle_droits' => '<strong>Rights</strong> : un droit par ligne (bouton +), méthode à gauche et chemin à droite. Normalement déjà pré-remplis :',
	'aide_cle_ip' => '<strong>Restricted IPs</strong> : facultatif, l’adresse IP de ce serveur pour plus de sécurité.',
	'aide_cle_nom' => '<strong>Application name</strong> : SPIP 2FA, <strong>Application description</strong> : codes de connexion par SMS.',
	'aide_cle_validite' => '<strong>Validity</strong> : <em>Unlimited</em>, sinon les SMS cesseront de partir à l’expiration de la clé.',
	'alt_qrcode' => 'QR code à scanner avec votre application d’authentification',

	// B
	'bouton_activer' => 'Activer la double authentification',
	'bouton_activer_sms' => 'Activer par SMS',
	'bouton_annuler' => 'Annuler',
	'bouton_commander_credits' => 'Commander ces crédits',
	'bouton_creer_cle' => 'Créer la clé API chez OVHcloud',
	'bouton_desactiver' => 'Désactiver la double authentification',
	'bouton_effacer_config' => 'Supprimer la configuration',
	'bouton_enregistrer_cles' => 'Enregistrer et vérifier',
	'bouton_envoyer_sms' => 'M’envoyer un code par SMS',
	'bouton_manager' => 'Mon compte SMS chez OVHcloud',
	'bouton_payer_commande' => 'Payer sur OVHcloud',
	'bouton_regenerer_codes' => 'Générer de nouveaux codes de secours',
	'bouton_regenerer_secret' => 'Générer une autre clé',
	'bouton_reinitialiser' => 'Réinitialiser la double authentification',
	'bouton_renvoyer_sms' => 'Renvoyer le code',
	'bouton_sms_test' => 'Envoyer un SMS de test',
	'bouton_verifier' => 'Vérifier',

	// C
	'confirmer_reinitialiser' => 'Cet auteur pourra se connecter avec son seul mot de passe. Continuer ?',

	// E
	'erreur_action' => 'Action inconnue.',
	'erreur_api' => 'L’API OVHcloud ne répond pas comme prévu :',
	'erreur_attente_expiree' => 'La vérification a expiré. Identifiez-vous à nouveau.',
	'erreur_aucun_compte_sms' => 'Clés valides, mais aucun compte SMS sur ce compte OVHcloud. Commandez d’abord un pack de SMS sur https://www.ovhcloud.com/fr/sms/ puis enregistrez à nouveau.',
	'erreur_cles_refusees' => 'OVHcloud refuse ces clés : @erreur@',
	'erreur_code' => 'Code invalide.',
	'erreur_code_activation' => 'Code invalide. Vérifiez que l’heure de votre téléphone est juste et saisissez le code affiché par l’application.',
	'erreur_commande' => 'La commande n’a pas pu être créée : @erreur@',
	'erreur_config_constantes' => 'La configuration est imposée dans mes_options.php et ne peut pas être modifiée ici.',
	'erreur_quantite' => 'Quantité invalide.',
	'erreur_sender' => '11 caractères maximum, lettres, chiffres et espaces.',
	'erreur_sms_delai' => 'Patientez @nb@ secondes avant de demander un nouveau code.',
	'erreur_sms_envoi' => 'Le SMS n’a pas pu être envoyé. Réessayez dans un instant ou utilisez un code de secours.',
	'erreur_sms_indisponible' => 'L’envoi de codes par SMS n’est pas configuré sur ce site.',
	'erreur_sms_quota' => 'Trop de SMS envoyés pour ce compte. Réessayez dans une heure ou utilisez un code de secours.',
	'erreur_sms_test' => 'Le SMS de test n’est pas parti : @erreur@',
	'erreur_sms_trop_envois' => 'Nombre maximal d’envois atteint. Utilisez le dernier code reçu ou un code de secours.',
	'erreur_telephone' => 'Numéro de téléphone mobile invalide.',
	'erreur_trop_essais' => 'Trop de codes invalides. Identifiez-vous à nouveau.',
	'explication_activation' => 'Scannez ce QR code avec votre application d’authentification (Google Authenticator, Microsoft Authenticator, FreeOTP, Bitwarden…), ou saisissez la clé ci-dessous, puis indiquez le code à 6 chiffres qu’elle affiche.',
	'explication_active' => 'La double authentification est active : un code vous est demandé après votre mot de passe. Pour modifier ce réglage, indiquez un code valide ou un code de secours.',
	'explication_code_secours' => 'Vous n’avez pas votre téléphone ? Saisissez un de vos codes de secours.',
	'explication_codes_secours' => 'Conservez ces codes de secours en lieu sûr. Chacun permet de se connecter une seule fois sans votre téléphone. Ils ne seront plus affichés.',
	'explication_commande' => 'Crée un bon de commande chez OVHcloud, à payer ensuite sur leur site. 1 crédit = 1 SMS en France.',
	'explication_creer_cle' => 'Connectez-vous avec le compte OVHcloud qui possède le compte SMS, puis remplissez le formulaire :',
	'explication_login' => 'Saisissez le code à 6 chiffres affiché par votre application d’authentification.',
	'explication_login_sms' => 'Saisissez le code à 6 chiffres envoyé par SMS au @telephone@.',
	'explication_page_configurer' => 'Pour proposer aux auteurs de recevoir leurs codes de connexion par SMS, reliez ce site à un compte SMS OVHcloud. L’application d’authentification fonctionne sans aucune configuration.',
	'explication_secret_enregistre' => 'Déjà enregistré : laissez vide pour le conserver.',
	'explication_sender' => 'Facultatif. Nom d’expéditeur déclaré et validé chez OVHcloud. Vide : un numéro court est utilisé.',
	'explication_sms_activation' => 'Les codes vous seront envoyés par SMS à chaque connexion. Moins sûr qu’une application, mais sans rien à installer.',

	// I
	'info_active_auteur' => 'La double authentification est active pour cet auteur.',
	'info_compte_sms' => 'Compte SMS <strong>@service@</strong> : <strong>@credits@</strong> crédits restants.',
	'info_config_constantes' => 'La configuration SMS est définie dans config/mes_options.php.',
	'info_inactive_auteur' => 'La double authentification n’est pas active pour cet auteur.',
	'info_methode_sms' => 'Les codes sont envoyés par SMS au @telephone@.',
	'info_nb_codes_secours' => 'Codes de secours restants : @nb@.',

	// L
	'label_code' => 'Code de vérification',
	'label_code_activation' => 'Code affiché par l’application',
	'label_code_confirmation' => 'Code de l’application ou code de secours',
	'label_code_confirmation_sms' => 'Code reçu par SMS ou code de secours',
	'label_code_sms' => 'Code reçu par SMS',
	'label_quantite' => 'Acheter des crédits SMS',
	'label_secret' => 'Clé :',
	'label_sender' => 'Expéditeur',
	'label_service' => 'Compte SMS',
	'label_telephone' => 'Numéro de téléphone mobile',
	'label_telephone_test' => 'Votre numéro de mobile',
	'legend_etape_cle' => '1. Créer la clé API',
	'legend_etape_coller' => '2. Coller les clés obtenues',
	'legend_etape_compte' => '3. Tester et recharger',
	'lien_ouvrir_application' => 'Ouvrir dans l’application d’authentification',

	// M
	'message_active' => 'La double authentification est activée.',
	'message_active_sms' => 'La double authentification par SMS est activée.',
	'message_codes_regeneres' => 'De nouveaux codes de secours ont été générés. Les précédents ne sont plus valables.',
	'message_commande' => 'Bon de commande n° @numero@ créé (@prix@ TTC).',
	'message_config_effacee' => 'Configuration SMS supprimée. Les auteurs utilisant le SMS ne recevront plus de codes : ils peuvent utiliser leurs codes de secours.',
	'message_config_enregistree' => 'Clés vérifiées et enregistrées. Compte SMS utilisé : @service@.',
	'message_desactive' => 'La double authentification est désactivée.',
	'message_sms_envoye' => 'Un code a été envoyé par SMS au @telephone@.',
	'message_sms_renvoye' => 'Un nouveau code vous a été envoyé par SMS.',
	'message_sms_test' => 'SMS de test envoyé au @telephone@.',

	// S
	'sms_message' => '@code@ est votre code de connexion à @site@. Il expire dans @minutes@ minutes. Ne le communiquez à personne.',
	'sms_message_test' => 'SPIP : la double authentification par SMS fonctionne.',

	// T
	'titre_config_sms' => 'Codes de connexion par SMS (OVHcloud)',
	'titre_menu_configurer' => 'Double authentification',
	'titre_page_configurer' => 'Double authentification',
	'titre_sms' => 'Recevoir les codes par SMS plutôt',
	'titre_twofa' => 'Double authentification',
	'titre_verification' => 'Vérification en deux étapes',
];
