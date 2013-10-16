<?php

namespace Sli\ExtJsIntegrationBundle\Tests\Service\ParsingQuery;

use Sli\ExtJsIntegrationBundle\QueryBuilder\QueryParsing\Filter;
use Sli\ExtJsIntegrationBundle\QueryBuilder\QueryParsing\Filters;

/**
 * @author Sergei Lissovski <sergei.lissovski@gmail.com>
 */
class FiltersTest extends \PHPUnit_Framework_TestCase
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

        $filters->removeFilter($orderTotalFilters[0]);

        $compiled = $filters->compile();
        $this->assertTrue(is_array($compiled));
        $this->assertEquals(4, count($compiled));
    }
}