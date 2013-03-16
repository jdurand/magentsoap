## MagentSOAP


MagentSOAP is a simple wrapper for the Magento SOAP API v2

Usage :

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
