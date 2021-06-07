# Module for Magento 2.x - PayMee

## Documentation in English

This plugin for Magento 1.x allows you to integrate youy store with PayMee API.

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
- [cURL](https://www.php.net/manual/en/book.curl.php)

## OSC Compatibles
- PHP 5.6+
- Magento 1.x
- OpenMage

> **Note: make the store backup before instalation.**

    $ git clone https://github.com/paymeebrasil/magento-2x.git ~/magento-2.x/JuniorMaia/Paymee
    $ cp -r ~/magento-2.x/* /foo/bar/magento2/app/code
    
    #Execute
    
    $ cd /foo/bar/magento2
    $ php bin/magento setup:upgrade
    $ php bin/magento setup:static-content:deploy
    $ chmod -R 777 var/cache/*
    
Admin configuration: Stores > Configuration > Sales > Payment Methods > PayMee


## Demo
[http://magento2.paymee.com.br](http://magento2.paymee.com.br)

## API Reference
https://documenter.getpostman.com/view/3199663/RWM6zDGc?version=latest
