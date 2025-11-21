<?php
declare(strict_types=1);

namespace Chialab\Geometry;

use Brick\Geo\Exception\GeometryException;
use Brick\Geo\Exception\GeometryIoException;
use Brick\Geo\Geometry as BrickGeometry;
use Brick\Geo\Io\EwkbReader;
use Brick\Geo\Io\EwktReader;
use Brick\Geo\Io\GeoJsonReader;
use Brick\Geo\Io\GeoJsonWriter;
use Brick\Geo\Io\WkbReader;
use Brick\Geo\Io\WkbWriter;
use Brick\Geo\Io\WktWriter;
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
            // Hex geometry
            if (preg_match('/[A-F0-9]{12,}/i', $geometry)) {
                $geometry = hex2bin($geometry);
            }
            try {
                return new static((new EwkbReader())->read($geometry));
            } catch (GeometryIoException $e) {
                // Not a Ewkb (PostGis) string.
            }

            if (strlen($geometry) > 4) {
                try {
                    [$wkb, $srid] = [substr($geometry, 4), ...unpack('L', $geometry)];

                    return new static((new WkbReader())->read($wkb, (int)$srid));
                } catch (GeometryIoException $e) {
                    // Not a Wkb string with SRID prefix.
                }
            }

            try {
                return new static((new EwktReader())->read($geometry));
            } catch (GeometryIoException $e) {
                // Not a Wkt string.
            }
        }

        try {
            $json = is_string($geometry) ? $geometry : json_encode($geometry);

            return new static((new GeoJsonReader())->read($json));
        } catch (GeometryException $e) {
            // Not a GeoJson.
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
                return (new WkbWriter())->write($this->geometry);

            case 'wkt':
                $locale = setlocale(LC_NUMERIC, 0);
                setlocale(LC_NUMERIC, 'C');
                try {
                    $writer = new WktWriter();
                    $writer->setPrettyPrint(false);

                    return $writer->write($this->geometry);
                } finally {
                    setlocale(LC_NUMERIC, $locale);
                }

            case 'geojson':
            default:
                return (new GeoJsonWriter())->writeRaw($this->geometry);
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
            'wkt' => (new WktWriter())->write($this->geometry),
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
