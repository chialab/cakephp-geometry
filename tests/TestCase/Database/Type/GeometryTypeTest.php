<?php
declare(strict_types=1);

namespace Chialab\Geometry\Test\TestCase\Database\Type;

use Cake\Database\Driver;
use Chialab\Geometry\Database\Type\GeometryType;
use Chialab\Geometry\Geometry;
use PDO;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Chialab\Geometry\Database\Type\GeometryType
 */
class GeometryTypeTest extends TestCase
{
    /**
     * @var \Chialab\Geometry\Database\Type\GeometryType
     */
    protected GeometryType $type;

    /**
     * @var (\Cake\Database\Driver&\PHPUnit\Framework\MockObject\MockObject)|\PHPUnit\Framework\MockObject\MockObject
     */
    protected MockObject|Driver $driver;

    protected static $_data = [
        'point' => [
            'text' => 'POINT (1 2)',
            'bigEndianBinary' => '00000000013ff00000000000004000000000000000',
            'littleEndianBinary' => '0101000000000000000000f03f0000000000000040',
        ],
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->type = new GeometryType();
        $this->driver = $this->getMockBuilder(Driver::class)->getMock();
    }

    /**
     * This is a very succinct series of tests for text/binary import/export methods.
     * Exhaustive tests for WKT and WKB are in the IO directory.
     */
    public function providerTextBinary(): array
    {
        return [
            ['POINT (1 2)', '00000000013ff00000000000004000000000000000', '0101000000000000000000f03f0000000000000040'],
            ['LINESTRING Z EMPTY', '00000003ea00000000', '01ea03000000000000'],
            ['MULTIPOLYGON M EMPTY', '00000007d600000000', '01d607000000000000'],
            ['POLYHEDRALSURFACE ZM EMPTY', '0000000bc700000000', '01c70b000000000000'],
        ];
    }

    public function testToDatabaseNullGeometry(): void
    {
        $this->assertNull($this->type->toDatabase(null, $this->driver));
        $this->assertNull($this->type->toDatabase('', $this->driver));
    }

    public function testToDatabase(): void
    {
        $point = Geometry::parse(self::$_data['point']['text']);

        $binary = $this->type->toDatabase($point, $this->driver);
        $hex = bin2hex($binary);
        $this->assertEquals(self::$_data['point']['littleEndianBinary'], $hex);
    }

    public function testToDatabaseMysql(): void
    {
        $point = Geometry::parse(self::$_data['point']['text']);

        $driver = $this->getMockBuilder(Driver\Mysql::class)->getMock();
        $binary = $this->type->toDatabase($point, $driver);
        $hex = bin2hex($binary);
        $this->assertEquals('000000000101000000000000000000f03f0000000000000040', $hex);
    }

    public function testToPhpNull(): void
    {
        $this->assertNull($this->type->toPHP(null, $this->driver));
        $this->assertNull($this->type->toPHP('', $this->driver));
    }

    /**
     * @dataProvider providerTextBinary
     */
    public function testToPhpParse($text, string $bigEndianBinary, string $littleEndianBinary): void
    {
        $point = $this->type->toPHP($bigEndianBinary, $this->driver);
        $this->assertEquals($text, $point->getGeometry()->asText());
    }

    public function testToStatementNull(): void
    {
        $this->assertSame(PDO::PARAM_NULL, $this->type->toStatement(null, $this->driver));
    }

    public function testToStatementLob(): void
    {
        $this->assertSame(PDO::PARAM_LOB, $this->type->toStatement(self::$_data['point']['littleEndianBinary'], $this->driver));
    }
}
