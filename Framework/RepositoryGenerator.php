<?php

namespace EasyApiMaker\Framework;

use EasyApiCore\Model\EntityConfiguration;

class RepositoryGenerator extends AbstractGenerator
{
    protected function generateContent(): array
    {
        $content = [];

        $content['class_name'] = $this->config->getEntityName().'Repository';
        $content['namespace'] = $this->config->getBundleName().'\Repository'.($this->config->getContextName() ? '\\'.$this->config->getContextName() : '');

        $content['uses'] = [];
        $content['uses'][] = $this->container->getParameter('easy_api_maker.inheritance.repository');
        $content['parent'] = EntityConfiguration::getEntityNameFromNamespace($this->container->getParameter('easy_api_maker.inheritance.repository'));

        return $content;
    }

    public function generate(string $entityName, ?string $context, bool $dumpExistingFiles = false): array
    {
        $this->config = $this->loadEntityConfig($entityName, $context);
        $path = 'Repository/'.($this->config->getContextName() ? $this->config->getContextName().'/' : '');
        $this->config->setRepositoryClass('App\\'.str_replace('/', '\\', $path).$this->config->getEntityName().'Repository');
        $filename = $this->config->getEntityName().'Repository.php';

        $files = [];

        // Create entityRepository file
        $fileContent = $this->getContainer()->get('twig')->render(
            $this->getTemplatePath('doctrine/entity_repository.php.twig'),
            $this->generateContent()
        );

        $files[] = "{$this->container->getParameter('kernel.project_dir')}/".$this->writeFile("src/{$path}", $filename, $fileContent, $dumpExistingFiles);

        $files[] = $this->updateEntity( 'annotations', $dumpExistingFiles);

        return $files;
    }

    /**+
     * @param string $type
     * @param bool $dumpExistingFiles
     * @return string
     */
    protected function updateEntity(string $type, bool $dumpExistingFiles = false)
    {
        switch ($type){
            case 'annotations':
                return $this->updateEntityClass($dumpExistingFiles);
        }
    }

    /**
     * @param bool $dumpExistingFiles
     * @return string
     */
    protected function updateEntityClass(bool $dumpExistingFiles = false): string
    {
        $content = file_get_contents($this->config->getEntityFileClassPath());
        $repositoryClass = $this->config->getRepositoryClass();

        if (preg_match('/@ORM\\\Entity\(\)/', $content)) {
            $content = str_replace('@ORM\\Entity()', "@ORM\Entity(repositoryClass=\"{$repositoryClass}\")", $content);
        } elseif (preg_match('/@ORM\\\Entity\(.*repositoryClass="(.+)"\)/', $content, $matches)) {
            $content = str_replace($matches[1], $repositoryClass, $content);
        }

        return $this->writeFile($this->config->getEntityFileClassDirectory(), $this->config->getEntityFileClassName(), $content, $dumpExistingFiles);;
    }
}
