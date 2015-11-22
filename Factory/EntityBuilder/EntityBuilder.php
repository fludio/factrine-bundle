<?php

namespace Fludio\FactrineBundle\Factory\EntityBuilder;

use Dflydev\DotAccessData\Data;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Fludio\FactrineBundle\Factory\EntityBuilder\Associations\ManyToMany;
use Fludio\FactrineBundle\Factory\EntityBuilder\Associations\ManyToOne;
use Fludio\FactrineBundle\Factory\EntityBuilder\Associations\OneToMany;
use Fludio\FactrineBundle\Factory\EntityBuilder\Associations\OneToOne;
use Symfony\Component\PropertyAccess\PropertyAccessor;

/**
 * Class EntityBuilder
 * @package Fludio\FactoryBundle\Factory\Util
 */
class EntityBuilder
{
    /**
     * @var ObjectManager
     */
    protected $em;
    /**
     * @var PropertyAccessor
     */
    protected $accessor;

    /**
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
        $this->accessor = new PropertyAccessor();
    }

    /**
     * @param $entity
     * @param array $params
     * @param null $callback
     * @return object
     */
    public function createEntity($entity, $params = [], $callback = null)
    {
        $params = $this->prepareParams($params);

        $meta = $this->em->getClassMetadata($entity);

        $instance = new $entity;

        foreach($meta->getFieldNames() as $field) {
            if(in_array($field, $meta->getIdentifierFieldNames())) {
                continue;
            }

            if($params->get($field) !== null) {
                $this->accessor->setValue($instance, $field, $params->get($field));
            }
        }

        foreach($meta->getAssociationNames() as $association) {
            $this->handleAssociation($association, $meta, $instance, $params);
        }
        
        if(is_callable($callback)) {
            $instance = $callback($instance);
        }

        return $instance;
    }

    /**
     * @param $params
     * @return Data
     */
    private function prepareParams($params)
    {
        $data = new Data();
        $params = is_array($params) ? $params : [];

        foreach($params as $path => $value) {
            $data->set($path, $value);
        }

        return $data;
    }

    /**
     * @param $association
     * @param ClassMetadataInfo $meta
     * @param $instance
     * @param Data $params
     * @throws \Doctrine\ORM\Mapping\MappingException
     */
    private function handleAssociation($association, ClassMetadataInfo $meta, $instance, Data $params)
    {
        $data = $params->get($association);
        if(is_array($data) && isset($data[0])) {
            foreach($data as $index => $set) {
                $assoc = $this->getAssocicationHandler($association, $meta);
                $assoc->handle($association, $meta, $instance, new Data([$association => $set]));
            }
        } else {
            $assoc = $this->getAssocicationHandler($association, $meta);
            $assoc->handle($association, $meta, $instance, $params);
        }
    }

    /**
     * @param $association
     * @param ClassMetadataInfo $meta
     * @return ManyToMany|ManyToOne|OneToMany|OneToOne
     * @throws \Doctrine\ORM\Mapping\MappingException
     */
    private function getAssocicationHandler($association, ClassMetadataInfo $meta)
    {
        $mapping = $meta->getAssociationMapping($association);

        switch ($mapping['type']) {
            case ClassMetadataInfo::ONE_TO_ONE:
                $assoc = new OneToOne($this->accessor, $this);
                break;
            case ClassMetadataInfo::MANY_TO_ONE:
                $assoc = new ManyToOne($this->accessor, $this);
                break;
            case ClassMetadataInfo::ONE_TO_MANY:
                $assoc = new OneToMany($this->accessor, $this);
                break;
            case ClassMetadataInfo::MANY_TO_MANY:
                $assoc = new ManyToMany($this->accessor, $this);
                break;
        }
        return $assoc;
    }
}
