# SdzBlog
Voici le code source compatible Symfony3 du célèbre blog "SdzBlog" construit grâce au [cours du Site du Zéro](http://www.siteduzero.com/informatique/tutoriels/developpez-votre-site-web-avec-le-framework-symfony2) de Winzou.


# Installation
## 1. Récupérer le code
Vous avez deux solutions pour le faire :

1. Via Git, en clonant ce dépôt ;
2. Via le téléchargement du code source en une archive ZIP, à cette adresse : https://github.com/tvequaud/SdzBlog/archive/master.zip

## 2. Définir vos paramètres d'application
Pour ne pas qu'on se partage tous nos mots de passe, le fichier `app/config/parameters.yml` est ignoré dans ce dépôt. A la place, vous avez le fichier `parameters.yml.dist` que vous devez renommer (enlevez le `.dist`) et modifier.

## 3. Télécharger les vendors
Avec Composer bien évidemment :
    php composer.phar install

## Et profitez !
