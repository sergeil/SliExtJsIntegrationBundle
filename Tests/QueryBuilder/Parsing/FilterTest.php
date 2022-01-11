<?php

namespace Sli\ExtJsIntegrationBundle\Tests\Service\Parsing;

use Sli\ExtJsIntegrationBundle\QueryBuilder\Parsing\Filter;

/**
 * @author Sergei Lissovski <sergei.lissovski@gmail.com>
 */
class FilterTest extends \PHPUnit\Framework\TestCase
{
    public function testIsValid()
    {
        $f = new Filter(array('property' => 'user.firstname', 'value' => 'eq:' . 'Sergei'));

        $this->assertTrue($f->isValid());

        $f = new Filter(array('property' => 'username', 'value' => 'eq:'));

        $this->assertTrue($f->isValid());

        $f = new Filter(array('value' => 'eq:'));

        $this->assertFalse($f->isValid());

        $f = new Filter(array('property' => 'username'));

        $this->assertFalse($f->isValid());

        $f = new Filter(array());

        $this->assertFalse($f->isValid());

        $f = new Filter(array('property' => 'user', 'value' => 'isNull'));

        $this->assertTrue($f->isValid());

        $f = new Filter(array('property' => 'user', 'value' => 'isNotNull'));

        $this->assertTrue($f->isValid());

        $f = new Filter(array('property' => 'user', 'value' => 'in:1,2,5'));

        $this->assertTrue($f->isValid());

        $f = new Filter(array('property' => 'user', 'value' => array('eq:1', 'eq:5')));

        $this->assertTrue($f->isValid());
    }

    public function testHowWellItWorksWithGoodInput()
    {
        $f = new Filter(array('property' => 'user.firstname', 'value' => 'eq:' . 'Sergei'));

        $this->assertEquals('user.firstname', $f->getProperty());
        $this->assertEquals('Sergei', $f->getValue());
        $this->assertEquals('eq', $f->getComparator());

        $this->assertSame($f, $f->setProperty('user.lastname'));
        $this->assertSame($f, $f->setValue('Liss%'));
        $this->assertSame($f, $f->setComparator('like'));

        $compiled = $f->compile();

        $this->assertTrue(is_array($compiled));
        $this->assertArrayHasKey('property', $compiled);
        $this->assertArrayHasKey('value', $compiled);
        $this->assertEquals('user.lastname', $compiled['property']);
        $this->assertEquals('like:Liss%', $compiled['value']);

        // ---

        $f = new Filter(array('property' => 'user', 'value' => 'isNull'));

        $this->assertEquals('user', $f->getProperty());
        $this->assertNull($f->getValue());
        $this->assertEquals('isNull', $f->getComparator());

        $compiled = $f->compile();

        $this->assertTrue(is_array($compiled));
        $this->assertArrayHasKey('property', $compiled);
        $this->assertArrayHasKey('value', $compiled);
        $this->assertEquals('user', $compiled['property']);
        $this->assertEquals('isNull', $compiled['value']);

        // ---

        $f = new Filter(array('property' => 'user', 'value' => 'in:1,5,8'));
        $inValue = $f->getValue();

        $this->assertTrue(is_array($inValue));
        $this->assertSame(array('1', '5', '8'), $inValue);

        // ---

        $f = new Filter(array('property' => 'user', 'value' => 'in:2,4'));
        $notInValue = $f->getValue();

        $this->assertTrue(is_array($notInValue));
        $this->assertSame(array('2', '4'), $notInValue);

        // ---

        $f = new Filter(array('property' => 'user', 'value' => 'in:'));

        $this->assertTrue(is_array($f->getValue()));
        $this->assertSame(array(), $f->getValue());

        // ---

        $f = new Filter(array('property' => 'user', 'value' => array('eq:5', 'gt:105')));

        $this->assertTrue(is_array($f->getValue()));
        $this->assertSame(
            array(
                array('comparator' => 'eq', 'value' => '5'),
                array('comparator' => 'gt', 'value' => '105')
            ),
            $f->getValue()
        );
    }

