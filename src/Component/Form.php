<?php
namespace sgoranov\Dendroid\Component;

use sgoranov\Dendroid\ComponentContainer;
use sgoranov\Dendroid\Component;
use sgoranov\Dendroid\Component\Form\ElementInterface;
use sgoranov\Dendroid\Component\Form\Element;
use sgoranov\Dendroid\ComponentInterface;
use sgoranov\Dendroid\EventDefinition;

class Form extends ComponentContainer
{
    protected $onSubmit;
    protected $id;
    protected $method;
    protected $csrfEnabled;
    protected $errors = [];

    public function __construct(string $id, string $method = 'post', bool $csrfEnabled = true)
    {
        if ($method !== 'post' && $method !== 'get') {
            throw new \InvalidArgumentException('Invalid method passed');
        }

        $this->addEventDefinition(new EventDefinition('onSubmit', [$this, 'isSubmitted']));

        $this->id = $id;
        $this->method = $method;
        $this->csrfEnabled = $csrfEnabled;
    }

    public function addComponent($ref, ComponentInterface $component)
    {
        /** @var Element $component */
        if (!$component instanceof ElementInterface) {
            throw new \InvalidArgumentException('Invalid component passed');
        }

        $component->setForm($this);

        parent::addComponent($ref, $component);
    }

    public function getId()
    {
        return $this->id;
    }

    public function addError(string $error)
    {
        $this->errors[] = $error;
    }

    public function getErrors()
    {
        return $this->errors;
    }

    public function render(\DOMNode $parent): \DOMNode
    {
        /** @var \DOMElement $parent */
        if (!$parent instanceof \DOMElement) {
            throw new \InvalidArgumentException('DOMElement expected');
        }

        $parent = parent::render($parent);
        $parent->setAttribute('id', $this->id);
        $parent->setAttribute('method', $this->method);

        $dom = $parent->ownerDocument;
        $hidden = $dom->createElement('input');
        $hidden->setAttribute('type', 'hidden');
        $hidden->setAttribute('name', 'event');
        $hidden->setAttribute('value', $this->id);
        $parent->insertBefore($hidden);

        if ($this->csrfEnabled) {
            $hidden = $dom->createElement('input');
            $hidden->setAttribute('type', 'hidden');
            $hidden->setAttribute('name', 'csrf_token');
            $hidden->setAttribute('value', $this->generateCSRFToken());
            $parent->insertBefore($hidden);
        }

        return $parent;
    }

    public function isSubmitted()
    {
        return isset($_POST['event']) && $_POST['event'] === $this->getId();
    }

    public function isValid()
    {
        if (!$this->isSubmitted()) {
            return true;
        }

        $isValid = true;

        // CSRF validation
        if ($this->csrfEnabled) {
            if ($this->method === 'get') {
                $submittedToken = $_GET['csrf_token'];
            } else {
                $submittedToken = $_POST['csrf_token'];
            }

            if ($this->getCSRFToken() !== $submittedToken) {
                $isValid = false;
                $this->addError('CSRF validation failed');
            }
        }

        $formData = $this->getData();

        /** @var Element $element */
        foreach ($this->components as $element) {
            $validator = $element->getValidator();

            if (!is_null($validator)) {
                $data = $formData[$element->getName()];

                if (!$validator->isValid($data)) {
                    $element->setErrors($validator->getErrors());
                    $isValid = false;
                }
            }
        }

        return $isValid;
    }

    public function setData(array $data)
    {
        /** @var Element $component */
        foreach ($this->getComponents() as $component) {
            $name = $component->getName();

            if (isset($data[$name])) {
                $component->setData($data[$name]);
            }
        }
    }

    public function getData()
    {
        if (!$this->isSubmitted()) {
            throw new \InvalidArgumentException('Form is not submitted.');
        }

        $result = [];

        /** @var Element $component */
        foreach ($this->getComponents() as $component) {
            $name = $component->getName();

            $data = null;

            // check GET or POST and then FILES
            if ($this->method === 'get' && isset($_GET[$name])) {

                $data = $_GET[$name];
            } elseif (isset($_POST[$name])) {

                $data = $_POST[$name];
            } elseif (isset($_FILES[$name])) {

                $data = $_FILES[$name];
            } else {
                
                // throw exception if data is not found
                // but skip if the component is "optional"
                if (!$component->isOptional()) {

                    throw new \InvalidArgumentException(sprintf(
                        'Unable to find the data for %s element. The form may contains a file 
                                element but enctype="multipart/form-data" attribute is missing', $name));
                }
            }

            $result[$name] = $data;
        }

        return $result;
    }

    public function getElementByName($name)
    {
        /** @var Element $component */
        foreach ($this->getComponents() as $component) {

            if ($component->getName() === $name) {

                return $component;
            }
        }

        throw new \InvalidArgumentException(sprintf('Unable to find an element with name %s', $name));
    }

    protected function getCSRFToken()
    {
        if (isset($_SESSION['form_' . $this->getId()]['csrf_token'])) {
            return $_SESSION['form_' . $this->getId()]['csrf_token'];
        }

        return '';
    }

    protected function generateCSRFToken()
    {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        $token = bin2hex(random_bytes(32));
        $_SESSION['form_' . $this->getId()]['csrf_token'] = $token;

        return $token;
    }
}