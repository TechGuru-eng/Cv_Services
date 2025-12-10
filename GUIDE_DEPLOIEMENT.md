# 🚀 Guide de Déploiement sur Hostinger

Votre site **HireMe CV Services** est prêt. Suivez ces étapes pour le mettre en ligne.

## 1. Préparation des Fichiers
Le dossier actuel contient tout le nécessaire :
*   `index.html` (Page d'accueil)
*   `paiement.html`, `details-cv.html`, `merci.html` (Tunnel de vente)
*   `conditions-generales.html`, `politique-confidentialite.html` (Pages légales)
*   `send-confirmation.php` (Script d'envoi d'email)
*   `assets/` (Images et Favicons)
*   `vendor/` (Bibliothèque PHPMailer - **IMPORTANT**)

## 2. Connexion à Hostinger
1.  Connectez-vous à votre **hPanel Hostinger**.
2.  Allez dans **Fichiers > Gestionnaire de fichiers (File Manager)**.
3.  Ouvrez le dossier **public_html**.

## 3. Upload du Site
1.  Supprimez le fichier `default.php` s'il existe.
2.  **Uploadez tous les fichiers et dossiers** de votre dossier de projet vers `public_html`.

## 4. Installation de PHPMailer (Crucial)
Comme Composer n'est peut-être pas installé par défaut :
1.  Une fois les fichiers uploadés, **visitez cette adresse dans votre navigateur** :
    `https://moncvpro.hiremeguide.com/install_mailer.php`
2.  Vous devriez voir "Installation terminée".
3.  Retournez dans votre gestionnaire de fichiers Hostinger et **supprimez le fichier `install_mailer.php`** (c'est une mesure de sécurité).

## 5. Vérification Finale
1.  Ouvrez votre site : `https://moncvpro.hiremeguide.com`
2.  **Testez le processus complet** :
    *   Passez une commande fictive.
    *   Allez jusqu'au formulaire de détails.
    *   Validez le formulaire.
    *   Vérifiez que vous êtes redirigé vers la page "Merci".
    *   Vérifiez votre boite mail `newsletter@hiremeguide.com` (ou celle du client test) pour voir si l'email de confirmation est arrivé.

## 5. Dépannage (Emails)
Si les emails ne partent pas :
*   Vérifiez que le fichier `send-confirmation.php` contient bien vos mots de passe (ne les partagez jamais).
*   Vérifiez dans Hostinger que votre mot de passe email n'a pas changé.
*   Consultez le fichier `DNS_SETUP.md` pour configurer SPF/DKIM et éviter les SPAMS.

---
**Félicitations ! Votre business est en ligne.** 🚀
