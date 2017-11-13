# 130 Words

130 Words is a private daily journaling web app.  It encourages writing at least 130 words every day.

## Deploy

```
export SYMFONY_ENV=prod
php composer.phar install --no-dev --optimize-autoloader
bin/console doctrine:schema:update --force
bin/console cache:clear --env=prod --no-debug
bin/console server:start
bin/console send-email --env=prod
```