
# 🚀 Guide d'Installation Local (XAMPP)

Suivez ces étapes pour tester votre site **HireMe** localement avec toutes les fonctionnalités (PHP, Base de données, Code Promo).

## 1. Préparer XAMPP
1. Lancez **XAMPP Control Panel**.
2. Démarrez les modules **Apache** et **MySQL**.

## 2. Déplacer les fichiers
Le site doit être servi par Apache pour que le PHP fonctionne.
1. Allez dans votre dossier d'installation XAMPP (généralement `C:\xampp\htdocs`).
2. Créez un nouveau dossier nommé `hireme`.
3. **Copiez tout le contenu** de votre dossier de projet actuel (`Cv_Services`) et collez-le dans `C:\xampp\htdocs\hireme`.

## 3. Configurer la Base de Données
1. Ouvrez votre navigateur et allez sur : [http://localhost/phpmyadmin](http://localhost/phpmyadmin)
2. Cliquez sur **Nouvelle base de données** (à gauche).
3. Nommez-la `hireme_db` et cliquez sur **Créer**.
4. Une fois dans la base, cliquez sur l'onglet **Importer** (en haut).
5. Cliquez sur "Choisir un fichier" et sélectionnez le fichier situé dans votre projet :
   `admin/sql/schema.sql`
6. Cliquez sur **Importer** (en bas de page).

> ✅ Cela va créer les tables nécessaires et un compte admin par défaut.

## 4. Tester le site
1. Accédez au site public : [http://localhost/hireme](http://localhost/hireme)
2. Accédez à l'administration : [http://localhost/hireme/admin](http://localhost/hireme/admin)
   - **Email** : `moncvpro@hiremeguide.com`
   - **Mot de passe** : `admin123`

## 5. Gestion des Codes Promo
1. Connectez-vous à l'admin.
2. Allez dans la section **Codes Promo** (ou créez-en un nouveau).
3. Créez un code (ex: `HIREME60` avec `60`%).
4. Retournez sur la page d'accueil [http://localhost/hireme](http://localhost/hireme).
   - Le bandeau de promo devrait s'afficher automatiquement.
   - Testez le formulaire de commande avec ce code.

---
**Note** : Si vous changez le mot de passe de votre base de données locale (autre que `root` / vide), pensez à mettre à jour le fichier `admin/config/db.php`.
