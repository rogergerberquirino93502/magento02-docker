<?php declare(strict_types=1); 
Use Magento\Framework\Component\ComponentRegistrar;//registration.php is a file that is used to register the module with Magento.// Path: app/code/MaxiCompra/Blog/registration.php

    ComponentRegistrar::register(
        ComponentRegistrar::MODULE, 
        'MaxiCompra_BlogExtra', __DIR__
    );   // Register the module in the regis             