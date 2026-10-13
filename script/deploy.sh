#!/usr/bin/env bash
# Pull the latest main into this checkout and bring the running stack up to
# date. Run from the repo root on the server:
#
#   bash script/deploy.sh
#
# The per-step work (composer, migrate, assets, caches) lives in
# docker/php/app-bootstrap.sh, which the app entrypoint runs on every start —
# so this script only has to make sure it runs once against the new code.
#
# Everything is inside main() so bash parses the whole file up front: the
# `git merge` below can rewrite this very script mid-run, and bash otherwise
# reads scripts lazily line by line.
set -euo pipefail

main() {
  local root_dir branch
  root_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
  branch="${DEPLOY_BRANCH:-main}"
  cd "$root_dir"

  # --- 1. Pull ---------------------------------------------------------------
  # Refuse to deploy over hand-edits to tracked files; a merge would either
  # fail halfway or silently keep a stale hotfix. Untracked files (.env,
  # db_data, node_modules, public/build) are fine.
  if [[ -n "$(git status --porcelain --untracked-files=no)" ]]; then
    echo "ERROR: tracked files were edited on this server — commit or discard them first:" >&2
    git status --short --untracked-files=no >&2
    exit 1
  fi

  local old new
  old="$(git rev-parse HEAD)"
  git fetch --quiet origin "$branch"
  git merge --ff-only --quiet "origin/$branch"
  new="$(git rev-parse HEAD)"
  echo "[deploy] ${old:0:7} -> ${new:0:7}"

  # default.conf is bind-mounted, so the merge just changed the live file —
  # check it before anything else moves, rather than finding out at the reload
  # in step 3 with the app already updated.
  if [[ -n "$(docker compose ps -q --status running nginx)" ]]; then
    docker compose exec -T nginx nginx -t -q
  fi

  # --- 2. Containers ---------------------------------------------------------
  # Compose decides what needs rebuilding/recreating (Dockerfile, compose file,
  # .env changes) from its build cache, and --wait blocks on app's healthcheck,
  # i.e. until the entrypoint's app-bootstrap has finished. Buildx's default
  # attestations are timestamped, so leaving them on mints a new image ID on
  # every fully-cached build and compose would recreate the PHP containers on
  # every deploy.
  local before after
  before="$(docker compose ps -q app)"
  echo "[deploy] docker compose up --build"
  BUILDX_NO_DEFAULT_ATTESTATIONS=1 docker compose up -d --build --remove-orphans --wait
  after="$(docker compose ps -q app)"

  # --- 3. App ----------------------------------------------------------------
  # A recreated container already ran app-bootstrap (and its workers are
  # fresh); otherwise run it now and tell the long-lived queue workers to pick
  # up the new code.
  if [[ "$before" == "$after" ]]; then
    docker compose exec -T app sh -c 'app-bootstrap && php artisan queue:restart'
  fi
  # default.conf is bind-mounted, so compose never sees edits to it.
  docker compose exec -T nginx nginx -s reload

  # --- 4. Smoke test ---------------------------------------------------------
  # Hit Laravel's built-in /up health route through nginx.
  local url
  url="http://localhost:$(docker compose port nginx 80 | cut -d: -f2)/up"
  if curl -fsS -o /dev/null --max-time 15 "$url"; then
    echo "[deploy] /up OK — deployed ${new:0:7}"
  else
    echo "ERROR: health check $url failed" >&2
    docker compose logs --tail 50 app >&2
    exit 1
  fi
}

main "$@"
exit
