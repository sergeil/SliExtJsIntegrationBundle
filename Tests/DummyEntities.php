<?php

namespace Sli\ExtJsIntegrationBundle\Tests;

use Doctrine\ORM\Mapping as Orm;
use Sli\ExtJsIntegrationBundle\Service\QueryOrder;
use Doctrine\Common\Collections\ArrayCollection;

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
     * @var DummyAddress
     * @Orm\OneToOne(targetEntity="DummyAddress", cascade={"PERSIST"})
     */
    public $address;

    /**
     * @Orm\ManyToOne(targetEntity="CreditCard")
     */
    public $creditCard;

    /**
     * @Orm\ManyToMany(targetEntity="Group", inversedBy="users")
     */
    public $groups;

    public function __construct()
    {
        $this->groups = new ArrayCollection();
    }

    static public function clazz()
    {
        return get_called_class();
    }
}

/**
 * @Orm\Entity
 * @Orm\Table(name="sli_extjsintegration_group")
 */
class Group
{
    /**
     * @Orm\Id
     * @Orm\Column(type="integer")
     * @Orm\GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @Orm\Column(type="string")
     */
    public $name;

    /**
     * @Orm\ManyToMany(targetEntity="DummyUser", mappedBy="groups")
     */
    public $users;

    static public function clazz()
    {
        return get_called_class();
    }

    public function addUser(DummyUser $user)
    {
        $user->groups->add($this);
        if (!$this->users->contains($user)) {
            $this->users->add($user);
        }
    }

    public function __construct()
    {
        $this->users = new ArrayCollection();
    }
}

/**
 * @Orm\Entity
 * @Orm\Table(name="sli_extjsintegration_cc")
 */
class CreditCard
{
    /**
     * @Orm\Id
     * @Orm\Column(type="integer")
     * @Orm\GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @Orm\Column(type="integer")
     */
    public $number;

    static public function clazz()
    {
        return get_called_class();
    }
}

/**
 * @Orm\Entity
 * @Orm\Table(name="sli_extjsintegration_dummyaddress")
 *
 * @QueryOrder("zip")
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
     * @var DummyCountry
     * @Orm\ManyToOne(targetEntity="DummyCountry", cascade={"PERSIST"})
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

/**
 * @Orm\Entity
 * @Orm\Table(name="sli_extjsintegration_dummyorder")
 */
class DummyOrder
{
    /**
     * @Orm\Id
     * @Orm\Column(type="integer")
     * @Orm\GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @var DummyUser
     *
     * @Orm\ManyToOne(targetEntity="DummyUser")
     */
    public $user;

    /**
     * @Orm\Column(type="string")
     */
    public $number;

    static public function clazz()
    {
        return get_called_class();
    }
}
