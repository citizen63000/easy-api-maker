<?php

namespace EasyApiMaker\Framework;

use EasyApiCore\Model\EntityConfiguration;
use EasyApiCore\Model\EntityField;
use EasyApiCore\Util\Entity\EntityConfigLoader;
use EasyApiCore\Util\String\CaseConverter;
use EasyApiCore\Util\String\Inflector;

class EntityGenerator extends AbstractGenerator
{
    public const DEFAULT_ENTITY_SKELETON = 'doctrine/entity.php.twig';
    protected bool $useDoctrineAnnotations = true;

    protected const doctrineAnnotationAlias = 'ORM';
    protected static string $doctrineAnnotationPrefix = '@'.self::doctrineAnnotationAlias;

    /**
     * @return array
     * @throws \ReflectionException
     */
    protected function generateContent()
    {
        $content = ['fields' => [], 'uses' => [], '__construct' => ['fields' => []], 'classAnnotations' => []];
        $content['namespace'] = $this->getConfig()->getNamespace();
        $content['classname'] = $this->getConfig()->getEntityName();
        $content['extend'] = $this->getConfig()->getEntityType();
        $parentConfig = $this->getConfig()->getParentEntity();
        $content['parent'] = $parentConfig ? $parentConfig->getEntityName() : null;

        if (null !== $parentConfig) {
            $content['uses'][] = $parentConfig->getNamespace().'\\'.$parentConfig->getEntityName();
        } else {
            // Extends AbstractBaseEntity ?
            if ( null === $content['parent']) {
                if (!$this->getConfig()->isReferential()
                    && $this->getConfig()->hasField('id', 'integer', true)
                    && $this->getConfig()->hasField('createdAt', 'datetime')
                    && $this->getConfig()->hasField('updatedAt', 'datetime')
            ) {
                    if ($this->getConfig()->hasField('uuid', 'string', true)) {
                        $parent = $this->container->getParameter('easy_api_maker.inheritance.entity_with_uuid');
                    } else {
                        $parent = $this->container->getParameter('easy_api_maker.inheritance.entity');
                    }
                    $content['uses'][] = $parent;
                    $content['parent'] = EntityConfiguration::getEntityNameFromNamespace($parent);

                    // remove fields of parent entity
                    $parentConfig = EntityConfigLoader::createEntityConfigFromAnnotations(null, $parent);

                } elseif($this->getConfig()->isReferential()) { // referential
                    $parent = $this->container->getParameter('easy_api_maker.inheritance.entity_referential');
                    $content['uses'][] = $parent;
                    $content['parent'] = EntityConfiguration::getEntityNameFromNamespace($parent);

                    // remove fields of parent entity
                    $parentConfig = EntityConfigLoader::createEntityConfigFromAnnotations(null, $parent);
                }
            }
        }

        if(null !== $parentConfig) {
            foreach ($parentConfig->getFields() as $field) {
                if($this->getConfig()->hasField($field->getName())) {
                    $this->getConfig()->removeField($field->getName());
                } else {
                    throw new \Exception("The table must have a field named {$field->getName()} (field of {$parentConfig->getFullName()} parent class)");
                }
            }
        }

        if($this->useDoctrineAnnotations) {
            $content['uses'][] = 'Doctrine\ORM\Mapping as '.self::doctrineAnnotationAlias;
            $content['classAnnotations'][] = static::$doctrineAnnotationPrefix.'\Entity()';
            $schema = $this->config->getSchema() ? "schema=\"{$this->config->getSchema()}\", ": '' ;
            $content['classAnnotations'][] = static::$doctrineAnnotationPrefix."\\Table({$schema}name=\"`{$this->config->getTableName()}`\")";
        }

        $content['uses'][] = 'Symfony\\Component\\Serializer\\Annotation\\Groups';

        foreach ($this->getConfig()->getFields() as $field) {

            $annotations = $this->useDoctrineAnnotations ? $this->getDoctrineAnnotationsForField($field) : [];
            $annotations = array_merge($annotations, $this->generateSerializerAnnotationsForField($field));

            $content['fields'][] = [
                'name' => $field->getName(),
                'type' => $field->getTypeForClass(),
                'getter' => $field->getGetterName(),
                'setter' => $field->getSetterName(),
                'adder' => $field->getAdderName(),
                'remover' => $field->getRemoverName(),
                'entityClassName' => $field->getEntityClassName(),
                'entityVarName' => lcfirst($field->getEntityClassName()),
                'field' => $field,
                'defaultValue' => 'boolean' === $field->getType() ? ($field->getDefaultValue() ? 'true' : 'false') : $field->getDefaultValue(),
                'annotations' => $annotations,
            ];

            // add use on file if it's not the same namespace
            if (!$field->isNativeType()) {
                if ('\Doctrine\Common\Collections\ArrayCollection' === $field->getType()) {
                    $content['__construct']['fields'][] = ['name' => $field->getName(), 'entityType' => 'Collection'];
                    if (!in_array('\Doctrine\Common\Collections\ArrayCollection', $content['uses'])) {
                        $content['uses'][] = '\Doctrine\Common\Collections\ArrayCollection';
                    }
                    if (!in_array('\Doctrine\Common\Collections\Collection', $content['uses'])) {
                        $content['uses'][] = '\Doctrine\Common\Collections\Collection';
                    }
                }

                if (!in_array($field->getEntityType(), $content['uses']) && !empty($field->getEntityNamespace()) && $field->getEntityNamespace() !== $content['namespace']) {
                    $content['uses'][] = $field->getEntityType();
                }
            } elseif ('uuid' === $field->getType()) {
                $content['uses'][] = 'Ramsey\Uuid\Uuid';
                $content['uses'][] = 'Ramsey\Uuid\UuidInterface';
                $content['__construct']['fields'][] = ['name' => $field->getName(), 'entityType' => 'uuid'];
            }
        }

        return $content;
    }

