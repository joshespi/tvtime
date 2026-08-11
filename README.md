# TV Tracker

Self-hostable replacement for TV Time. Track what you're watching, get notified when a new episode airs.

- Track shows and movies, sourced from TMDB
- "Up Next" dashboard, a calendar view of upcoming air dates/releases, and a combined watch history
- Mark episodes/movies watched individually, or a whole run of episodes at once
- Email + web push notifications when a tracked show airs a new episode
- Installable as a PWA

## Local setup

```bash
cp .env.example .env
# fill in TMDB_API_KEY (free — see .env.example for the link)

docker compose build
docker compose up -d
```

The `app` container's entrypoint runs `composer install` and `migrate` on
every start/rebuild — no manual migrate step needed. It also caches config
and routes via `optimize`, but only when `APP_ENV=production`.

Frontend assets aren't built by the containers (there's no Node in the PHP
image). Run once, and again whenever you touch `resources/js` or
`resources/css`:

```bash
docker compose run --rm node sh -c "npm install && npm run build"
```
