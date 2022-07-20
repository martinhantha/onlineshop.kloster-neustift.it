# Mage2 Module Moritzmair Mergeport

    ``moritzmair/module-mergeport``

 - [Main Functionalities](#markdown-header-main-functionalities)
 - [Installation](#markdown-header-installation)
 - [Configuration](#markdown-header-configuration)
 - [Specifications](#markdown-header-specifications)
 - [Attributes](#markdown-header-attributes)


## Main Functionalities
Synch Magento with Mergeport

## Installation
\* = in production please use the `--keep-generated` option

### Type 1: Zip file

 - Unzip the zip file in `app/code/Moritzmair`
 - Enable the module by running `php bin/magento module:enable Moritzmair_Mergeport`
 - Apply database updates by running `php bin/magento setup:upgrade`\*
 - Flush the cache by running `php bin/magento cache:flush`

### Type 2: Composer

 - Make the module available in a composer repository for example:
    - private repository `repo.magento.com`
    - public repository `packagist.org`
    - public github repository as vcs
 - Add the composer repository to the configuration by running `composer config repositories.repo.magento.com composer https://repo.magento.com/`
 - Install the module composer by running `composer require moritzmair/module-mergeport`
 - enable the module by running `php bin/magento module:enable Moritzmair_Mergeport`
 - apply database updates by running `php bin/magento setup:upgrade`\*
 - Flush the cache by running `php bin/magento cache:flush`


## Configuration




## Specifications

 - Console Command
	- Synch_Products

 - Console Command
	- Synch_Orders

 - Crongroup
	- mergeport

 - Cronjob
	- moritzmair_mergeport_synchproducts

 - Cronjob
	- moritzmair_mergeport_synchorders


## Attributes

 - Product - Pos Id (pos_id)

 - Product - Last Synch (last_synch)

 - Sales - Pos ID (pos_id)

 - Sales - Synched at (synched_at)

