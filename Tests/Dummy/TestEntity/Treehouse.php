<?php

namespace Fludio\FactrineBundle\Tests\Dummy\TestEntity;

/**
 * @Entity
 */
class Treehouse extends House
{
    /**
     * @var string
     *
     * @Column(name="tree_type", type="string")
     */
    protected $treeType;

    /**
     * @return string
     */
    public function getTreeType()
    {
        return $this->treeType;
    }

    /**
     * @param string $treeType
     * @return $this
     */
    public function setTreeType($treeType)
    {
        $this->treeType = $treeType;

        return $this;
    }
}