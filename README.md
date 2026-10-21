# Digital_7anout — Café / Restaurant

Application de caisse PHP/MySQL pour gérer une carte de café/restaurant, les commandes et les tickets.

## Démarrer l’application

Depuis la racine du projet :

```powershell
docker compose up -d --build
```

Ouvrir <http://localhost:8080>. Pour arrêter les services : `docker compose down`.

## Mise en ligne sécurisée

Ne publiez pas le port Apache directement sur Internet. Le port Docker est limité à `127.0.0.1:8080`; placez un proxy HTTPS (par exemple Caddy ou Nginx avec un certificat TLS) devant l’application, et configurez-le pour rediriger HTTP vers HTTPS. Le proxy doit être la seule entrée publique vers ce port.

Sur l’hôte de production, définissez les variables d’environnement `APP_ENV=production` et `APP_HTTPS=1` pour le service web uniquement lorsque le proxy TLS est actif, accepte uniquement les connexions HTTPS vers l’application et que le port Docker reste inaccessible depuis le réseau public. Dans ce mode, l’application refuse les requêtes si HTTPS n’est pas déclaré. `APP_HTTPS=1` indique que le TLS est terminé par un proxy de confiance; ne l’utilisez pas si le port HTTP est exposé directement.

Les sessions utilisent des cookies `HttpOnly`, `SameSite=Lax` et, en HTTPS, `Secure`. Le mode strict des sessions est activé. Gardez les secrets de production hors du dépôt, utilisez un mot de passe de base de données unique et fort, et prévoyez des sauvegardes régulières de la base hors du serveur.

## Installer sur Windows

Ouvrez l’adresse du site dans Microsoft Edge ou Google Chrome. Le navigateur affiche son icône native d’installation à côté de la barre d’adresse lorsqu’il peut installer le site; vous pouvez aussi utiliser le menu du navigateur (**Applications** > **Installer ce site en tant qu’application** dans Edge). Une fois installée, l’application demande au navigateur de l’ouvrir en plein écran. L’installation du raccourci ne remplace pas le serveur : Docker et la connexion à la base de données doivent rester disponibles pour utiliser la caisse. Pour une adresse autre que `localhost`, le navigateur exige généralement une connexion HTTPS.

## Migration de la base existante

Les nouvelles installations appliquent le schéma café via `database.sql` lors de l’initialisation MySQL. Pour une base Docker déjà existante, appliquer la migration **une seule fois** après le démarrage des services :

```powershell
$dbContainer = docker compose ps -q db
docker cp .\migrations\20261020_cafe_restaurant.sql "${dbContainer}:/tmp/cafe-migration.sql"
docker compose exec -T db sh -c 'mysql --default-character-set=utf8mb4 -uroot -prootpassword grocery < /tmp/cafe-migration.sql'
docker compose exec -T db rm /tmp/cafe-migration.sql
```

La migration ajoute le lien direct produit/catégorie, l’activation des produits et le nom du produit figé sur chaque ligne de vente. Elle conserve les prix unitaires déjà enregistrés, archive sans supprimer les produits rattachés aux anciennes catégories « Lait », « Nettoyages » et « Huiles », conserve les produits des catégories personnalisées, et prépare les catégories Cafés, Thés, Boissons froides, Viennoiseries, Repas, Sandwichs et Desserts. Elle regroupe les anciennes catégories de pâtisseries/desserts et thés/infusions dans les catégories correspondantes.

Pour une base existante, appliquez ensuite une seule fois la migration des rôles :

```powershell
$dbContainer = docker compose ps -q db
docker cp .\migrations\20261020_user_roles.sql "${dbContainer}:/tmp/user-roles-migration.sql"
docker compose exec -T db sh -c 'mysql --default-character-set=utf8mb4 -uroot -prootpassword grocery < /tmp/user-roles-migration.sql'
docker compose exec -T db rm /tmp/user-roles-migration.sql
```

Elle convertit les anciens rôles `owner`/`assistant` en `admin`/`server` sans modifier les mots de passe ni l’historique des ventes.

Pour une base Docker existante, appliquez aussi une seule fois la migration des produits, des images et des catégories :

