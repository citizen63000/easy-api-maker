<?php

namespace EasyApiMaker\Framework;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\DBAL\DBALException;
use EasyApiBundle\Model\EntityConfiguration;
use EasyApiBundle\Util\Entity\EntityConfigLoader;
use Symfony\Component\DependencyInjection\ContainerInterface;

class AbstractGenerator
{
    public const DEFAULT_SKELETON_PATH = '@EasyApiMaker/templates/skeleton/';

    /**
     * @var string
     */
    protected static $templatesDirectory = '';

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var EntityConfiguration
     */
    protected $config;

    /**
     * AbstractGenerator constructor.
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @return ContainerInterface
     */
    protected function getContainer(): ContainerInterface
    {
        return $this->container;
    }

    /**
     * @return EntityConfiguration
     */
    protected function getConfig(): EntityConfiguration
    {
        return $this->config;
    }

    /**
     * @param string $entityName
     * @param string $tableName
     * @param string|null $schema
     * @param string|null $parentEntityName
     * @param string|null $inheritanceType
     * @param string|null $context
     * @return EntityConfiguration
     * @throws DBALException
     */
    protected function loadEntityConfigFromDatabase(string $entityName, string $tableName, string $schema = null, string $parentEntityName = null, string $inheritanceType = null, string $context =null): EntityConfiguration
    {
        return EntityConfigLoader::createEntityConfigFromDatabase($this->getDoctrine()->getManager(), $entityName, $tableName, $schema, $parentEntityName, $inheritanceType, $context);
    }

    /**
     * @param string $entityName
     * @param string|null $context
     * @return EntityConfiguration|null
     */
    protected function loadEntityConfig(string $entityName, string $context = null): ?EntityConfiguration
    {
        $this->config = EntityConfigLoader::findAndCreateFromEntityName($entityName);

//        if ($parentEntityName) {
//            $parentConfig = EntityConfigLoader::findAndCreateFromEntityName($parentEntityName);
//            $this->config->setParentEntity($parentConfig);
//        }

        return $this->config;
    }

    /**
     * @return string
     */
    protected static function getConsoleCommand(): string
    {
        return 'bin/console';
    }

    /**
     * @param string $directory
     * @param string $filename
     * @param string $fileContent
     * @param bool $dumpExistingFiles
     * @param bool $returnAbsolutePath
     * @return string
     */
    protected function writeFile(string $directory, string $filename, string $fileContent, bool $dumpExistingFiles = false, bool $returnAbsolutePath = false): string
    {
        $destinationFile = '/' === $directory[strlen($directory)-1] ? "{$directory}{$filename}" : "{$directory}/{$filename}";

        // create directory if necessary
        if (!is_dir($directory) && !mkdir($directory, 0755, true)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $directory));
        }

        if ($dumpExistingFiles && file_exists($destinationFile)) {
            rename($destinationFile, "{$destinationFile}.old");
        }

        file_put_contents($destinationFile, $fileContent);

        return ($returnAbsolutePath ? "{$this->container->getParameter('kernel.project_dir')}/" : '').$destinationFile;
    }

    /**
     * @return string
     */
    protected function getSkeletonPath(): string
    {
        $configPath = $this->container->getParameter('easy_api_maker.inheritance.generator_skeleton_path');

        return $configPath ?? self::DEFAULT_SKELETON_PATH;
    }

    /**
     * @param string $templateName
     * @return string
     */
    protected function getTemplatePath(string $templateName): string
    {
        return $this->getSkeletonPath().static::$templatesDirectory."/$templateName";
    }

    /**
     * Return the path of the entity.
     * @param string $context
     * @return string
     */
    protected function generateEntityFolderPath(string $context): string
    {
        return "src/Entity/{$context}/";
    }

    /**
     * Shortcut to return the Doctrine Registry service.
     *
     * @return ManagerRegistry
     *
     * @throws \LogicException If DoctrineBundle is not available
     *
     * @final since version 3.4
     */
    protected function getDoctrine()
    {
        if (!$this->container->has('doctrine')) {
            throw new \LogicException('The DoctrineBundle is not registered in your application. Try running "composer require symfony/orm-pack".');
        }

        return $this->container->get('doctrine');
    }

    /**
     * @param $typeName
     * @return string|null
     */
    public static function findTypeFile($typeName): ?string
    {
        return self::findFileRecursive('src/Form/Type', $typeName);
    }

    /**
     * @param $entityName
     * @return string|null
     */
    public static function findEntityFile($entityName): ?string
    {
        return self::findFileRecursive('src/Entity', "{$entityName}.php");
    }

    /**
     * @param $path
     * @param $filename
     *
     * @return string|null
     */
    protected static function findFileRecursive($path, $filename): ?string
    {
        $files = scandir($path);
        foreach ($files as $file) {
            if ('.' !== $file && '..' !== $file && is_dir($path.DIRECTORY_SEPARATOR.$file)) {
                $findFiles = self::findFileRecursive($path . DIRECTORY_SEPARATOR . $file, $filename);
                if (null !== $findFiles) {
                    return $findFiles;
                }
            } elseif ($file === $filename) {
                return $path.DIRECTORY_SEPARATOR.$file;
            }
        }

        return null;
    }
}