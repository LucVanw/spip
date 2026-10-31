# Double authentification (2FA) pour SPIP

Ajoute un second facteur au login de SPIP 5 : code d'une application d'authentification (TOTP, RFC 6238 : Google/Microsoft Authenticator, FreeOTP, Bitwarden, 1Password…) ou code reçu par SMS via l'API OVHcloud.

## Fonctionnement

- **Enrôlement** : chaque auteur active la 2FA depuis sa fiche (`ecrire/?exec=auteur&id_auteur=…`) :
  - **application** : scanner le QR code (ou saisir la clé), puis saisir un premier code ;
  - **SMS** (si configuré) : saisir son numéro de mobile, puis le code reçu.
  
  10 codes de secours à usage unique sont alors affichés une seule fois.
- **Login** : après un mot de passe correct, le formulaire `#FORMULAIRE_LOGIN` demande le code de l'application, ou envoie automatiquement un SMS (bouton « Renvoyer le code »). Un code de secours est toujours accepté. La connexion en attente expire après 5 minutes ou 5 codes invalides.
- **Réinitialisation** : un webmestre peut désactiver la 2FA d'un autre auteur depuis sa fiche (téléphone perdu).

## Configurer les SMS (webmestre)

**Configuration > Double authentification** dans l'espace privé, en 3 étapes :

1. **Créer la clé API** : le bouton ouvre la page OVHcloud de création de clé avec les droits pré-remplis. Choisir *Validity : Unlimited*. Droits nécessaires :
   - `GET /sms`, `GET /sms/*`, `GET /order/sms/*`
   - `POST /sms/*/jobs`, `POST /order/sms/*/credits`
2. **Coller les 3 clés** (Application key, Application secret, Consumer key) : elles sont vérifiées auprès d'OVHcloud, le compte SMS est détecté automatiquement.
3. **Tester et recharger** : crédits restants, SMS de test, bon de commande de crédits (payé ensuite sur OVHcloud).

Il faut un compte SMS OVHcloud existant (pack de crédits : https://www.ovhcloud.com/fr/sms/). Les secrets sont chiffrés avec la clé du site. La configuration peut aussi être imposée dans `config/mes_options.php`, prioritaire sur l'interface :

```php
define('_TWOFA_OVH_APPLICATION_KEY', '…');
define('_TWOFA_OVH_APPLICATION_SECRET', '…');
define('_TWOFA_OVH_CONSUMER_KEY', '…');
define('_TWOFA_OVH_SMS_SERVICE', 'sms-xx12345-1');
define('_TWOFA_OVH_SMS_SENDER', 'MONSITE'); // facultatif : expéditeur validé chez OVHcloud
define('_TWOFA_OVH_ENDPOINT', 'ovh-eu');    // facultatif : ovh-eu, ovh-ca, ovh-us
```

## Sécurité

- La session n'est ouverte qu'après le second facteur : `autoriser_loger()` est surchargée et refuse toute ouverture de session (`auth_loger()`) tant que le code n'a pas été validé. Cela couvre le formulaire de login, le retour de « mot de passe oublié », l'action `cookie` et les SSO.
- L'authentification HTTP est désactivée (`$GLOBALS['ignore_auth_http'] = true`, non surchargeable) car elle ne permet pas de second facteur.
- Le secret TOTP et les clés OVHcloud sont chiffrés avec le secret du site (`config/cles.php`). Les codes de secours et les codes SMS sont hachés (`password_hash`). Aucun champ 2FA n'est écrit dans les fichiers de session.
- Anti-rejeu : un code TOTP déjà utilisé est refusé, un code SMS ne sert qu'une fois.
- SMS : 3 envois par connexion, 30 s entre deux envois, 10 SMS par auteur et par heure (protège le crédit), 5 essais par code.
- Le SMS est moins sûr qu'une application (interception, SIM swap) : à réserver aux auteurs qui ne peuvent pas installer d'application.

## Réglages (`mes_options.php`)

| Constante | Défaut | Rôle |
|---|---|---|
| `_TWOFA_PERIODE` | 30 | durée d'un code TOTP (s) |
| `_TWOFA_CHIFFRES` | 6 | nombre de chiffres |
| `_TWOFA_FENETRE` | 1 | tolérance de décalage d'horloge (pas) |
| `_TWOFA_DUREE_ATTENTE` | 300 | validité de l'étape 2 du login (s) |
| `_TWOFA_ESSAIS_MAX` | 5 | essais de code par connexion |
| `_TWOFA_NB_CODES_SECOURS` | 10 | codes de secours générés |
| `_TWOFA_SMS_DUREE` | 300 | validité d'un code SMS (s) |
| `_TWOFA_SMS_ENVOIS_MAX` | 3 | SMS par connexion |
| `_TWOFA_SMS_DELAI_RENVOI` | 30 | délai entre deux SMS (s) |
| `_TWOFA_SMS_MAX_HEURE` | 10 | SMS par auteur et par heure |
| `_TWOFA_SMS_INDICATIF` | 33 | indicatif des numéros saisis en national (06…) |

## Limites connues

- Les sessions ouvertes avant l'activation de la 2FA restent valides.
- Un plugin qui surcharge aussi `autoriser_loger()` ou `inc_auth()` entre en conflit.
- Pas encore d'obligation de 2FA par statut.

## Crédits

- QR code d'enrôlement : [qrcode-generator](https://github.com/kazuhikoarase/qrcode-generator) 1.4.4 de Kazuhiko Arase (licence MIT), embarqué dans `javascript/qrcode.js`.
