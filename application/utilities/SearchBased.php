<?php

/**
 * Description of SearchBased
 *
 * @author shersingh
 */
class SearchBased extends SearchBase
{

    public function __construct(string $tbl, string $alias = '')
    {
        parent::__construct($tbl, $alias);
    }

    public function getFields()
    {
        return $this->flds;
    }

    public function removeFields()
    {
        $this->flds = [];
    }

    public function getConditions()
    {
        return $this->conditions;
    }

}
