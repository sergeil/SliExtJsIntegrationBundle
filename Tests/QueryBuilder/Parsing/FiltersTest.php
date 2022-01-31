<?php

namespace Sli\ExtJsIntegrationBundle\Tests\Service\Parsing;

use Sli\ExtJsIntegrationBundle\QueryBuilder\Parsing\Filter;
use Sli\ExtJsIntegrationBundle\QueryBuilder\Parsing\Filters;
use Sli\ExtJsIntegrationBundle\QueryBuilder\Parsing\OrFilter;

/**
 * @author Sergei Lissovski <sergei.lissovski@gmail.com>
 */
class FiltersTest extends \PHPUnit\Framework\TestCase
{
    public function testHowWellItWorks()
    {
        $input = array(
            array('property' => 'orderTotal', 'value' => 'gt:30'),
            array('property' => 'orderTotal', 'value' => 'lt:100'),
            array('property' => 'currency', 'value' => 'eq:2'),
            array('property' => 'paidAt' ,'value' => 'isNotNull'),
            array('property' => 'shippedAt', 'value' => 'isNull')
        );

        $filters = new Filters($input);

        $orderTotalGt = $filters->findOneByPropertyAndComparator('orderTotal', Filter::COMPARATOR_GREATER_THAN);

        $this->assertInstanceOf(Filter::clazz(), $orderTotalGt);
        $this->assertEquals('orderTotal', $orderTotalGt->getProperty());
        $this->assertEquals('gt', $orderTotalGt->getComparator());

        // ---

        $orderTotalFilters = $filters->findByProperty('orderTotal');

        $this->assertTrue(is_array($orderTotalFilters));
        $this->assertEquals(2, count($orderTotalFilters));
        $this->assertInstanceOf(Filter::clazz(), $orderTotalFilters[0]);
        $this->assertInstanceOf(Filter::clazz(), $orderTotalFilters[1]);
        $this->assertEquals(30, $orderTotalFilters[0]->getValue());
        $this->assertEquals(100, $orderTotalFilters[1]->getValue());

        $currencyFilter = $filters->findOneByProperty('currency');

        $this->assertInstanceOf(Filter::clazz(), $currencyFilter);
        $this->assertEquals('eq', $currencyFilter->getComparator());
        $this->assertEquals(2, $currencyFilter->getValue());

        $thrownException = null;
        try {
            $filters->findOneByProperty('orderTotal');
        } catch (\RuntimeException $e) {
            $thrownException = $e;
        }
        $this->assertNotNull($thrownException);

        // ---

        $filters->removeFilter($orderTotalFilters[0]);

        $compiled = $filters->compile();

        $this->assertTrue(is_array($compiled));
        $this->assertEquals(4, count($compiled));

        $filters->addFilter(new Filter(array('property' => 'address', 'value' => 'like:%Tallinn%')));

        $compiled = $filters->compile();

        $this->assertTrue(is_array($compiled));
        $this->assertEquals(5, count($compiled));

        // --- iterator

        $iteratedFilters = array();
        foreach ($filters as $filter) {
            $iteratedFilters[] = $filter;
        }

        $this->assertEquals(5, count($iteratedFilters));
    }

    public function testHowWellItWorksWithMixedFilters()
    {
        $input = array(
            array('property' => 'orderTotal', 'value' => array('eq:10', 'eq:20')),
            array(
                array('property' => 'user.firstname', 'value' => 'like:Se%'),
                array('property' => 'user.lastname', 'value' => 'like:Li%')
            )
        );

        $filters = new Filters($input);

        $this->assertEquals(2, count($filters));
        $this->assertInstanceOf(Filter::clazz(), $filters[0]);
        $this->assertInstanceOf(OrFilter::clazz(), $filters[1]);
        $this->assertEquals('orderTotal', $filters[0]->getProperty());
        $this->assertNull($filters[0]->getComparator());
        $this->assertSame(
            array(
                array('comparator' => 'eq', 'value' => '10'),
                array('comparator' => 'eq', 'value' => '20')
            ),
            $filters[0]->getValue()
        );

        /* @var Filter[] $subFilters */
        $subFilters = $filters[1]->getFilters();

        $this->assertTrue(is_array($subFilters));
        $this->assertEquals(2, count($subFilters));
    }
}