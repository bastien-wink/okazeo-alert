#!/bin/bash
set -e

/usr/bin/php ./bin/console doctrine:migrations:migrate --no-interaction
/usr/bin/php ./bin/console doctrine:schema:validate
