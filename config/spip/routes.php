<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
	$container->parameters()
		->set('spip.routes.front_office', '/')
		->set('spip.routes.back_office', 'ecrire/');
};
