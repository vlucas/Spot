Spot PHP ORM
----------------
For RDBMS (Currently only has a MySQL adapter)


Connecting to a Database
========================
The `Spot\Config` object stores and references database connections by name.
Create a new instance of `Spot\Config` and add database connections with
DSN strings so Spot can establish a database connection.

```
// MySQL
$cfg = new \Spot\Config();
$adapter = $cfg->addConnection('test_mysql', 'mysql://user:password@localhost/database_name');
```

Accessing the Mapper
====================

Since Spot follows the DataMapper design pattern, you will need a mapper
instance for working with object Entities and database tables.

```
$mapper = new \Spot\Mapper($cfg);
```

Since you have to have access to your mapper anywhere you use the
database, most people create a helper method to create a mapper instance
once and then return the same instance when required again. Such a
helper method might look something like this:

```
function get_mapper() {
    static $mapper;
    if($mapper === null) {
        $mapper = new \Spot\Mapper($cfg);
    }
    return $mapper;
}
```

Creating Entities
=================

Entity classes can be named and namespaced however you want to set them
up within your project structure. For the following examples, the
Entities will just be prefixed with an `Entity` namespace for easy psr-0
compliant autoloading.

```
namespace Entity;

class Post extends \Spot\Entity
{
    protected static $_datasource = 'posts';

    public static function fields()
    {
        return array(
            'id' => array('type' => 'int', 'primary' => true, 'serial' => true),
            'title' => array('type' => 'string', 'required' => true),
            'body' => array('type' => 'text', 'required' => true),
            'status' => array('type' => 'int', 'default' => 0, 'index' => true),
            'date_created' => array('type' => 'datetime')
        );
    }

    public static function relations()
    {
        return array(
            // Each post entity 'hasMany' comment entites
            'comments' => array(
                'type' => 'HasMany',
                'entity' => 'Entity_Post_Comment',
                'where' => array('post_id' => ':entity.id'),
                'order' => array('date_created' => 'ASC')
            )
        );
    }
}
```

### Built-in Field Types

All the basic field types are built-in with all the default
functionality provided for you:

 * `string`
 * `int`
 * `float/double/decimal`
 * `boolean`
 * `text`
 * `date`
 * `datetime`
 * `timestamp`
 * `year`
 * `month`
 * `day`

#### Registering Custom Field Types

If you want to register your own custom field type with custom
functionality on get/set, have a look at the clases in the `Spot\Type`
namespace, make your own, and register it in `Spot\Config`:

```
$this->typeHandler('string', '\Spot\Type\String');
```

### Relation Types

Entity relation types are:

 * `HasOne`
 * `HasMany`
 * `HasManyThrough`


Finders (Mapper)
================

The main finders used most are `all` to return a collection of entities,
and `first` or `get` to return a single entity matching the conditions.

### all(entityName, [conditions])

Find all `entityName` that matches the given conditions and return a
`Spot\Entity\Collection` of loaded `Spot\Entity` objects.
```
// Conditions can be the second argument
$posts = $mapper->all('Entity\Post', array('status' => 1));

// Or chained using the returned `Spot\Query` object - results identical to above
$posts = $mapper->all('Entity\Post')->where(array('status' => 1));
```

Since a `Spot\Query` object is returned, conditions and other statements
can be chained in any way or order you want. The query will be
lazy-executed on interation or `count`, or manually by ending the chain with a
call to `execute()`.

### first(entityName, [conditions])

Find and return a single `Spot\Entity` object that matches the criteria.
```
$post = $mapper->first('Entity\Post', array('title' => "Test Post"));
```




