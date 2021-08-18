# Module for Magento 2.x - PayMee

## Documentation in English

This plugin for Magento 2.x allows you to integrate youy store with PayMee API.

Banks:

- 001 - Banco do Brasil S.A
- 033 - Santander Brasil S.A
- 077 - Banco Inter S.A
- 104 - Caixa Econômica Federal
- 212 - Banco Original S.A
- 237 - Banco Bradesco S.A
- 341 - Itaú-Unibanco S.A
- OUTROS BANCOS
- PIX

## Requirements to integrate
- [PHP 7.0+](https://www.php.net)
- [Magento 2.x](https://magento.com/tech-resources/download)

## OSC Compatibles
- Inovarti Onestep Checkout
- Firecheckout
- Moip Checkout
- Onepage

## Git Instalation
    $ git clone https://github.com/paymeebrasil/magento-2x.git ~/paymee
    $ cp -r ~/paymee/* /dir/magento2x/app/code
    $ rm -rf pub/static/*
    $ php bin/magento setup:upgrade;
    $ php bin/magento setup:di:compile;
    $ php bin/magento setup:static-content:deploy -f
    $ php bin/magento cache:clean;
    $ php bin/magento cache:flush;   

## Callback URL
	$ domain.com.br/paymeepix/index/callback

## API Reference
https://documenter.getpostman.com/view/3199663/RWM6zDGc?version=latest