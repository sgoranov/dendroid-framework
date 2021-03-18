<?php
namespace sgoranov\Dendroid\Component\Form;

class Button extends Element
{
    protected $optional = true;

    protected $type = 'submit';

    public function __construct(string $name)
    {
        parent::__construct($name);

        $this->attributes['value'] = $name;
    }

    public function setType($type = 'text')
    {
        $this->type = $type;
    }

    public function getData()
    {
        if (isset($this->form->getData()[$this->name])) {

            return $this->form->getData()[$this->name];
        }

        return null;
    }

    public function isClicked(): bool
    {
        return !is_null($this->getData());
    }

    public function render(\DOMNode $node): \DOMNode
    {
        if (!$node instanceof \DOMElement) {
            throw new \InvalidArgumentException('DOMElement expected');
        }

        // overwrite the name of the form field
        $node->setAttribute('name', $this->getName());

        // set all additional attributes
        foreach ($this->attributes as $key => $value) {
            $node->setAttribute($key, $value);
        }

        $node->setAttribute('value', $this->getDataToRender());
        $node->setAttribute('type', $this->type);

        return $node;
    }
}