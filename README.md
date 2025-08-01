# CesiBloc3 — Site Pro (PHP)
- Tailwind (CDN), thème clair/sombre
- Page publique `/events/{id}` + carte + **flyer PDF**
- Admin CRUD événements + **Créer un membre**
- Compte: changer email + **code email** pour changer le mot de passe
- **API JWT** `/api/*` (login, list, detail, create)

## Setup
1. VHost -> `public/` (garder `.htaccess` / `AllowOverride All`)
2. `config/config.php` : `app_url`, DB, `jwt_secret`
3. Importer `schema.sql`
4. Login: `admin@cesi.local / admin123!`
