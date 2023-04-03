<?php
declare(strict_types=1);

namespace Chialab\Geometry;

use Brick\Geo\Exception\GeometryException;
use Brick\Geo\Exception\GeometryIOException;
use Brick\Geo\Geometry as BrickGeometry;
use Brick\Geo\IO\EWKBReader;
use Brick\Geo\IO\EWKTReader;
use Brick\Geo\IO\GeoJSONReader;
use Brick\Geo\IO\GeoJSONWriter;
use Brick\Geo\IO\WKBReader;
use Brick\Geo\IO\WKTWriter;
use InvalidArgumentException;

/**
 * Serializable Geometry wrapper.
 */
class Geometry implements \JsonSerializable
{
    /**
     * The brick geometry instance.
     */
    protected BrickGeometry $geometry;

    /**
     * Create a geometry wrapper.
     *
     * @param \Brick\Geo\Geometry $geometry The geometry instance.
     */
    public function __construct(BrickGeometry $geometry)
    {
        $this->geometry = $geometry;
    }

    /**
     * Parse string or JSON into Geometry object.
     *
     * @param mixed $geometry Geometry.
     * @return static
     */
    public static function parse($geometry): self
    {
        if ($geometry instanceof static) {
            return clone $geometry;
        }

        if ($geometry instanceof BrickGeometry) {
            return new static($geometry);
        }

        if (is_string($geometry)) {
            try {
                return new static((new EWKBReader())->read($geometry));
            } catch (GeometryIOException $e) {
                // Not a WKB string.
            }

            try {
                [$wkb, $srid] = [substr($geometry, 4), bindec(substr($geometry, 0, 4))];

                return new static((new WKBReader())->read($wkb, $srid));
            } catch (GeometryIOException $e) {
                // Not a WKB string with SRID prefix.
            }

            try {
                return new static((new EWKTReader())->read($geometry));
            } catch (GeometryIOException $e) {
                // Not a WKT string.
            }
        }

        try {
            return new static((new GeoJSONReader())->read(is_string($geometry) ? $geometry : json_encode($geometry)));
        } catch (GeometryException $e) {
            // Not a GeoJSON.
        }

        throw new InvalidArgumentException('Could not parse geometry object');
    }

    /**
     * Get the brick geometry instance.
     *
     * @return \Brick\Geo\Geometry The original geometry instance.
     */
    public function getGeometry(): BrickGeometry
    {
        return $this->geometry;
    }

    /**
     * Serialize the geometry object.
     *
     * @return \stdClass The serialized geometry.
     */
    public function jsonSerialize(): \stdClass
    {
        return (new GeoJSONWriter())->writeRaw($this->geometry);
    }

    /**
     * Return debugging info.
     *
     * @return array
     */
    public function __debugInfo()
    {
        return [
            'type' => $this->geometry->geometryType(),
            'wkt' => (new WKTWriter())->write($this->geometry),
        ];
    }

    /**
     * Clone the geometry object.
     *
     * @return void
     */
    public function __clone()
    {
        $this->geometry = clone $this->geometry;
    }
}
