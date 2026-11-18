<?php
declare(strict_types=1);

namespace Chialab\Geometry\Test\TestCase;

use Chialab\Geometry\Geometry;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Chialab\Geometry\Geometry
 */
class GeometryTest extends TestCase
{
    public static function dataParser(): array
    {
        return [
            [
                // Point
                '0101000020e61000006666666666264c40666666666666f63f',
                'POINT (56.3 1.4)',
            ],
            [
                // Polygon
                '0103000020E61000000100000005000000000000000000000000000000000000000000000000000000000000000000F03F000000000000F03F000000000000F03F000000000000F03F000000000000000000000000000000000000000000000000',
                'POLYGON ((0 0, 0 1, 1 1, 1 0, 0 0))',
            ],
            [
                // Binary point
                hex2bin('0101000020E61000006666666666264C40666666666666F63F'),
                'POINT (56.3 1.4)',
            ],
            [
                // Binary polygon
                hex2bin('0103000020E61000000100000005000000000000000000000000000000000000000000000000000000000000000000F03F000000000000F03F000000000000F03F000000000000F03F000000000000000000000000000000000000000000000000'),
                'POLYGON ((0 0, 0 1, 1 1, 1 0, 0 0))',
            ],
            [
                // WKT point
                'POINT(11.34 44.49)',
                'POINT (11.34 44.49)',
            ],
            [
                // WKT point with 12+ consecutive digits, must not be mistaken for hex
                'POINT(11.343126672866 44.493869109644)',
                'POINT (11.343126672866 44.493869109644)',
            ],
        ];
    }

    /** @dataProvider dataParser */
    public function testParse($input, $expected): void
    {
        $geometry = Geometry::parse($input);

        $this->assertEquals($expected, $geometry->__debugInfo()['wkt']);
    }
}
