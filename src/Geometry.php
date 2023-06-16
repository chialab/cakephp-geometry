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
use Brick\Geo\IO\WKBWriter;
use Brick\Geo\IO\WKTWriter;
use InvalidArgumentException;
use JsonSerializable;

/**
 * Serializable Geometry wrapper.
 */
class Geometry implements JsonSerializable
{
    /**
     * The brick geometry instance.
     */
    protected BrickGeometry $geometry;

    /**
     * Serialization format.
     */
    protected static ?string $serializeAs = null;

    /**
     * Create a geometry wrapper.
     *
     * @param \Brick\Geo\Geometry $geometry The geometry instance.
     */
    final public function __construct(BrickGeometry $geometry)
    {
        $this->geometry = $geometry;
    }

    /**
     * Parse string or JSON into Geometry object.
     *
     * @param mixed $geometry Geometry.
     * @return static
     */
    public static function parse(mixed $geometry): self
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

            if (strlen($geometry) > 4) {
                try {
                    [$wkb, $srid] = [substr($geometry, 4), ...unpack('L', $geometry)];

                    return new static((new WKBReader())->read($wkb, $srid));
                } catch (GeometryIOException $e) {
                    // Not a WKB string with SRID prefix.
                }
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
     * @return mixed The serialized geometry.
     */
    public function jsonSerialize(): mixed
    {
        switch (static::$serializeAs) {
            case 'wkb':
                return (new WKBWriter())->write($this->geometry);

            case 'wkt':
                $locale = setlocale(LC_NUMERIC, 0);
                setlocale(LC_NUMERIC, 'C');
                try {
                    $writer = new WKTWriter();
                    $writer->setPrettyPrint(false);

                    return $writer->write($this->geometry);
                } finally {
                    setlocale(LC_NUMERIC, $locale);
                }

            case 'geojson':
            default:
                return (new GeoJSONWriter())->writeRaw($this->geometry);
        }
    }

    /**
     * Return debugging info.
     *
     * @return array
     */
    public function __debugInfo(): array
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

    /**
     * Set the serialization format.
     * Supported formats are: wkb, wkt, geojson.
     *
     * @param string $serializeAs The serialization format.
     * @return void
     */
    public static function setSerializeAs(string $serializeAs): void
    {
        switch ($serializeAs) {
            case 'wkb':
            case 'wkt':
            case 'geojson':
                static::$serializeAs = $serializeAs;

                return;

            default:
                throw new InvalidArgumentException(sprintf('Invalid serialization format: %s', $serializeAs));
        }
    }
}
