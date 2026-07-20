<?php

namespace Tests\AppBundle\Form;

use AppBundle\Form\UserType;
use AppBundle\Entity\User;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Validator\Validation;

/**
 * Class UserTypeTest
 *
 * @package Tests\AppBundle\Form
 * @covers \AppBundle\Form\UserType
 */
class UserTypeTest extends TypeTestCase
{
    /**
     * Register the ValidatorExtension to support validation options like 'invalid_message'.
     *
     * @return array
     */
    protected function getExtensions()
    {
        $validator = Validation::createValidator();

        return [
            new ValidatorExtension($validator),
        ];
    }

    /**
     * Test form submission with valid data mapping to the User entity.
     */
    public function testSubmitValidData()
    {
        // Data structure matching the repeated password fields structure
        $formData = [
            'username' => 'testuser',
            'password' => [
                'first'  => 'securepassword123',
                'second' => 'securepassword123',
            ],
            'email'    => 'testuser@example.com',
        ];

        $objectToCompare = new User();
        $form = $this->factory->create(UserType::class, $objectToCompare);

        $expectedObject = new User();
        $expectedObject->setUsername('testuser');
        $expectedObject->setPassword('securepassword123');
        $expectedObject->setEmail('testuser@example.com');

        $form->submit($formData);

        $this->assertTrue($form->isSynchronized(), 'FAILED: Data transformation failed within UserType lifecycle.');

        $this->assertEquals($expectedObject->getUsername(), $objectToCompare->getUsername());
        $this->assertEquals($expectedObject->getPassword(), $objectToCompare->getPassword());
        $this->assertEquals($expectedObject->getEmail(), $objectToCompare->getEmail());

        $view = $form->createView();
        $children = $view->children;

        foreach (array_keys($formData) as $key) {
            $this->assertArrayHasKey($key, $children, sprintf('FAILED: Form view configuration lacks the "%s" field child key.', $key));
        }
    }
}
