#!/bin/bash -l

cd ${APP_HOME}
/usr/bin/php bin/console okkazeo:alert -vvv
/usr/bin/php bin/console garage:alert -vvv