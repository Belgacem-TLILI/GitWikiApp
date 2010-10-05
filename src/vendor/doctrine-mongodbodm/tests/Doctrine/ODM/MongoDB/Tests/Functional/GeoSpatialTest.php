<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional;

require_once __DIR__ . '/../../../../../TestInit.php';

class GeoSpacialTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testQueries()
    {
        $q = $this->dm->createQuery(__NAMESPACE__.'\City')
            ->field('coordinates')->near(1000000, 11111);
        $this->assertEquals(array('latitude' => 1000000, 'longitude' => 11111), $q->debug('near'));

        $q = $this->dm->createQuery(__NAMESPACE__.'\City')
            ->field('coordinates')->withinBox(41, 41, 72, 72);
        $this->assertEquals(array(
            'coordinates' => array(
                '$within' => array('$box' => array(array(41, 41), array(72, 72)))
            )
        ), $q->debug('query'));
    }

    public function testGeoSpatial()
    {
        $this->dm->getSchemaManager()->ensureDocumentIndexes(__NAMESPACE__.'\City');

        $city = new City();
        $city->name = 'Nashville';
        $city->coordinates = new Coordinates();
        $city->coordinates->latitude = 50;
        $city->coordinates->longitude = 30;

        $this->dm->persist($city);
        $this->dm->flush(array('safe' => true));
        $this->dm->clear();

        $city = $this->dm->createQuery(__NAMESPACE__.'\City')
            ->field('coordinates')->near(1000000, 11111)
            ->getSingleResult();
        $this->assertNull($city);

        $city = $this->dm->createQuery(__NAMESPACE__.'\City')
            ->field('coordinates')->near(50, 50)
            ->getSingleResult();
        $this->assertNotNull($city);
        $this->assertEquals('19.999998807907', $city->test);
    }

    public function testGeoSpatial2()
    {
        $this->dm->getSchemaManager()->ensureDocumentIndexes(__NAMESPACE__.'\City');

        $city = new City();
        $city->name = 'Nashville';
        $city->coordinates = new Coordinates();
        $city->coordinates->latitude = 34.2055968;
        $city->coordinates->longitude = -118.8713314;

        $this->dm->persist($city);
        $this->dm->flush(array('safe' => true));
        $this->dm->clear();

        $city = $this->dm->createQuery(__NAMESPACE__.'\City')
            ->field('coordinates')->near(50, 50)
            ->getSingleResult();
        $this->assertNotNull($city);
    }

    public function testWithinBox()
    {
        $this->dm->getSchemaManager()->ensureDocumentIndexes(__NAMESPACE__.'\City');

        $city = new City();
        $city->name = 'Nashville';
        $city->coordinates = new Coordinates();
        $city->coordinates->latitude = 40.739037;
        $city->coordinates->longitude = 73.992964;

        $this->dm->persist($city);
        $this->dm->flush(array('safe' => true));
        $this->dm->clear();

        $city = $this->dm->createQuery(__NAMESPACE__.'\City')
            ->field('coordinates')->withinBox(41, 41, 72, 72)
            ->getSingleResult();
        $this->assertNull($city);

        $city = $this->dm->createQuery(__NAMESPACE__.'\City')
            ->field('coordinates')->withinBox(30, 30, 80, 80)
            ->field('name')->equals('Nashville')
            ->getSingleResult();
        $this->assertNotNull($city);
    }

    public function testWithinCenter()
    {
        $this->dm->getSchemaManager()->ensureDocumentIndexes(__NAMESPACE__.'\City');

        $city = new City();
        $city->name = 'Nashville';
        $city->coordinates = new Coordinates();
        $city->coordinates->latitude = 50;
        $city->coordinates->longitude = 30;

        $this->dm->persist($city);
        $this->dm->flush(array('safe' => true));
        $this->dm->clear();

        $city = $this->dm->createQuery(__NAMESPACE__.'\City')
            ->field('coordinates')->withinCenter(50, 50, 20)
            ->field('name')->equals('Nashville')
            ->getSingleResult();
        $this->assertNotNull($city);
    }
}

/**
 * @Document
 * @Index(keys={"coordinates"="2d"})
 */
class City
{
    /** @Id */
    public $id;

    /** @String */
    public $name;

    /** @EmbedOne(targetDocument="Coordinates") */
    public $coordinates;

    /** @Distance */
    public $test;
}

/** @EmbeddedDocument */
class Coordinates
{
    /** @Float */
    public $latitude;

    /** @Float */
    public $longitude;
}