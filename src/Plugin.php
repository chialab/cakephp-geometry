<?php
declare(strict_types=1);

namespace Chialab\Geometry;

use Cake\Core\BasePlugin;
use Cake\Core\PluginApplicationInterface;
use Cake\Database\TypeFactory;
use Chialab\Geometry\Database\Type\GeometryType;

/**
 * Plugin for Chialab\Geometry
 */
class Plugin extends BasePlugin
{
    /**
     * @inheritDoc
     */
    public function bootstrap(PluginApplicationInterface $app): void
    {
        parent::bootstrap($app);

        TypeFactory::map('geometry', GeometryType::class);
    }
}
