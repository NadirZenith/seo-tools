#!/usr/bin/env bash

#######################################################################
########################  FUNCTIONS  ##################################
#######################################################################
display_error () {
    echo -e "\033[33;31m[ERROR] $1 \033[0m"
}

display_success () {
    echo -e "\033[33;32m[OK] $1 \033[0m"
}

display_info () {
    echo -e "\033[33;33m[INFO] $1 \033[0m"
}

die () {
    exit 1
}

#######################################################################
###################### END FUNCTIONS  #################################
#######################################################################

#Check parameters
if [ $# -eq 0 ] || ([ $1 != 'dev' ] && [ $1 != 'prod' ])
then
    display_error "You must pass a symfony environment (dev|prod)"
    die
else
    PHP="$(which php)"
    DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )/../" && pwd )"
#    DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
    CONSOLE=$DIR"/bin/console "
    SYMFONY_ENV=$1
    USER="$(whoami)"

    display_success "Params:"
    display_info "Application directory: $DIR"
    display_info "Console binary:        $CONSOLE"
    display_info "PHP binary:            $PHP"
    display_info "Symfony environment:   $SYMFONY_ENV"
    display_info "User:                  $USER"
fi

safeRunCommand() {
  typeset cmnd="$*"
  typeset ret_code
  display_info "command=$cmnd"

  eval $cmnd
  ret_code=$?
  if [ $ret_code != 0 ]; then
    display_error "Error : [$ret_code] when executing command: '$cmnd'"
    exit $ret_code
  fi
}

# test
#$PHP $CONSOLE report:view app.report.email last --env=$SYMFONY_ENV
#safeRunCommand "$PHP $CONSOLE report:process app.report.user --env=$SYMFONY_ENV"

#safeRunCommand "$PHP $CONSOLE app:parser:parse --limit=50 --force --env=$SYMFONY_ENV"
safeRunCommand "$PHP $CONSOLE app:parser:status --env=$SYMFONY_ENV"

#echo "Hello world, $USER" >> /application/var/logs/cron.log
#echo "Hello world, ${echo whoami}" >> /application/var/logs/cron.log 2>&1
display_success 'Done'