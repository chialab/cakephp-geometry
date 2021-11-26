<?php
declare(strict_types=1);

namespace Chialab\Geometry\Type;

use Brick\Geo\Exception\GeometryException;
use Brick\Geo\Exception\GeometryIOException;
use Brick\Geo\Geometry as BrickGeometry;
use Brick\Geo\IO\EWKBReader;
use Brick\Geo\IO\EWKTReader;
use Brick\Geo\IO\GeoJSONReader;
use Brick\Geo\IO\WKBReader;
use Cake\Database\Driver;
use Cake\Database\DriverInterface;
use Cake\Database\TypeInterface;
use Chialab\Geometry\Geometry;
use InvalidArgumentException;
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
     * Parse string or JSON into Geometry object.
     *
     * @param mixed $geometry Geometry.
     * @return \Brick\Geo\Geometry
     */
    protected static function parseGeometry($geometry): BrickGeometry
    {
        if ($geometry instanceof Geometry) {
            return $geometry->getGeometry();
        }

        if ($geometry instanceof BrickGeometry) {
            return $geometry;
        }

        if (is_string($geometry)) {
            try {
                return (new EWKBReader())->read($geometry);
            } catch (GeometryIOException $e) {
                // Not a WKB string.
            }

            try {
                return (new EWKTReader())->read($geometry);
            } catch (GeometryIOException $e) {
                // Not a WKT string.
            }
        }

        try {
            return (new GeoJSONReader())->read(is_string($geometry) ? $geometry : json_encode($geometry));
        } catch (GeometryException $e) {
            // Not a GeoJSON.
        }

        throw new InvalidArgumentException('Could not parse geometry object');
    }

    /**
     * @inheritdoc
     */
    public function toDatabase($value, DriverInterface $driver): ?string
    {
        if ($value === null) {
            return null;
        }

        $geometry = static::parseGeometry($value);
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

        [$wkb, $srid] = [$value, 0];
        if ($driver instanceof Driver\Mysql) {
            [$wkb, $srid] = [substr($value, 4), bindec(substr($value, 0, 4))];
        }
        $reader = new WKBReader();

        return new Geometry($reader->read($wkb, $srid));
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

        return new Geometry(static::parseGeometry($value));
    }

    /**
     * @inheritdoc
     */
    public function newId(): ?string
    {
        return null;
    }
}
