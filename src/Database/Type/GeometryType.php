<?php
declare(strict_types=1);

namespace Chialab\Geometry\Database\Type;

use Cake\Database\Driver;
use Cake\Database\DriverInterface;
use Cake\Database\TypeInterface;
use Chialab\Geometry\Geometry;
use PDO;

/**
 * Database type to store geometry object.
 *
 * @package Chialab\Geometry\Type
 */
class GeometryType implements TypeInterface
{
    /**
     * Type name.
     *
     * @var string
     */
    protected string $name = 'geometry';

    /**
     * Decorator method.
     *
     * @var (callable(\Brick\Geo\Geometry $geometry): \Brick\Geo\Geometry)|null
     */
    protected $decorator = null;

    /**
     * GeometryType constructor.
     *
     * @param string $name Type name.
     */
    public function __construct(string $name)
    {
        $this->name = $name;
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @inheritDoc
     */
    public function getBaseType(): string
    {
        return $this->name;
    }

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
    public function toDatabase($value, DriverInterface $driver): ?string
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
            $wkb = pack('V', $geometry->SRID()) . $wkb;
        }

        return $wkb;
    }

    /**
     * @inheritDoc
     */
    public function toPHP($value, DriverInterface $driver): ?Geometry
    {
        if (static::isNullGeometry($value)) {
            return null;
        }

        return Geometry::parse($value);
    }

    /**
     * @inheritDoc
     */
    public function toStatement($value, DriverInterface $driver): int
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
     * @inheritDoc
     */
    public function newId(): ?string
    {
        return null;
    }

    /**
     * Add a decorator to the type converter.
     * Useful to set the SRID of the geometry.
     *
     * @param (callable(\Brick\Geo\Geometry $geometry): \Brick\Geo\Geometry) $decorator The decorator method.
     * @return $this
     */
    public function withDecorator(callable $decorator)
    {
        $this->decorator = $decorator;

        return $this;
    }
}
