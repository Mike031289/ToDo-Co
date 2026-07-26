<?php

namespace Tests\App\Form;

use App\Form\TaskType;
use App\Entity\Task;
use Symfony\Component\Form\Test\TypeTestCase;

/**
 * Class TaskTypeTest
 *
 * @package Tests\App\Form
 * @covers \App\Form\TaskType
 */
class TaskTypeTest extends TypeTestCase
{
    /**
     * Test form submission with valid data mapping to the Task entity.
     */
    public function testSubmitValidData()
    {
        $formData = [
            'title'   => 'Test Task Title',
            'content' => 'Test Task Content description.',
        ];

        $objectToCompare = new Task();
        // $objectToCompare will receive data from the form submission
        $form = $this->factory->create(TaskType::class, $objectToCompare);

        $expectedObject = new Task();
        $expectedObject->setTitle('Test Task Title');
        $expectedObject->setContent('Test Task Content description.');

        // Submit the mock payload directly into the form lifecycle handler
        $form->submit($formData);

        $this->assertTrue($form->isSynchronized(), 'FAILED: The form fields mapping data conversion failed.');

        // Check that the data injected matches our expected entity structure
        $this->assertEquals($expectedObject->getTitle(), $objectToCompare->getTitle());
        $this->assertEquals($expectedObject->getContent(), $objectToCompare->getContent());

        // Ensure that form view hierarchy contains the expected property fields keys
        $view = $form->createView();
        $children = $view->children;

        foreach (array_keys($formData) as $key) {
            $this->assertArrayHasKey($key, $children, sprintf('FAILED: The form view is missing the "%s" field child key.', $key));
        }
    }
}
