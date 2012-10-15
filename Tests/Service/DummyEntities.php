<?php

namespace Sli\ExtJsIntegrationBundle\Tests\Service;

use Doctrine\ORM\Mapping as Orm;

/**
 * @Orm\Entity
 * @Orm\Table(name="sli_extjsintegration_dummyuser")
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

    /**
     * @Orm\Column(type="string", nullable=true)
     */
    public $lastname;

    /**
     * @Orm\OneToOne(targetEntity="DummyAddress", cascade={"PERSIST"})
     */
    public $address;

    static public function clazz()
    {
        return get_called_class();
    }
}

/**
 * @Orm\Entity
 * @Orm\Table(name="sli_extjsintegration_dummyaddress")
 */
class DummyAddress
{
    /**
     * @Orm\Id
     * @Orm\Column(type="integer")
     * @Orm\GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @Orm\Column
     */
    public $zip;

    /**
     * @Orm\Column
     */
    public $street;

    /**
     * @Orm\ManyToOne(targetEntity="DummyCountry")
     */
    public $country;

    static public function clazz()
    {
        return get_called_class();
    }
}

/**
 * @Orm\Entity
 * @Orm\Table(name="sli_extjsintegration_dummycountry")
 */
class DummyCountry
{
    /**
     * @Orm\Id
     * @Orm\Column(type="integer")
     * @Orm\GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @Orm\Column
     */
    public $name;

    static public function clazz()
    {
        return get_called_class();
    }
}
