#!/bin/bash

mkdir Frontend
cd Frontend
git clone git://github.com/ShopwareAG/ShopwareMobile.git
mv ShopwareMobile SwagMobileTemplate
rm -rf `find . -type d -name .git`
cd .. && zip -r SwagMobileTemplate.zip Frontend
rm -rf Frontend