<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\Validator\Validation;

use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityType as AccountSelectTypeStub;
use Oro\Bundle\FormBundle\Form\Type\OroDateType;

use OroB2B\Bundle\AccountBundle\Entity\AccountUserAddress;
use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\AccountBundle\Form\Type\AccountUserType;
use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Tests\Unit\Form\Type\Stub\EntityType;
use OroB2B\Bundle\AccountBundle\Tests\Unit\Form\Type\Stub\AddressCollectionTypeStub;
use OroB2B\Bundle\AccountBundle\Entity\AccountUserRole;
use OroB2B\Bundle\AccountBundle\Form\Type\AccountSelectType;
use OroB2B\Bundle\AccountBundle\Form\Type\AccountUserRoleSelectType;
use OroB2B\Bundle\AccountBundle\Tests\Unit\Form\Type\Stub\EntitySelectTypeStub;

class AccountUserTypeTest extends FormIntegrationTestCase
{
    const DATA_CLASS = 'OroB2B\Bundle\AccountBundle\Entity\AccountUser';
    const ROLE_CLASS = 'OroB2B\Bundle\AccountBundle\Entity\AccountUserRole';
    const ADDRESS_CLASS = 'OroB2B\Bundle\AccountBundle\Entity\AccountUserAddress';

    /**
     * @var AccountUserType
     */
    protected $formType;

    /**
     * @var SecurityFacade|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $securityFacade;

    /**
     * @var Account[]
     */
    protected static $accounts = [];

    /**
     * @var AccountUserAddress[]
     */
    protected static $addresses = [];

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();
        $this->securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();

