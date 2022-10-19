
<?php 
//declare(strict_types=1); is a declaration that enables strict typing. It means that it will force to type check the variables and the function's return types.// Path: app/code/MaxiCompra/Blog/registration.php

Use Magento\Framework\Component\ComponentRegistrar;//registration.php is a file that is used to register the module with Magento.// Path: app/code/MaxiCompra/Blog/registration.php

    ComponentRegistrar::register(
        ComponentRegistrar::MODULE, 'MaxiCompra_Blog', __DIR__
    );   // Register the module in the regis             