```powershell
$dbContainer = docker compose ps -q db
docker cp .\migrations\20261021_cafe_products_and_categories.sql "${dbContainer}:/tmp/cafe-products-migration.sql"
docker compose exec -T db sh -c 'mysql --default-character-set=utf8mb4 -uroot -prootpassword grocery < /tmp/cafe-products-migration.sql'
docker compose exec -T db rm /tmp/cafe-products-migration.sql
```

Elle prépare les anciennes eaux et associe les photos de la carte aux produits reconnus par leur nom. La migration suivante règle les prix définitifs et ajoute les boissons demandées.

Pour une base Docker existante, appliquez ensuite une seule fois la migration du catalogue boissons :

```powershell
$dbContainer = docker compose ps -q db
docker cp .\migrations\20261021_cafe_drinks_catalog.sql "${dbContainer}:/tmp/cafe-drinks-migration.sql"
docker compose exec -T db sh -c 'mysql --default-character-set=utf8mb4 -uroot -prootpassword grocery < /tmp/cafe-drinks-migration.sql'
docker compose exec -T db rm /tmp/cafe-drinks-migration.sql
```

Elle ajoute ou met à jour Eau 33 cl (3 DH) et Eau 1,5 L (9 DH) dans Boissons froides, Eau 33 cl avec café (2 DH) et Café noir (9 DH) dans Cafés, Jus d’orange et Panaché (16 DH chacun), et Limonade (13 DH). Les jus et la limonade sont placés dans Boissons froides. Les catégories sans produit sont supprimées; les marques liées à ces catégories sont conservées et détachées.

Pour appliquer les derniers prix de café à une base existante, copiez et exécutez aussi cette migration :

```powershell
$dbContainer = docker compose ps -q db
docker cp .\migrations\20261021_cafe_coffee_prices.sql "${dbContainer}:/tmp/cafe-coffee-prices.sql"
docker compose exec -T db sh -c 'mysql --default-character-set=utf8mb4 -uroot -prootpassword grocery < /tmp/cafe-coffee-prices.sql'
docker compose exec -T db rm /tmp/cafe-coffee-prices.sql
```

Elle règle le Cappuccino à 10 DH, le Café au lait et l’Espresso à 9 DH, et archive le Latte Macchiato et le Café noir sans supprimer l’historique des ventes.

Pour ne conserver que le Pain au Chocolat à 4 DH dans la catégorie Desserts d’une base existante, appliquez aussi une seule fois :

```powershell
$dbContainer = docker compose ps -q db
docker cp .\migrations\20261021_desserts_pain_au_chocolat_only.sql "${dbContainer}:/tmp/desserts-catalog.sql"
docker compose exec -T db sh -c 'mysql --default-character-set=utf8mb4 -uroot -prootpassword grocery < /tmp/desserts-catalog.sql'
docker compose exec -T db rm /tmp/desserts-catalog.sql
```

Les autres produits de Desserts sont archivés afin de rester invisibles dans la caisse, tout en conservant les lignes de ventes historiques.

Pour renommer les produits Snacks d’une base existante, appliquez aussi une seule fois :

```powershell
$dbContainer = docker compose ps -q db
docker cp .\migrations\20261021_snacks_rename.sql "${dbContainer}:/tmp/snacks-rename.sql"
docker compose exec -T db sh -c 'mysql --default-character-set=utf8mb4 -uroot -prootpassword grocery < /tmp/snacks-rename.sql'
docker compose exec -T db rm /tmp/snacks-rename.sql
```

La migration renomme Sandwich en Petit-déjeuner (25 DH) et Panini en Khlea (8 DH), en conservant leurs identifiants et leurs historiques de ventes.

Pour mettre à jour le Toast dans une base existante, appliquez aussi :

```powershell
$dbContainer = docker compose ps -q db
docker cp .\migrations\20261021_toast_cheese.sql "${dbContainer}:/tmp/toast-cheese.sql"
docker compose exec -T db sh -c 'mysql --default-character-set=utf8mb4 -uroot -prootpassword grocery < /tmp/toast-cheese.sql'
docker compose exec -T db rm /tmp/toast-cheese.sql
```

