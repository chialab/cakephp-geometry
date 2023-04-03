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
     * GeometryType constructor.
     *
     * @param string $name Type name.
     */
    public function __construct(string $name)
    {
        $this->name = $name;
    }

    /**
     * @inheritdoc
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @inheritdoc
     */
    public function getBaseType(): string
    {
        return $this->name;
    }

    /**
     * @inheritdoc
     */
    public function toDatabase($value, DriverInterface $driver): ?string
    {
        if ($value === null) {
            return null;
        }

        $geometry = Geometry::parse($value)->getGeometry();
        $wkb = $geometry->asBinary();
        if ($driver instanceof Driver\Mysql) {
            $wkb = pack('V', $geometry->SRID()) . $wkb;
        }

        return $wkb;
    }

    /**
     * @inheritdoc
     */
    public function toPHP($value, DriverInterface $driver): ?Geometry
    {
        if ($value === null) {
            return null;
        }

        return Geometry::parse($value);
    }

    /**
     * @inheritdoc
     */
    public function toStatement($value, DriverInterface $driver): int
    {
        if ($value === null) {
            return PDO::PARAM_NULL;
        }

        return PDO::PARAM_LOB;
    }

    /**
     * @inheritdoc
     */
    public function marshal($value): ?Geometry
    {
        if ($value === null) {
            return null;
        }

        return Geometry::parse($value);
    }

    /**
     * @inheritdoc
     */
    public function newId(): ?string
    {
        return null;
    }
}
