<?php

namespace Sli\ExtJsIntegrationBundle\Tests;

use Doctrine\ORM\Mapping as Orm;
use Sli\ExtJsIntegrationBundle\DataMapping\PreferencesAwareUserInterface;
use Sli\ExtJsIntegrationBundle\Service\QueryOrder;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @Orm\Entity
 * @Orm\Table(name="sli_extjsintegration_dummyuser")
 */
class DummyUser implements PreferencesAwareUserInterface
{
    /**
     * @Orm\Id
     * @Orm\Column(type="integer")
     * @Orm\GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @Orm\Column(type="boolean")
     */
    public $isActive;

    /**
     * @Orm\Column(type="integer")
     */
    public $accessLevel;

    /**
     * @Orm\Column(type="string", nullable=true)
     */
    public $email;

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

    /**
     * @Orm\Column(type="integer", nullable=true)
     */
    public $price = 0;

    /**
     * @Orm\Column(type="json_array", nullable=false)
     */
    public $meta = array();

    public function setActive($isActive)
    {
        $this->isActive = $isActive;
    }

    public function setAccessLevel($accessLevel)
    {
        $this->accessLevel = $accessLevel;
    }

    public function setEmail($email)
    {
        $this->email = $email;
    }

    public function setMeta($meta)
    {
        $this->meta = $meta;
    }

    /**
     * @inheritDoc
     */
    public function getPreferences()
    {
        return array(
            PreferencesAwareUserInterface::SETTINGS_DATE_FORMAT => 'd.m.y',
            PreferencesAwareUserInterface::SETTINGS_DATETIME_FORMAT => 'd.m.y H:i',
            PreferencesAwareUserInterface::SETTINGS_MONTH_FORMAT => 'm.Y',
        );
    }

    public function __construct($email = null, $accessLevel = 0, $isActive = true)
    {
        $this->email = $email;
        $this->accessLevel = $accessLevel;
        $this->isActive = $isActive;

        $this->groups = new ArrayCollection();
    }

    public function __toString()
    {
        return implode('-', array(
            $this->id,
            $this->firstname,
            $this->lastname,
        ));
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

    /**
     * @var DummyCity
     *
     * @Orm\ManyToOne(targetEntity="DummyCity", cascade={"PERSIST"})
     */
    public $city;

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

    /**
     * @Orm\OneToOne(targetEntity="President")
     */
    public $president;

    static public function clazz()
    {
        return get_called_class();
    }
}

/**
 * @Orm\Entity
 * @Orm\Table(name="sli_doctrinearrayquerybuilder_dummycity")
 */
class DummyCity
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
 * @Orm\Table(name="sli_extjsintegration_president")
 */
class President
{
    /**
     * @Orm\Id
     * @Orm\Column(type="integer")
     * @Orm\GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @Orm\Column(type="date")
     */
    public $since;

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
