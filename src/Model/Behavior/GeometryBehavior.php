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

        $dbGeom = new FunctionExpression('ST_GeomFromText', [$this->_table->aliasField($this->getConfigOrFail('geometryField'))]);
        foreach ($options as $op => $geom) {
            switch ($op) {
                case 'intersects':
                    $geom = Geometry::parse($geom)->getGeometry();
                    $query = $query->where(fn (QueryExpression $exp) => $exp->add(new FunctionExpression('ST_Intersects', [$dbGeom => 'identifier', $geom], ['string', 'geometry'])));

                    break;
                case 'within':
                    $geom = Geometry::parse($geom)->getGeometry();
                    $query = $query->where(fn (QueryExpression $exp) => $exp->add(new FunctionExpression('ST_Within', [$dbGeom => 'identifier', $geom], ['string', 'geometry'])));

                    break;
            }
        }

        return $query;
    }
}
