<?php

namespace MssMessage;

abstract class AbstractModel
{
    protected $arrayData = array();

    /**
     * Render as an array.
     *
     * @return array
     */
    public function toArray()
    {
        $vars   = get_object_vars($this);
        $result = array();

        foreach($vars as $name => $value) {
            if (!in_array($name, $this->arrayData)) {
                continue;
            }

            $getter = 'get' . ucfirst($name);
            if (method_exists($this, $getter)) {
                $result[$name] = $this->$getter();
            }
        }

        return $result;
    }

    /**
     * Set from an array.
     *
     * @return MssMessage\Model\Message
     */
    public function fromArray(array $input)
    {
        foreach($input as $name => $value) {
            $setter = 'set' . ucfirst($name);
            if (method_exists($this, $setter)) {
                $this->$setter($input[$name]);
            }
        }

        return $this;
    }
}