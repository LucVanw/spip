<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
	$container->parameters()
		->set('spip.dirs.doc', 'IMG/')
		->set('spip.dirs.tmp', 'tmp/')
		->set('spip.dirs.var', 'local/')
		->set('spip.dirs.core', 'ecrire/')
		->set('spip.dirs.extensions', 'plugins-dist/')
		->set('spip.dirs.plugins', 'plugins/')
		->set('spip.dirs.extra_plugins', '')
		->set('spip.dirs.template', 'squelettes-dist/')
		->set('spip.dirs.custom', 'squelettes/')
		->set('spip.dirs.extra_custom', '')
		->set('spip.dirs.private_template', 'prive/')
		->set('spip.dirs.log', '%spip.dirs.tmp%log/')
		->set('spip.dirs.sessions', '%spip.dirs.tmp%sessions/');
};
