<?php
declare(strict_types=1);

namespace Chialab\Geometry;

use Cake\Core\BasePlugin;
use Cake\Core\PluginApplicationInterface;
use Cake\Database\Type;
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

        if (class_exists(TypeFactory::class)) {
            // CakePHP 4.x
            TypeFactory::map('geometry', GeometryType::class);
        } else {
            // CakePHP 3.x
            Type::map('geometry', GeometryType::class);
        }
    }
}
