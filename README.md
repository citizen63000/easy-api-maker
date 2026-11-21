# easy-api-maker

Code generator for the `easy-api` family: Symfony console commands (prefixed `api:make`) that generate entities, CRUD controllers, forms, repositories and integration tests from the database schema and existing code, using Twig skeletons.

## Related bundles

| Bundle | Purpose | Doc |
|--------|---------|-----|
| `citizen63000/easy-api-core` | Core utilities used by the generators (entity config introspection, `EntityConfigLoader`, string/file helpers) | [github.com/citizen63000/easy-api-core](https://github.com/citizen63000/easy-api-core) |
| `citizen63000/easy-api-bundle` | The bundle the generated code targets (base entities, `AbstractApiController`, CRUD traits, form types) | [github.com/citizen63000/easy-api-bundle](https://github.com/citizen63000/easy-api-bundle) |
| `citizen63000/easy-api-maker` (this one) | Code generator (`api:make:*` commands) | this README |
| `citizen63000/easy-api-tests` | Test framework the generated TI tests rely on (`EasyApiTests\Core\AbstractApiTestCase`, CRUD test traits) | [github.com/citizen63000/easy-api-tests](https://github.com/citizen63000/easy-api-tests) |

## Installation

```bash
composer require --dev citizen63000/easy-api-maker
```

Register the bundle in `config/bundles.php` (dev only is enough):

```php
return [
    // ... other bundles
    EasyApiMaker\EasyApiMaker::class => ['dev' => true],
];
```

Requires PHP >= 8.3 and `citizen63000/easy-api-core`.

## Configuration

Optional file `config/packages/easy_api_maker.yaml`. The `inheritance` section defines the parent classes and the skeleton path used by the generators (defaults target easy-api-bundle):

```yaml
easy_api_maker:
    inheritance:
        generator_skeleton_path: '@EasyApiMaker/skeleton/'
        entity: 'EasyApiBundle\Entity\AbstractBaseEntity'
        entity_with_uuid: 'EasyApiBundle\Entity\AbstractBaseUniqueEntity'
        entity_referential: 'EasyApiBundle\Entity\AbstractBaseReferential'
        form: 'EasyApiBundle\Form\Type\AbstractApiType'
        repository: 'EasyApiBundle\Util\AbstractRepository'
        controller: 'EasyApiBundle\Util\Controller\AbstractApiController'
        serialized_form: 'EasyApiBundle\Util\Forms\SerializedForm'
```

## Commands

All commands take a required `entity_name` argument (entity short name, underscores are rejected) and support `--context` (`-co`) to generate inside a context sub-namespace/folder, plus `--no-dump` (`-nd`) to skip dumping/regenerating existing files. Generated files are passed through `php-cs-fixer` automatically.

| Command | Description | Generator | Typical use |
|---------|-------------|-----------|-------------|
| `api:make:entity <entity_name>` | Generate the entity **from the database** (starting point) | `Framework\EntityGenerator` | `--schema` (`-sc`), `--table_name` (`-ta`), `--context`, `--parent` (`-pa`), `--inheritanceType` (`-in`, `joined`\|`superclass`), `--no-dump` |
| `api:make:crud <entity_name>` | Generate the CRUD controller from an existing entity | `Framework\CrudGenerator` | `--context`, `--no-dump` |
| `api:make:form <entity_name>` | Generate the form type from an existing entity | `Framework\FormGenerator` | `--context`, `--parent`, `--no-dump` |
| `api:make:repository <entity_name>` | Generate the repository from an existing entity | `Framework\RepositoryGenerator` | `--context`, `--no-dump` |
| `api:make:ti <entity_name>` | Generate the integration tests (TI) for the CRUD, from the entity and its routes | `Framework\TiCrudGenerator` | `--context`, `--no-dump` |

### Examples

```bash
# Entity from an existing table (entry point of the workflow)
bin/console api:make:entity Article --schema=memento_chatbot --table_name=article --context=Memento

# Then, once the entity exists
bin/console api:make:repository Article --context=Memento
bin/console api:make:form Article --context=Memento
bin/console api:make:crud Article --context=Memento

# Integration tests (uses the routes of the generated controller)
bin/console api:make:ti Article --context=Memento
```

### What `api:make:ti` generates

Under `tests/Functional/<Context>/<Entity>/` (see the [easy-api-tests README](https://github.com/citizen63000/easy-api-tests)):

- `Abstract<Context><Entity>.php`: abstract test class (`baseRouteName`, `entityClass`, `$additionalInitFiles`, `getRequiredFields()`)
- one concrete test class per action: `PostTest`, `GetTest`, `GetListTest`, `PutTest`, `DeleteTest`, `GetDescribeFormTest`
- fixture skeletons: `<entity>.yml` + `<entity>.csv` under `tests/data/csv/...`

Skeletons live in `templates/skeleton/` (`doctrine/` for entity/repository/form/controller, `TI/` for the tests and fixtures).

## Workflow

1. `api:make:entity` from the database table (point de départ)
2. `api:make:repository`, `api:make:form`, `api:make:crud` from the generated entity
3. `api:make:ti` to generate the integration tests
4. **Always review and adapt the generated code** to the project conventions before committing (the generated code is a starting point, not final)

## Compatibility

- PHP >= 8.3
- Symfony ^6.4 (console)

## Support

- **Issues:** [GitHub Issues](https://github.com/citizen63000/easy-api-maker/issues)
- **Documentation:** this README and docblocks in the code

## License

No license file is shipped with this package (see `composer.json`).