    protected function getDoctrineAnnotationsForField(EntityField $field)
    {
        $annotations = [];
        $options = $field->isRequired() ? ', nullable=false' : ', nullable=true';
        $ormPrefix = static::$doctrineAnnotationPrefix;

        if ('decimal' === $field->getType()) {
            $options = ', scale=' . $field->getScale() . ', precision=' . $field->getPrecision();
        } elseif ('string' === $field->getType()) {
            $options = ", length=\"{$field->getLength()}\"";
        }

        if ($field->isPrimary()) {
            $annotations[] = "{$ormPrefix}\Id()";

            if ($field->isAutoIncremented()) {
                $annotations[] = "{$ormPrefix}\GeneratedValue()";
            }
        }

        if ($field->isNativeType()) {
            $annotations[] = "{$ormPrefix}\Column(type=\"{$field->getType()}\"{$options})";
        } else {
            switch ($field->getRelationType()) {
                case 'manyToOne':
                    $inversedBy = Inflector::pluralize(lcfirst($this->config->getEntityName()));
                    $nullable = $field->isRequired() ? 'false' : 'true'; 
                    $annotations[] = "{$ormPrefix}\ManyToOne(targetEntity=\"{$field->getEntityType()}\", inversedBy=\"{$inversedBy}\")";
                    $joinColumn = "{$ormPrefix}\JoinColumn(name=\"{$field->getTableColumnName()}\", referencedColumnName=\"{$field->getReferencedColumnName()}\", nullable={$nullable})";
                    $annotations[] = "{$ormPrefix}\JoinColumns({$joinColumn})";
                    break;
                case 'oneToOne':
                    $annotations[] = "{$ormPrefix}\OneToOne(targetEntity=\"{$field->getEntityType()}\")";
                    $nullable = $field->isRequired() ? 'false' : 'true';
                    $joinColumn = "{$ormPrefix}\JoinColumn(name=\"{$field->getTableColumnName()}\", referencedColumnName=\"{$field->getReferencedColumnName()}\", nullable={$nullable})";
                    $annotations[] = "{$ormPrefix}\JoinColumns({$joinColumn})";
                    break;
                case 'oneToMany':
                    $mapped = lcfirst($this->config->getEntityName());
                    $annotations[] = "{$ormPrefix}\OneToMany(targetEntity=\"{$field->getEntityType()}\", mappedBy=\"{$mapped}\", cascade={}, orphanRemoval=true)";
                    break;
                case 'manyToMany':
                    $inversedBy = Inflector::pluralize(lcfirst($this->config->getEntityName()));
                    $relationParam = strpos($field->getJoinTable(), $field->getEntity()->getTableName()) == 0 ? 'mappedBy' : 'inversedBy';
                    $annotations[] = "{$ormPrefix}\ManyToMany(targetEntity=\"{$field->getEntityType()}\", {$relationParam}=\"{$inversedBy}\", cascade={})";
                    $annotations[] = "{$ormPrefix}\JoinTable(schema=\"`{$field->getJoinTableSchema()}`\", name=\"{$field->getJoinTable()}\",";
                    $annotations[] = "\tjoinColumns={";
                    $annotations[] = "\t\t{$ormPrefix}\JoinColumn(name=\"{$field->getTableColumnName()}\", referencedColumnName=\"{$field->getReferencedColumnName()}\")";
                    $annotations[] = "\t},";
                    $annotations[] = "\tinverseJoinColumns={";
                    $annotations[] = "\t\t{$ormPrefix}\JoinColumn(name=\"{$field->getInverseTableColumnName()}\", referencedColumnName=\"{$field->getInverseReferencedColumnName()}\")";
                    $annotations[] = "\t}";
                    $annotations[] = ')';
                    break;
            }
        }

        return $annotations;
    }

    protected function generateSerializerAnnotationsForField(EntityField $field): array
    {
        $prefix = CaseConverter::convertCamelCaseToSnakeCase($this->config->getEntityName());
        $groups = ["{$prefix}_full"];
        if($field->isPrimary()) {
            $groups[] = "{$prefix}_id";
        }

        return ['@Groups({"'.implode('","', $groups).'"})'];
    }

    /**
     * Return the path to the entity skeleton
     */
    protected function getEntitySkeletonPath(): string
    {
        return $this->getTemplatePath(self::DEFAULT_ENTITY_SKELETON);
    }

    /**
     * @throws \Doctrine\DBAL\DBALException
     * @throws \ReflectionException
     */
    public function generate(string $tableName, string $entityName, string $schema = null, string $parentName = null, string $inheritanceType = null, string $context = null, bool $dumpExistingFiles = true): string
    {
        $this->config = $this->loadEntityConfigFromDatabase($entityName, $tableName, $schema, $parentName, $inheritanceType, $context);

        $destinationDir = str_replace(['\\', 'App/'], ['/', ''], 'src\\'.$this->config->getNamespace().'\\');
        $filename = $this->config->getEntityName().'.php';
        $fileContent = $this->getTwig()->render(
            $this->getEntitySkeletonPath(),
            $this->generateContent()
        );

        // clean code
        $fileContent = str_replace(["{\n\n", ";\n\n\n", "}\n\n\n", "}\n\n}"], ["{\n", ";\n\n", "}\n\n", "}\n}"], $fileContent);

        return $this->writeFile($destinationDir, $filename, $fileContent, $dumpExistingFiles);
    }
}
