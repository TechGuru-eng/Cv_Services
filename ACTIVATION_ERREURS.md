# Configuration des Pages d'Erreur (Hostinger / .htaccess)

Pour que vos visiteurs voient la belle page d'erreur `error.html` au lieu des pages grises par défaut, vous devez configurer le fichier `.htaccess`.

## 1. Créer ou Modifier le fichier `.htaccess`
Ce fichier doit se trouver dans le dossier `public_html` (la racine de votre site).

## 2. Ajoutez le code suivant :

```apache
ErrorDocument 404 /error.html#404
ErrorDocument 500 /error.html#500
ErrorDocument 403 /error.html#general
```

## 3. C'est tout !
Désormais, si quelqu'un tape une mauvaise adresse, il sera redirigé vers votre page personnalisée.
