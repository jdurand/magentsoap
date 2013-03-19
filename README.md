## MagentSOAP


MagentSOAP is a simple wrapper for the Magento SOAP API v2

### Installation (using [composer](https://github.com/composer/composer))

Add this infomation to your composer.json :

    "require": {
        "jdurand/magentsoap": "dev-master"
    },
    "repositories": [{
        "type": "package",
        "package": {
            "name": "jdurand/magentsoap",
            "version": "dev-master",
            "source": {
                "url": "git://github.com/jdurand/magentsoap.git",
                "type": "git",
                "reference": "remotes/origin/master"
            }
        }
    }],
    "autoload": {
        "psr-0": {
            "MagentSOAP": "./vendor/jdurand/magentsoap/src/"
        }
    }

Update composer :

    ./composer.phar update


I'm pretty sure this isn't the best way to do it. Feel free to educate me.

### Usage

    <?php
    
    //API initialization
    $api  = \MagentSOAP\MagentSOAP::getInstance();
    
    if($api->catalogProductCreate($attributes, $attribute_set, $type)->success()) {
    	// Cool
    } else if($api->duplicate()) {
        // The product we tried to create already exists... we'll update it then
        if($api->catalogProductUpdate($attributes)->success()) {
            // The product was updated
        } else {
            // Something went wrong while updating the product
            echo $api->error());
        }
     } else {
        // Something went wrong while creating the product
        echo $api->error());
    }
    
    $api->end();