        $this->formType = new AccountUserType($this->securityFacade);
        $this->formType->setDataClass(self::DATA_CLASS);
        $this->formType->setAddressClass(self::ADDRESS_CLASS);
    }

    /**
     * @return array
     */
    protected function getExtensions()
    {
        $accountUserRoleSelectType = new EntitySelectTypeStub(
            $this->getRoles(),
            AccountUserRoleSelectType::NAME,
            new AccountUserRoleSelectType()
        );
        $addressEntityType = new EntityType($this->getAddresses(), 'test_address_entity');
        $accountSelectType = new AccountSelectTypeStub($this->getAccounts(), AccountSelectType::NAME);

        return [
            new PreloadedExtension(
                [
                    OroDateType::NAME => new OroDateType(),
                    AccountUserRoleSelectType::NAME => $accountUserRoleSelectType,
                    $accountSelectType->getName() => $accountSelectType,
                    AddressCollectionTypeStub::NAME => new AddressCollectionTypeStub(),
                    $addressEntityType->getName() => $addressEntityType,
                ],
                []
            ),
            new ValidatorExtension(Validation::createValidator())
        ];
    }

    /**
     * @dataProvider submitProvider
     *
     * @param AccountUser $defaultData
     * @param array $submittedData
     * @param AccountUser $expectedData
     * @param bool|true $rolesGranted
     */
    public function testSubmit(
        AccountUser $defaultData,
        array $submittedData,
        AccountUser $expectedData,
        $rolesGranted = true
    ) {
        if ($rolesGranted) {
            $this->securityFacade->expects($this->once())
                ->method('isGranted')
                ->with('orob2b_account_account_user_role_view')
                ->will($this->returnValue(true));
        }

        $form = $this->factory->create($this->formType, $defaultData, []);

        $this->assertEquals($defaultData, $form->getData());
        $form->submit($submittedData);
        $this->assertTrue($form->isValid());
        $this->assertEquals($expectedData, $form->getData());
    }

    /**
     * @return array
     */
    public function submitProvider()
    {
        $newAccountUser = new AccountUser();

        $existingAccountUser = new AccountUser();

        $class = new \ReflectionClass($existingAccountUser);
        $prop = $class->getProperty('id');
        $prop->setAccessible(true);
        $prop->setValue($existingAccountUser, 42);

        $existingAccountUser->setFirstName('Mary');
        $existingAccountUser->setLastName('Doe');
        $existingAccountUser->setEmail('john@example.com');
        $existingAccountUser->setPassword('123456');
        $existingAccountUser->setAccount($this->getAccount(1));
        $existingAccountUser->addAddress($this->getAddresses()[1]);

        $alteredExistingAccountUser = clone $existingAccountUser;
        $alteredExistingAccountUser->setAccount($this->getAccount(2));
        $alteredExistingAccountUser->setEnabled(false);

        $alteredExistingAccountUserWithRole = clone $alteredExistingAccountUser;
        $alteredExistingAccountUserWithRole->setRoles([$this->getRole(2, 'test02')]);

        $alteredExistingAccountUserWithAddresses = clone $alteredExistingAccountUser;
        $alteredExistingAccountUserWithAddresses->addAddress($this->getAddresses()[2]);

        return
            [
                'user without submitted data' => [
                    'defaultData' => $newAccountUser,
                    'submittedData' => [],
                    'expectedData' => $newAccountUser
                ],
                'altered existing user' => [
                    'defaultData' => $existingAccountUser,
                    'submittedData' => [
                        'firstName' => 'Mary',
                        'lastName' => 'Doe',
                        'email' => 'john@example.com',
                        'account' => 2
                    ],
                    'expectedData' => $alteredExistingAccountUser
                ],
                'altered existing user with roles' => [
                    'defaultData' => $existingAccountUser,
                    'submittedData' => [
                        'firstName' => 'Mary',
                        'lastName' => 'Doe',
                        'email' => 'john@example.com',
                        'account' => 2,
                        'roles' => [2]
                    ],
                    'expectedData' => $alteredExistingAccountUserWithRole,
                    'rolesGranted' => true
                ],
                'altered existing user with addresses' => [
                    'defaultData' => $existingAccountUser,
                    'submittedData' => [
                        'firstName' => 'Mary',
                        'lastName' => 'Doe',
                        'email' => 'john@example.com',
                        'account' => 2,
                        'addresses' => [1, 2]
                    ],
                    'expectedData' => $alteredExistingAccountUserWithAddresses,
                ]
            ];
    }

    /**
     * Test getName
     */
    public function testGetName()
    {
        $this->assertEquals(AccountUserType::NAME, $this->formType->getName());
    }

    /**
     * @return AccountUserAddress[]
     */
    protected function getAddresses()
    {
        if (!self::$addresses) {
            self::$addresses = [
                1 => $this->getEntity('OroB2B\Bundle\AccountBundle\Entity\AccountUserAddress', 1),
                2 => $this->getEntity('OroB2B\Bundle\AccountBundle\Entity\AccountUserAddress', 2)
            ];
        }

        return self::$addresses;
    }

    /**
     * @return AccountUserRole[]
     */
    protected function getRoles()
    {
        return [
            1 => $this->getRole(1, 'test01'),
            2 => $this->getRole(2, 'test02')
        ];
    }

    /**
     * @return Account[]
     */
    protected function getAccounts()
    {
        if (!self::$accounts) {
            self::$accounts = [
                '1' => $this->createAccount(1, 'first'),
                '2' => $this->createAccount(2, 'second')
            ];
        }

        return self::$accounts;
    }

    /**
     * @param int $id
     * @return Account
     */
    protected function getAccount($id)
    {
        $accounts = $this->getAccounts();

        return $accounts[$id];
    }

    /**
     * @param int $id
     * @param string $name
     * @return Account
     */
    protected static function createAccount($id, $name)
    {
        $account = new Account();

        $reflection = new \ReflectionProperty(get_class($account), 'id');
        $reflection->setAccessible(true);
        $reflection->setValue($account, $id);

        $account->setName($name);

        return $account;
    }

    /**
     * @param int $id
     * @param string $label
     * @return AccountUserRole
     */
    protected function getRole($id, $label)
    {
        $role = new AccountUserRole($label);

        $reflection = new \ReflectionProperty(get_class($role), 'id');
        $reflection->setAccessible(true);
        $reflection->setValue($role, $id);

        $role->setLabel($label);

        return $role;
    }

    /**
     * @param string $className
     * @param int $id
     * @return object
     */
    protected function getEntity($className, $id)
    {
        $entity = new $className;

        $reflectionClass = new \ReflectionClass($className);
        $method = $reflectionClass->getProperty('id');
        $method->setAccessible(true);
        $method->setValue($entity, $id);

        return $entity;
    }
}
