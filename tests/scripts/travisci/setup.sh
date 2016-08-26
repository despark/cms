#!/usr/bin/env bash

mysql -e 'create database IF NOT EXISTS '$DB_DATABASE';' -u root
mysql -e "grant all privileges on *.* to '$DB_USERNAME'@'localhost' with grant option;" -u root

if [ ! -f "laravel/composer.json" ]; then
    printenv TRAVIS_COMMIT_RANGE
    rm -rf laravel
    composer create-project laravel/laravel
    cd laravel
    composer update
    composer require $TRAVIS_REPO_SLUG:dev-master#$TRAVIS_COMMIT
    if [[ -v PACKAGE_PROVIDER ]]; then
        echo "$(awk '/'\''providers'\''[^\n]*?\[/ { print; print "'$(sed -e 's/\s*//g' <<<${PACKAGE_PROVIDER})',"; next }1' \
            config/app.php)" > config/app.php
    fi
    if [[ -v SEED_CLASS ]]; then
        echo "$(cat database/seeds/DatabaseSeeder.php | \
            sed ':a;N;$!ba;s/\(public function run().*\?{\)/\1\n\t\$this->call('$SEED_CLASS');/g')" \
            > database/seeds/DatabaseSeeder.php
    fi
    php artisan vendor:publish
    cd ..
fi