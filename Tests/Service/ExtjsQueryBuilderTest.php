<?php

namespace Sli\ExtJsIntegrationBundle\Tests\Service;

use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\ORM\Mapping as Orm;

/**
 * @Orm\Entity
 * @Orm\Table("sli_extjsintegration_dummyuser")
 */
class DummyUser
{
    /**
     * @Orm\Id
     * @Orm\Column(type="integer")
     * @Orm\GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @Orm\Column(type="string", nullable=true)
     */
    public $firstname;

    static public function clazz()
    {
        return get_called_class();
    }
}

/**
 * @author Sergei Lissovski <sergei.lissovski@gmail.com>
 */
class ExtjsQueryBuilderTest extends \Symfony\Bundle\FrameworkBundle\Test\WebTestCase
{
    /* @var \Doctrine\ORM\EntityManager */
    private $em;

    public static function setUpBeforeClass()
    {
        /* @var \Symfony\Component\HttpKernel\Kernel $kernel */
        $kernel = static::createKernel();
        $kernel->boot();
        /* @var \Doctrine\ORM\EntityManager $em */
        $em = $kernel->getContainer()->get('doctrine.orm.entity_manager');
        $st = new SchemaTool($em);
        $st->updateSchema(array(DummyUser::clazz()), true);
    }

    protected function setUp()
    {
        /* @var \Symfony\Component\HttpKernel\Kernel $kernel */
//        $kernel = static::createKernel();
//        $kernel->boot();
//        $this->em = $kernel->getContainer()->get('doctrine.orm.entity_manager');
//        $this->em->beginTransaction();
    }

    public function testBuildQueryBuilder()
    {

    }
}
