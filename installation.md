# Installation Guide

### Note this installation guide is Mac-specific, tweaks maybe required for non-Mac users.

1. Open bash `pico ~/.bash_profile` and add the installation func:

#### createLark

The `createLark()` func creates a fresh install of the Lark build, an argument is required so that some 
files can be changed for you i.e. `webpack.mix.js` and `.env`. 

This is called like the following:

`createLark starter-theme`


#### installLark

This func will install all of the necessary composer and yarn packages.

```
# Lark helpers
function createLark()
{
    eval sitename="$1"

    cd ~/Sites/

    # Clone repo
    git clone https://github.com/wecreatedigital/starter-wordpress.git "$1"

    cd ~/Sites/"$1"/
    
    # Remove unnecessary files
    rm -rf "$1"/.git
    cp -rf "$1"/. .
    rm -rf "$1"/

    # Clone .env
    cp .env.example .env

    # Open files you may want to edit within Atom
    atom .
    atom composer.json
    atom web/app/themes/lark-child/app/Library/content.php
    atom web/app/themes/lark-child/webpack.mix.js

    # Assuming you pass in a param, let's rename the URLs for you
    sed -i '' "s/starter/${sitename}/g" web/app/themes/lark-child/webpack.mix.js
    sed -i '' "s/WP_DOMAIN=starter.test/WP_DOMAIN=${sitename}.test/g" .env
}

function installLark()
{
    valet link "$1"
    valet secure "$1"
    cd ~/Sites/"$1"/
    composer install
    cd web/app/themes/lark/
    composer install
    cd ../lark-child/
    composer install
    yarn install
    yarn start
    yarn clean:views
}
```