Cette migration le renomme Toast au fromage, règle son prix à 8 DH et associe l’image du petit-déjeuner au fromage.

Pour renommer la catégorie Desserts en Croissantes dans une base existante, appliquez aussi :

```powershell
$dbContainer = docker compose ps -q db
docker cp .\migrations\20261021_rename_desserts_to_croissantes.sql "${dbContainer}:/tmp/rename-desserts-category.sql"
docker compose exec -T db sh -c 'mysql --default-character-set=utf8mb4 -uroot -prootpassword grocery < /tmp/rename-desserts-category.sql'
docker compose exec -T db rm /tmp/rename-desserts-category.sql
```

Cette migration renomme la catégorie sans modifier les produits ni leurs historiques.

Pour mettre à jour le catalogue de thés d’une base existante, appliquez aussi :

```powershell
$dbContainer = docker compose ps -q db
docker cp .\migrations\20261021_tea_catalog.sql "${dbContainer}:/tmp/tea-catalog.sql"
docker compose exec -T db sh -c 'mysql --default-character-set=utf8mb4 -uroot -prootpassword grocery < /tmp/tea-catalog.sql'
docker compose exec -T db rm /tmp/tea-catalog.sql
```

Cette migration renomme Thé Noir en Thé (10 DH), Infusion en Thé moyenne (12 DH) et archive Thé à la Menthe pour éviter les doublons, en préservant les historiques de ventes. Elle ne modifie pas les eaux.

Pour ajouter l’eau 33 cl à 2 DH dans la catégorie Thés d’une base existante, appliquez :

```powershell
$dbContainer = docker compose ps -q db
docker cp .\migrations\20261021_tea_catalog.sql "${dbContainer}:/tmp/tea-catalog.sql"
docker compose exec -T db sh -c 'mysql --default-character-set=utf8mb4 -uroot -prootpassword grocery < /tmp/tea-catalog.sql'
docker compose exec -T db rm /tmp/tea-catalog.sql
```

## Utilisation

- **Produits / Carte** : utilisez le formulaire « Ajouter / Modifier Produit » pour créer un produit avec son nom, sa catégorie, son prix en DH et, facultativement, un code ou une image. Les anciens produits archivés peuvent être réactivés en les modifiant. « Retirer » les archive sans effacer les ventes associées.
- **Catégories** : ajoutez ou modifiez des catégories; une catégorie contenant des produits actifs ne peut pas être supprimée.
- **Caisse** : choisissez une catégorie, sélectionnez un produit, ajustez les quantités, choisissez le paiement et validez. Les prix sont relus côté serveur au moment de valider. Aucun stock n’est décompté pour les commandes de restauration.
- **Utilisateurs et rôles** : l’administrateur peut ajouter des comptes serveur avec un mot de passe (4 caractères minimum) dans Paramètres. Un serveur voit uniquement Caisse, Rapports jour/semaine, Paiements, Tickets et Déconnexion; ses rapports, paiements et tickets sont limités à ses propres ventes. Les pages d’administration sont également protégées par rôle, même si leur URL est saisie directement.
- **Attribution des commandes** : chaque nouvelle vente est enregistrée avec l’identifiant de l’utilisateur connecté pour les rapports et l’historique; le nom du serveur n’est pas affiché sur le ticket imprimé.
- **Tickets** : chaque ticket conserve le nom, la quantité et le prix unitaire appliqués à la commande. La page ticket est imprimable et présente le Wi‑Fi en bas.
- **Paramètres** : dans « Facturation, Wi-Fi & Impression », renseignez le SSID et le mot de passe, personnalisez l’en-tête et le pied de ticket et activez l’impression automatique si souhaité.
- **Rapports jour / semaine** : depuis le menu, choisissez aujourd’hui, cette semaine ou une période personnalisée. Le rapport des ventes affiche aussi les détails des produits vendus par date, avec quantité, prix unitaire historique et montant. Les rapports peuvent être imprimés ou exportés en CSV.

Les catégories sont prêtes après migration; ajoutez les produits et prix réels du café depuis le formulaire de gestion avant de prendre des commandes.
