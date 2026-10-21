#!/usr/bin/env bash
# Execute from the root of an ISOLATED Hamqadam QA Laravel checkout.
set -euo pipefail

# Explicitly block production execution of the QA wrapper.
if grep -Eq '^APP_ENV=production' .env 2>/dev/null; then
  echo 'ERROR: never execute the QA suite against production.' >&2
  exit 3
fi

if [ ! -f artisan ]; then
  echo 'ERROR: run from your Hamqadam Laravel project root (artisan not found).' >&2
  exit 2
fi

printf '\n== Environment and migration state (do not copy secrets) ==\n'
php artisan about --only=environment
php artisan migrate:status

printf '\n== Route and schedule availability ==\n'
php artisan route:list --name=admin.client.scope
php artisan route:list --name=client.scope
php artisan route:list --name=admin.payment_refunds
php artisan route:list --name=member.payment_refunds
php artisan schedule:list

printf '\n== Blade view compile and focused automation ==\n'
php artisan view:cache
php artisan test tests/Feature/Phase10 --stop-on-failure

printf '\n== QA audit finished. Provider/browser signoff is still manual. ==\n'

