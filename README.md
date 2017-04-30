130words
======

A secure journaling website that encourages writing at least 130 words every day.

Deploy
------


```
export SYMFONY_ENV=prod
php composer.phar install --no-dev --optimize-autoloader
bin/console doctrine:schema:update --force
bin/console cache:clear --env=prod --no-debug
bin/console server:start
```

http://symfony.com/doc/current/deployment.html
