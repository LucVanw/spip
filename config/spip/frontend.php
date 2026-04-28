<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $container->parameters()
        ->set('spip.frontend.js_subdir', 'javascript/')
        ->set('spip.frontend.private_js_subdir', 'prive/javascript/');
};
