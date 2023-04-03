<?php
declare(strict_types=1);

namespace Chialab\Geometry\Model\Behavior;

use Cake\Database\Expression\FunctionExpression;
use Cake\Database\Expression\QueryExpression;
use Cake\ORM\Behavior;
use Cake\ORM\Query;
use Chialab\Geometry\Geometry;

/**
 * Geometry behavior
 */
class GeometryBehavior extends Behavior
{
    /**
     * @inheritDoc
     */
    protected $_defaultConfig = [
        'geometryField' => 'geometry',
        'storedAs' => 'geometry',
        'implementedFinders' => [
            'geo' => 'findGeo',
        ],
    ];

    /**
     * @inheritDoc
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->_table
            ->getSchema()
            ->setColumnType($this->getConfigOrFail('geometryField'), 'geometry');
    }

    /**
     * Find an object by its geometrical properties.
     *
     * @param \Cake\ORM\Query $query Query object instance.
     * @param array $options Filter options.
     * @return \Cake\ORM\Query
     */
    public function findGeo(Query $query, array $options): Query
    {
        $options = array_intersect_key($options, array_flip(['intersects', 'within']));

        $dbField = $this->_table->aliasField($this->getConfigOrFail('geometryField'));
        $dbGeom = [$dbField => 'identifier'];
        switch ($this->getConfig('storedAs')) {
            case 'geometry':
                break;
            case 'text':
            case 'wkt':
                $dbGeom = [new FunctionExpression('ST_GeomFromText', $dbGeom)];
                break;
            case 'binary':
            case 'wkb':
                $dbGeom = [new FunctionExpression('ST_GeomFromWKB', $dbGeom)];
                break;
            default:
                throw new \RuntimeException('Invalid geometry storage format');
        }

        foreach ($options as $op => $geom) {
            switch ($op) {
                case 'intersects':
                    $geom = Geometry::parse($geom)->getGeometry()->withSRID(0);

                    $query = $query->where(fn (QueryExpression $exp) => $exp
                        ->isNotNull($dbField)
                        ->notEq($dbField, '', 'string')
                        ->add(new FunctionExpression('ST_Intersects', array_merge($dbGeom, ['test' => $geom]), ['test' => 'geometry']))
                    );

                    break;
                case 'within':
                    $geom = Geometry::parse($geom)->getGeometry()->withSRID(0);

                    $query = $query->where(fn (QueryExpression $exp) => $exp
                        ->isNotNull($dbField)
                        ->notEq($dbField, '', 'string')
                        ->add(new FunctionExpression('ST_Within', array_merge($dbGeom, ['test' => $geom]), ['test' => 'geometry']))
                    );

                    break;
            }
        }

        return $query;
    }
}
