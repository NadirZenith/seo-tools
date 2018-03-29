#!/usr/bin/env sh

# this script is responsible to deploy(install)
# the app in the current machine

. $(dirname "$0")/functions.sh

#Check parameters
if [ $# -eq 0 ]
then
    display_error "You must set an environment (dev|test|prod)"
    die
else
    pwd=`pwd`
    display_info "Deploying cwd: $pwd"
    display_success "Environment:    $1"
fi

#Check php binary
if [ ! -x 'bin/php' ]
then
    display_error "PHP CLI not found "
    display_info "Do you forgot to create a link? (ln -s /usr/bin/php bin/php)"
    die
else
    version=`bin/php -v | grep cli`
    display_success "PHP:      $version"
fi

#Check Composer binary
if [ ! -x 'bin/composer' ]
then
    display_error "Composer not found in 'bin' folder"
    display_info "Do you forgot to create a link? (ln -s /usr/local/bin/composer bin/composer)"
    die
else
    version=`bin/composer -V`
    display_success "Composer: $version"
fi

#PhpCS binary
if [ ! -x 'bin/phpcs' ]
then
    display_error "Php Code Sniffer not found in 'bin' folder"
    display_info "Do you forgot to create a link? (ln -s /usr/bin/phpcs bin/phpcs)"
    die
else
    version=`bin/phpcs --version`
    display_success "Php Code Sniffer: $version"
fi

#PhpMD binary
if [ ! -x 'bin/phpmd' ]
then
    display_error "Php Mess Detector not found in 'bin' folder"
    display_info "Do you forgot to create a link? (ln -s /usr/bin/phpmd bin/phpmd)"
    die
else
    version=`bin/phpmd --version`
    display_success "Php Mess Detector: $version"
fi

##phpdoc binary
## version conflict when composer require phpdocumentor/phpdocumentor --dev
#if [ ! -x 'bin/phpdoc' ]
#then
#    display_error "PhpDocumentor not found in 'bin' folder"
#    display_info "Do you forgot to create a link? (ln -s /usr/bin/phpdoc bin/phpdoc)"
#    die
#else
#    version=`bin/phpdoc --version`
#    display_success "Php Documentor: $version"
#fi

#metrics binary
if [ ! -x 'bin/phpmetrics' ]
then
    display_error "Php Metrics not found in 'bin' folder"
    display_info "Do you forgot to create a link? (ln -s /usr/bin/phpmetrics bin/phpmetrics)"
    die
else
    version=`bin/phpmetrics --version`
    display_success "Php Metrics: $version"
fi

##Check NODE binary
#if [ ! -x 'bin/node' ]
#then
#    display_error "NODE not found at 'bin' folder"
#    display_info "Do you forgot to create a link? (ln -s /usr/local/bin/node bin/node)"
#    die
#else
#    version=`bin/node -v`
#    display_success "Node:     $version"
#fi
#
##Check NPM binary
#if [ ! -x 'bin/npm' ]
#then
#    display_error "NPM not found at 'bin' folder"
#    display_info "Do you forgot to create a link? (ln -s /usr/local/bin/npm bin/npm)"
#    die
#else
#    version=`bin/npm -v`
#    min='2.0'
#    if version_lt $min $version; then
#        display_success "NPM:      $version"
#    else
#        display_error "Old npm version found: $version, require +$min"
#        die
#    fi
#fi

display_info 'Check validators'
dumps=`find src/ -type f -print0 | xargs -0 grep -l "dump("`
if [ ! -z "$dumps" ]
then
    display_error "Remove dump() function from:\n$dumps"
    die
else
    display_success "* dump() calls not found in src/"
fi

dumps=`find app/Resources/views/ -type f -print0 | xargs -0 grep -l "dump("`
if [ ! -z "$dumps" ]
then
    display_error "Remove dump() function from:\n$dumps"
    die
else
    display_success "* dump() calls not found in app/Resources/views"
fi

# auto fixer example
#phpcbf=`bin/phpcbf --standard=build/phpcs.xml src/`
phpcs_summary=`bin/phpcs --standard=build/phpcs.xml -n --report=summary src/`
if [ ! -z "$phpcs_summary" ]
then
    phpcs=`bin/phpcs --standard=build/phpcs.xml -n src/`
    display_error "Fix phpcs errors:\n$phpcs"
#    die
else
    display_success "No Php Code Sniffer errors"
fi

phpmd=`bin/phpmd src/ text build/phpmd.xml`
if [ ! -z "$phpmd" ]
then
    display_error "Fix phpmd errors:\n$phpmd"
    die
else
    display_success "No Php Mess Detector errors"
fi

display_info "Generating phpmetrics in build/phpmetrics:"
bin/phpmetrics --report-html=build/phpmetrics src

#display_info "Generating documentation in build/docs:"
#bin/phpdoc -p -n --sourcecode --title="Seo Tools" -d ./src -t build/docs

#Check for htaccess(apache only)
#if [ ! -e 'web/.htaccess' ]
#then
#    cp web/.htaccess.dist web/.htaccess
#    display_info ".htaccess generated"
#fi
#Check for robots
#if [ ! -e 'web/robots.txt' ]
#then
#    cp web/robots.txt.dist web/robots.txt
#    display_info "robots.txt generated"
#fi

#display_success "------------ Start deploy --------------"
#die;
if [ $1 = 'dev' ]
then
#    display_info 'Check for NPM updates'
#    bin/npm update
#    bin/npm list --depth=0

    display_info 'Check for composer updates'
    export SYMFONY_ENV=dev
    bin/composer install

    display_success 'Update schema'
    bin/php bin/console doctrine:schema:update --dump-sql --force

#    display_success 'Generate ASSETS'
#    bin/node node_modules/.bin/grunt --force default

elif [ $1 = 'test' ]
then
    display_info 'Check for composer updates'
    export SYMFONY_ENV=dev
    bin/composer install

    display_info 'Reset database'
    bin/php bin/console doctrine:database:drop --force
    bin/php bin/console doctrine:database:create
    bin/php bin/console doctrine:schema:update --dump-sql --force
#    bin/php bin/console doctrine:fixtures:load --no-interaction

#    display_success 'Generate ASSETS'
#    bin/node node_modules/.bin/grunt --force package

elif [ $1 = 'prod' ]
then
    display_info 'Check for composer updates'
    export SYMFONY_ENV=prod
    bin/composer install --no-dev --optimize-autoloader

    display_success 'Update schema'
    bin/php bin/console doctrine:schema:update --dump-sql --force

#    display_success 'Generate ASSETS'
#    bin/node node_modules/.bin/grunt --force package

else
    display_error 'Environment does not exist'
    die
fi

display_success 'Done'