    public function testWithIsNullAndIsNotNullComparators()
    {
        $f = new Filter(array('property' => 'user', 'value' => 'isNull'));

        $this->assertTrue($f->isValid());
        $this->assertEquals('user', $f->getProperty());
        $this->assertEquals('isNull', $f->getComparator());
        $this->assertNull($f->getValue());

        $f = new Filter(array('property' => 'user', 'value' => 'isNotNull'));

        $this->assertTrue($f->isValid());
        $this->assertEquals('user', $f->getProperty());
        $this->assertEquals('isNotNull', $f->getComparator());
        $this->assertNull($f->getValue());
    }

    public function testGetSupportedComparators()
    {
        $result = Filter::getSupportedComparators();

        $this->assertTrue(in_array('eq', $result));
        $this->assertTrue(in_array('neq', $result));
    }

    public function testCreate()
    {
        $f = Filter::create('price', Filter::COMPARATOR_GREATER_THAN, 500);

        $this->assertInstanceOf(Filter::clazz(), $f);
        $this->assertEquals('price', $f->getProperty());
        $this->assertEquals(Filter::COMPARATOR_GREATER_THAN, $f->getComparator());
        $this->assertEquals(500, $f->getValue());

        // ---

        $f = Filter::create('shippedAt', Filter::COMPARATOR_IS_NULL);
        $this->assertInstanceOf(Filter::clazz(), $f);
        $this->assertEquals('shippedAt', $f->getProperty());
        $this->assertEquals(Filter::COMPARATOR_IS_NULL, $f->getComparator());
        $this->assertNull($f->getValue());
    }

    /**
     * @return array
     */
    public function compileDataProvider()
    {
        $result = array(
            array(
                'filter' => Filter::create('user.firstName', Filter::COMPARATOR_EQUAL, 'Sergei'),
                'expected' => array('property' => 'user.firstName', 'value' => 'eq:Sergei'),
            ),
            array(
                'filter' => Filter::create('user.firstName', Filter::COMPARATOR_NOT_EQUAL, 'Sergei'),
                'expected' => array('property' => 'user.firstName', 'value' => 'neq:Sergei'),
            ),
            array(
                'filter' => Filter::create('user.firstName', Filter::COMPARATOR_LIKE, 'Liss%'),
                'expected' => array('property' => 'user.firstName', 'value' => 'like:Liss%'),
            ),
            array(
                'filter' => Filter::create('user.firstName', Filter::COMPARATOR_NOT_LIKE, 'Liss%'),
                'expected' => array('property' => 'user.firstName', 'value' => 'notLike:Liss%'),
            ),
            array(
                'filter' => Filter::create('user.branch', Filter::COMPARATOR_IN, '1,2,3'),
                'expected' => array('property' => 'user.branch', 'value' => 'in:1,2,3'),
            ),
            array(
                'filter' => Filter::create('user.branch', Filter::COMPARATOR_NOT_IN, '1,2,3'),
                'expected' => array('property' => 'user.branch', 'value' => 'notIn:1,2,3'),
            ),
            array(
                'filter' => Filter::create('user.branch', Filter::COMPARATOR_IS_NULL),
                'expected' => array('property' => 'user.branch', 'value' => 'isNull'),
            ),
            array(
                'filter' => Filter::create('user.branch', Filter::COMPARATOR_IS_NOT_NULL),
                'expected' => array('property' => 'user.branch', 'value' => 'isNotNull'),
            ),
            array(
                'filter' => Filter::create('price', Filter::COMPARATOR_GREATER_THAN, '5'),
                'expected' => array('property' => 'price', 'value' => 'gt:5'),
            ),
            array(
                'filter' => Filter::create('price', Filter::COMPARATOR_GREATER_THAN_OR_EQUAL, '5'),
                'expected' => array('property' => 'price', 'value' => 'gte:5'),
            ),
            array(
                'filter' => Filter::create('price', Filter::COMPARATOR_LESS_THAN, '5'),
                'expected' => array('property' => 'price', 'value' => 'lt:5'),
            ),
            array(
                'filter' => Filter::create('price', Filter::COMPARATOR_LESS_THAN_OR_EQUAL, '5'),
                'expected' => array('property' => 'price', 'value' => 'lte:5'),
            ),
        );
        return $result;
    }

    /**
     * @dataProvider compileDataProvider
     *
     * @param Filter $filter
     * @param $expected
     */
    public function testCompile(Filter $filter, $expected)
    {
        $this->assertSame($expected, $filter->compile());
    }
}