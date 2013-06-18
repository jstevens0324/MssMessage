<?php

namespace MssMessage\Service;

use MssMessage\Mapper\Mergeword\MapperInterface,
    MssMessage\Mergeword\MergewordInterface,
    MssMessage\MergewordSet;

class Mergeword
{
    const CLIENT_FIRST_NAME = 'clientFirstName';
    const CLIENT_LAST_NAME  = 'clientLastName';

    /**
     * @var MssMessage\Mapper\Messenger\MapperInterface
     */
    private $mapper;

    /**
     * A cached array of merge words indexed by company id.
     *
     * @var array
     */
    private $mergewordSets;

    /**
     * @var array
     */
    private $mergewords = array();

    public function __construct(MapperInterface $mapper)
    {
        $this->mapper  = $mapper;
    }

    public function getMergewords()
    {
        return $this->mergewords;
    }

    public function addMergeword(MergewordInterface $mergeword)
    {
        $this->mergewords[] = $mergeword;
        return $this;
    }

    public function findByCompanyId($companyId)
    {
        if (!isset($this->mergewordSets[$companyId])) {
            $this->mergewordSets[$companyId] = $this->mapper->findByCompanyId($companyId);
        }

        return $this->mergewordSets[$companyId];
    }

    public function mergeFromArray($string, MergewordSet $set, array $mergewords)
    {
        return $this->merge($set, $string, $mergewords);
    }

    protected function merge(MergewordSet $set, $string, array $mergewords)
    {
        $prefix  = $set->getPrefix();
        $suffix  = $set->getSuffix();

        // add defaults to prefix/suffix if they do not exist
        if (!strstr($prefix, '{')) {
            $prefix.= ',{';
        }

        if (!strstr($suffix, '}')) {
            $suffix.= ',}';
        }

        // copy aliases so they get replaced alongside the defaults
        foreach($set->getAliases() as $alias => $mergeword) {
            if (array_key_exists($mergeword, $mergewords)) {
                $mergewords[$alias] = $mergewords[$mergeword];
            }
        }

        $prefixRegex = str_replace(',', '|', preg_quote($prefix));
        $suffixRegex = str_replace(',', '|', preg_quote($suffix));
        foreach($mergewords as $find => $replace) {
            $pattern = sprintf('/[%s]%s[%s]/', $prefixRegex, $find, $suffixRegex);
            $string  = preg_replace($pattern, $replace, $string);

            if (null === $string) {
                throw new RuntimeException(sprintf(
                    'failed to replace_all using pattern: %s',
                    $pattern
                ));
            }
        }

        return $string;
    }
}
