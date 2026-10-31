# Double authentification (2FA) pour SPIP

Ajoute un second facteur TOTP (RFC 6238) au login de SPIP 5, compatible avec toutes les applications d'authentification (Google/Microsoft Authenticator, FreeOTP, Bitwarden, 1Password…).

## Fonctionnement

- **Enrôlement** : chaque auteur active la 2FA depuis sa fiche (`ecrire/?exec=auteur&id_auteur=…`) en scannant le QR code (ou en saisissant la clé) dans son application, puis en saisissant un premier code. 10 codes de secours à usage unique sont alors affichés une seule fois.
- **Login** : après un mot de passe correct, le formulaire `#FORMULAIRE_LOGIN` demande le code TOTP (ou un code de secours). La connexion en attente expire après 5 minutes ou 5 codes invalides.
- **Réinitialisation** : un webmestre peut désactiver la 2FA d'un autre auteur depuis sa fiche (téléphone perdu).

## Sécurité

- La session n'est ouverte qu'après le second facteur : `autoriser_loger()` est surchargée et refuse toute ouverture de session (`auth_loger()`) tant que le code n'a pas été validé. Cela couvre le formulaire de login, le retour de « mot de passe oublié », l'action `cookie` et les SSO.
- L'authentification HTTP est désactivée (`$GLOBALS['ignore_auth_http'] = true`, non surchargeable) car elle ne permet pas de second facteur.
- Le secret TOTP est chiffré avec le secret du site (`config/cles.php`). Les codes de secours sont hachés (`password_hash`). Aucun champ 2FA n'est écrit dans les fichiers de session.
- Anti-rejeu : un code TOTP déjà utilisé est refusé.

## Réglages (`mes_options.php`)

| Constante | Défaut | Rôle |
|---|---|---|
| `_TWOFA_PERIODE` | 30 | durée d'un code (s) |
| `_TWOFA_CHIFFRES` | 6 | nombre de chiffres |
| `_TWOFA_FENETRE` | 1 | tolérance de décalage d'horloge (pas) |
| `_TWOFA_DUREE_ATTENTE` | 300 | validité de l'étape 2 du login (s) |
| `_TWOFA_ESSAIS_MAX` | 5 | essais de code par connexion |
| `_TWOFA_NB_CODES_SECOURS` | 10 | codes de secours générés |

## Limites connues

- Les sessions ouvertes avant l'activation de la 2FA restent valides.
- Un plugin qui surcharge aussi `autoriser_loger()` ou `inc_auth()` entre en conflit.
- Pas encore d'obligation de 2FA par statut.

## Crédits

- QR code d'enrôlement : [qrcode-generator](https://github.com/kazuhikoarase/qrcode-generator) 1.4.4 de Kazuhiko Arase (licence MIT), embarqué dans `javascript/qrcode.js`.
