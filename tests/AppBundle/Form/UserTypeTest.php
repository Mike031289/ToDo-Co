<?php

namespace Tests\AppBundle\Form;

use AppBundle\Form\UserType;
use AppBundle\Entity\User;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Validator\ValidatorBuilder;

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
     * Instantiates the ValidatorBuilder directly to avoid static access analyzer warnings.
     *
     * @return array
     */
    protected function getExtensions()
    {
        $validatorBuilder = new ValidatorBuilder();
        $validator = $validatorBuilder->getValidator();

        return [
            new ValidatorExtension($validator),
        ];
    }

    /**
     * Test form submission with valid data mapping to the User entity.
     *
     * @return void
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

        $this->assertSame(
            true,
            $form->isSynchronized(),
            'FAILED: Data transformation failed within UserType lifecycle.'
        );

        $this->assertEquals($expectedObject->getUsername(), $objectToCompare->getUsername());
        $this->assertEquals($expectedObject->getPassword(), $objectToCompare->getPassword());
        $this->assertEquals($expectedObject->getEmail(), $objectToCompare->getEmail());

        $view = $form->createView();
        $children = $view->children;

        foreach (array_keys($formData) as $key) {
            $this->assertArrayHasKey(
                $key,
                $children,
                sprintf('FAILED: Form view configuration lacks the "%s" field child key.', $key)
            );
        }
    }
}
