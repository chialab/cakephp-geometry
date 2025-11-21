<?php
declare(strict_types=1);

namespace Chialab\Geometry\Database\Type;

use Cake\Database\Driver;
use Cake\Database\Type\BaseType;
use Chialab\Geometry\Geometry;
use PDO;

/**
 * Database type to store geometry object.
 *
 * @package Chialab\Geometry\Type
 */
class GeometryType extends BaseType
{
    /**
     * Decorator method.
     *
     * @var (callable(\Brick\Geo\Geometry): \Brick\Geo\Geometry)|null
     */
    protected $decorator = null;

    /**
     * Check if the value is a null geometry.
     *
     * @param mixed $value The value to check.
     * @return bool
     */
    protected static function isNullGeometry(mixed $value): bool
    {
        return $value === null || $value === '';
    }

    /**
     * @inheritDoc
     */
    public function toDatabase(mixed $value, Driver $driver): mixed
    {
        if (static::isNullGeometry($value)) {
            return null;
        }

        $geometry = Geometry::parse($value)->getGeometry();

        if ($this->decorator !== null) {
            $geometry = call_user_func($this->decorator, $geometry);
        }
        $wkb = $geometry->asBinary();
        if ($driver instanceof Driver\Mysql) {
            $wkb = pack('V', $geometry->srid()) . $wkb;
        }

        return $wkb;
    }

    /**
     * @inheritDoc
     */
    public function toPHP($value, Driver $driver): ?Geometry
    {
        if (static::isNullGeometry($value)) {
            return null;
        }

        return Geometry::parse($value);
    }

    /**
     * @inheritDoc
     */
    public function toStatement($value, Driver $driver): int
    {
        if (static::isNullGeometry($value)) {
            return PDO::PARAM_NULL;
        }

        return PDO::PARAM_LOB;
    }

    /**
     * @inheritDoc
     */
    public function marshal($value): ?Geometry
    {
        if (static::isNullGeometry($value)) {
            return null;
        }

        return Geometry::parse($value);
    }

    /**
     * Add a decorator to the type converter.
     * Useful to set the SRID of the geometry.
     *
     * @param (callable(\Brick\Geo\Geometry): \Brick\Geo\Geometry) $decorator The decorator method.
     * @return static
     */
    public function withDecorator(callable $decorator): static
    {
        $this->decorator = $decorator;

        return $this;
    }
}
