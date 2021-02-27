Paguelofacil_Gateway
======================

Author: [rolmos@paguelofacil.com](mailto:rolmos@paguelofacil)

Extensión de proveedor de pagos Paguelofacil para Magento v2.

Usted podrá procesar pagos a través de PagueloFacil.

Para obtener más información visite el siguiente enlace [here](https://developers.paguelofacil.com/ecommerce/magento).

Other notes on extension: https://github.com/paguelofacil/magento

Install
=======

1. Vaya a la carpeta raiz de Magento.

2. Ingrese alguno de los comandos para instalar el modulo.

    ```bash
    composer require paguelofacil/magento2
    ```
Esperar mientras las dependencias se actualizan.

3. Ingrese los siguientes comandos para activar el modulo:

    ```bash
    php bin/magento module:enable Paguelofacil_Gateway --clear-static-content
    php bin/magento setup:upgrade
   php bin/magento setup:di:compile
    ```
4. Habilite y configure PagueloFacil en Magento Admin sobre Stores/Configuration/Sales/Payment Methods/PagueloFacil o Paguelofacil Link Payment


