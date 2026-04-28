<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
	$container->parameters()
		->set('spip.docs.home_server', 'https://www.spip.net')
		->set('spip.docs.url_glossaire', 'https://@lang@.wikipedia.org/wiki/%s');
};
