<?php

namespace MssMessage;

class MergewordSet
{
    private $id;
    
    private $name;
    
    private $prefix;
    
    private $suffix;
    
    private $aliases = array();

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set id
     *
     * @param integer $id
     * @return MssMessage\Model\MergewordAlias
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return MssMessage\Model\MergewordAlias
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Get prefix
     *
     * @return string
     */
    public function getPrefix()
    {
        return $this->prefix;
    }

    /**
     * Set prefix
     *
     * @param string $prefix
     * @return MssMessage\Model\MergewordAlias
     */
    public function setPrefix($prefix)
    {
        $this->prefix = $prefix;
        return $this;
    }

    /**
     * Get suffix
     *
     * @return string
     */
    public function getSuffix()
    {
        return $this->suffix;
    }

    /**
     * Set suffix
     *
     * @param string $suffix
     * @return MssMessage\Model\MergewordAlias
     */
    public function setSuffix($suffix)
    {
        $this->suffix = $suffix;
        return $this;
    }
    
    /**
     * Adds an alias linked to a mergeword. Aliases are added
     * to the $aliases array and indexed by mergeword.
     */
    public function addAlias($mergeword, $alias)
    {
        $this->aliases[$alias] = $mergeword;
        return $this;
    }

    /**
     * Get aliases
     *
     * @return array
     */
    public function getAliases()
    {
        return $this->aliases;
    }
}