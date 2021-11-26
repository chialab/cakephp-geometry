<?php
declare(strict_types=1);

namespace Chialab\Geometry;

use Brick\Geo\Geometry as BrickGeometry;
use Brick\Geo\IO\GeoJSONWriter;

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
        $writer = new GeoJSONWriter();

        return $writer->writeRaw($this->geometry);
    }
}
