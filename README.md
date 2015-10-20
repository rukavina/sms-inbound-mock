# SMS Mock test tool

## Intro

This is a mock server for HORISEN AG premium transit API: https://www.horisen.com/en/help/api-manuals/premium-transit

## Install

After git clone do

```
composer install
cp dist.config.php config.php
# update configphp
touch data/log/client.log
chmod 777 data/log/client.log
cd public
bower install
cd js
cp dist.config.js config.js
# update config.js
```

and then update config.php with your data

You will need to install *redis* server as well

## Run

in order to run MO websocket server execute

```
php script/mo-server.php
```


and then navigate to your page in `public/index.html`

