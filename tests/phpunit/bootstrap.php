<?php

chdir(__DIR__ . '/../../');

require 'app/bootstrap.php.cache';

// drop and recreate database (use migrations)
passthru('php app/console doctrine:query:sql "DROP SCHEMA IF EXISTS public cascade; CREATE SCHEMA IF NOT EXISTS public;" --env=test');
passthru('php app/console doctrine:migrations:migrate --no-interaction --env=test');

