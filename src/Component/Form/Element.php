<?php
namespace sgoranov\Dendroid\Component\Form;

use sgoranov\Dendroid\Component;
use sgoranov\Dendroid\Component\Form as Form;
use sgoranov\Dendroid\EventDefinition;

abstract class Element extends Component implements ElementInterface
{
    /** @var  Form */
    protected $form;
    protected $name;

    protected $data = '';
    protected $submittedData;
    protected $optional = false;

    protected $errors = [];
    protected $validator;
    protected $attributes = [];

    public function __construct(string $name)
    {
        $this->name = $name;

        $this->addEventDefinition(new EventDefinition('onChange', [$this, 'hasChanged']));
    }

    public function setErrors(array $errors)
    {
        $this->errors = $errors;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setData($data)
    {
        if ($this->form && $this->form->isSubmitted()) {
            $this->submittedData = $data;
        } else {
            $this->data = $data;
        }
    }

    public function getData()
    {
        if ($this->form && $this->form->isSubmitted()) {

            if (is_null($this->submittedData)) {
                $this->submittedData = $this->form->getData()[$this->getName()];
            }

            return $this->submittedData;
        }

        return $this->data;
    }

    public function setForm(Form $form)
    {
        $this->form = $form;
    }

    public function getForm(): Form
    {
        return $this->form;
    }

    public function setValidator(ValidatorInterface $validator)
    {
        $this->validator = $validator;
    }

    public function getValidator()
    {
        return $this->validator;
    }

    public function hasChanged()
    {
        if ($this->form->isSubmitted() && $this->data !== $this->getData()) {
            return true;
        }

        return false;
    }

    public function setId(string $id)
    {
        $this->attributes['id'] = $id;
    }

    public function setDisabled(bool $value)
    {
        if ($value) {
            $this->attributes['disabled'] = 'disabled';
        } else {
            unset($this->attributes['disabled']);
        }
    }

    public function setReadOnly(bool $value)
    {
        if ($value) {
            $this->attributes['readonly'] = 'readonly';
        } else {
            unset($this->attributes['readonly']);
        }
    }

    protected function getDataToRender()
    {
        return $this->getData();
    }

    /**
     * @return bool
     */
    public function isOptional(): bool
    {
        return $this->optional;
    }

    /**
     * @param bool $optional
     */
    public function setOptional(bool $optional): void
    {
        $this->optional = $optional;
    }
}
