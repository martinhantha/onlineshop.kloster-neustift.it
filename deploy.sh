#!/bin/bash

bin/magento setup:static-content:deploy --theme Local/kloster -j 4 -f

bin/magento setup:di:compile

bin/magento deploy:mode:set production -s

bin/magento setup:static-content:deploy --area adminhtml