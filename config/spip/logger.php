<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
	$container->parameters()
		->set('spip.logger.max_files', 4)
		->set('spip.logger.max_size', 100)
		->set('spip.logger.max_log', 100)
		->set('spip.logger.max_level', 'notice')
		->set('spip.logger.fileline', false)
		->set('spip.logger.brut', false)
		->set('spip.logger.log_path', '%spip.dirs.log%%s.log');
};
