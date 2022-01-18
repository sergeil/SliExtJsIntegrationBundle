<?php

namespace Sli\ExtJsIntegrationBundle\Tests\DataMapping;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Sli\ExtJsIntegrationBundle\DataMapping\EntityDataMapperService;
use Sli\ExtJsIntegrationBundle\Tests\AbstractDatabaseTestCase;
use Sli\ExtJsIntegrationBundle\Tests\DummyUser;

/**
 * @author Sergei Vizel <sergei.vizel@gmail.com>
 */ 
class EntityDataMapperServiceTest extends AbstractDatabaseTestCase
{
    /**
     * @var EntityDataMapperService $mapper
     */
    private $mapper;

    public function setUp()
    {
        /* @var TokenStorageInterface $ts */
        $ts = self::$kernel->getContainer()->get('security.token_storage');

        $qb = self::$builder->buildQueryBuilder(DummyUser::clazz(), array(
            'filter' => array(
                array('property' => 'id', 'value' => 'eq:1')
            )
        ));
        /* @var DummyUser[] $users */
        $users = $qb->getQuery()->getResult();

        $token = new UsernamePasswordToken($users[0], null, 'main', ['ROLE_ADMIN']);
        $ts->setToken($token);

        $this->mapper = self::$kernel->getContainer()->get('sli.extjsintegration.entity_data_mapper');
    }

    public function testConvertDate()
    {
        $date = $this->mapper->convertDate('02.01.06');

        $this->assertInstanceOf('DateTime', $date);
        $this->assertEquals('02', $date->format('d'));
        $this->assertEquals('01', $date->format('m'));
        $this->assertEquals('06', $date->format('y'));

        $date = $this->mapper->convertDate('02.01.06', true);

        $this->assertEquals('2006-01-02', $date);
    }

    public function testConvertDateTime()
    {
        $date = $this->mapper->convertDateTime('02.01.06 15:04');

        $this->assertInstanceOf('DateTime', $date);
        $this->assertEquals('02', $date->format('d'));
        $this->assertEquals('01', $date->format('m'));
        $this->assertEquals('06', $date->format('y'));
        $this->assertEquals('15', $date->format('G'));
        $this->assertEquals('04', $date->format('i'));
    }

    public function testConvertBoolean()
    {
        $this->assertTrue($this->mapper->convertBoolean(1));
        $this->assertTrue($this->mapper->convertBoolean('on'));
        $this->assertTrue($this->mapper->convertBoolean('true'));
        $this->assertTrue($this->mapper->convertBoolean(true));

        $this->assertFalse($this->mapper->convertBoolean(0));
        $this->assertFalse($this->mapper->convertBoolean('off'));
        $this->assertFalse($this->mapper->convertBoolean('false'));
        $this->assertFalse($this->mapper->convertBoolean(false));
    }

    public function testMapEntity()
    {
        $user = new DummyUser();
        $userParams = array(
            'email' => 'john.doe@example.org',
            'isActive' => 'off',
            'accessLevel' => '5',
            'meta' => array(
                'foo' => 'bar',
            ),
        );

        $this->mapper->mapEntity($user, $userParams, array_keys($userParams));

        $this->assertEquals($userParams['email'], $user->email);
        $this->assertFalse($user->isActive);
        $this->assertEquals(5, $user->accessLevel);
        $this->assertEquals($userParams['meta'], $user->meta);
    }
}
