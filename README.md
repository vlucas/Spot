Spot PHP ORM
============
For RDBMS (Currently has a MySQL and Sqlite adapter)

Using Spot In Your Project
--------------------------

Spot is a standalone ORM that can be used in any project. Follow the
instructions below to get Spot setup in your project, or use a pre-coded
plugin for the framework you are using:

[Silex provider](https://github.com/psamatt/SpotServiceProvider) by [@psamatt](https://github.com/psamatt)

Connecting to a Database
------------------------
The `Spot\Config` object stores and references database connections by name.
Create a new instance of `Spot\Config` and add database connections with
DSN strings so Spot can establish a database connection.

```php

$cfg = new \Spot\Config();
// MySQL
$adapter = $cfg->addConnection('test_mysql', 'mysql://user:password@localhost/database_name');
// Sqlite
$adapter = $cfg->addConnection('test_sqlite', 'sqlite://path/to/database.sqlite');
```

If you are using Sqlite, the Sqlite filename must be the name of your database followed by the extension e.g `blogs.sqlite`

Accessing the Mapper
--------------------

Since Spot follows the DataMapper design pattern, you will need a mapper
instance for working with object Entities and database tables.

```php
$mapper = new \Spot\Mapper($cfg);
```

Since you have to have access to your mapper anywhere you use the
database, most people create a helper method to create a mapper instance
once and then return the same instance when required again. Such a
helper method might look something like this:

```php
function get_mapper() {
    static $mapper;
    if($mapper === null) {
        $mapper = new \Spot\Mapper($cfg);
    }
    return $mapper;
}
```

Creating Entities
-----------------

Entity classes can be named and namespaced however you want to set them
up within your project structure. For the following examples, the
Entities will just be prefixed with an `Entity` namespace for easy psr-0
compliant autoloading.

```php
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

```php
$config->typeHandler('string', '\Spot\Type\String');
```

Finders (Mapper)
----------------

The main finders used most are `all` to return a collection of entities,
and `first` or `get` to return a single entity matching the conditions.

### all(entityName, [conditions])

Find all `entityName` that matches the given conditions and return a
`Spot\Entity\Collection` of loaded `Spot\Entity` objects.

```php
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

```php
$post = $mapper->first('Entity\Post', array('title' => "Test Post"));
```

Or `first` can be used on a previous query with `all` to fetch only the first
matching record.

```php
$post = $mapper->all('Entity\Post', array('title' => "Test Post"))->first();
```

### Conditional Queries

```php
# All posts with a 'published' status, descending by date_created
$posts = $mapper->all('Entity\Post')
    ->where(array('status' => 'published'))
    ->order(array('date_created' => 'DESC'));

# All posts created before today
$posts = $mapper->all('Entity\Post')
    ->where(array('date_created <' => new \DateTime()));

# Posts with 'id' of 1, 2, 5, 12, or 15 - Array value = automatic "IN" clause
$posts = $mapper->all('Entity\Post')
    ->where(array('id' => array(1, 2, 5, 12, 15)));
```

### Searches

Spot supports search queries using LIKE, REGEX, and FULLTEXT searches (if your
adapter supports it).

#### Query#search(text)
The `search` method on `Spot\Query` will use a LIKE search, or a FULLTEXT
search if enabled. The [wiki page on searching](https://github.com/vlucas/Spot/wiki/Searching-with-LIKE,-REGEX,-and-FULLTEXT) has more details about using FULLTEXT searches.

```php
$q = "walrus"; // Text to search for
$results = $mapper->all('Entity\Post')
    ->search('body', $q)
    ->order(array('date_created' => 'DESC'));
```

#### LIKE searches
You can use the `:like` query operator to perform a LIKE search:

```php
$results = $mapper->all('Entity\Post')
    ->where('body :like', 'walrus%')
    ->order(array('date_created' => 'DESC'));
```

#### REGEX searches
You can use the `:regex` or `~=` query operator to perform a REGEX search:

```php
$results = $mapper->all('Entity\Post')
    ->where('body ~=', 'walrus.+')
    ->order(array('date_created' => 'DESC'));
```
NOTE: Most REGEX searches are very slow, so use this sparingly.


Relations
---------

Relations are convenient ways to access related, parent, and child entities from
another loaded entity object. An example might be `$post->comments` to query for
all the comments related to the current `$post` object.

### Relation Types

Entity relation types are:

 * `HasOne`
 * `HasMany`
 * `HasManyThrough`

### HasOne

HasOne is the simplest relation - an example might be `Post` has one `Author`.

```php
class Entity\Post extends \Spot\Entity
{
    protected static $_datasource = 'posts';

    public static function fields()
    {
        return array(
            'id' => array('type' => 'int', 'primary' => true, 'serial' => true),
            'author_id' => array('type' => 'int', 'required' => true),
            'title' => array('type' => 'string', 'required' => true),
            'body' => array('type' => 'text', 'required' => true),
            'status' => array('type' => 'int', 'default' => 0, 'index' => true),
            'date_created' => array('type' => 'datetime')
        );
    }

    public static function relations()
    {
        return array(
            // Each post 'hasOne' author
            'author' => array(
                'type' => 'HasOne',
                'entity' => 'Entity\Author',
                'where' => array('id' => ':entity.author_id')
            )
        );
    }
}
```

### HasMany

HasMany is used where a single record relates to multiple other records - an
example might be `Post` has many `Comments`.

We start by adding a `comments` relation to our `Post` object:
```php
class Entity\Post extends \Spot\Entity
{
    protected static $_datasource = 'posts';

    public static function fields()
    {
        return array(
            'id' => array('type' => 'int', 'primary' => true, 'serial' => true),
            'author_id' => array('type' => 'int', 'required' => true),
            'title' => array('type' => 'string', 'required' => true),
            'body' => array('type' => 'text', 'required' => true),
            'status' => array('type' => 'int', 'default' => 0, 'index' => true),
            'date_created' => array('type' => 'datetime')
        );
    }

    public static function relations()
    {
        return array(
            // Each post 'hasMany' comments
            'comments' => array(
                'type' => 'HasMany',
                'entity' => 'Entity\Post\Comment',
                'where' => array('post_id' => ':entity.id'),
                'order' => array('date_created' => 'ASC')
            )
        );
    }
}
```

And add a `Entity\Post\Comment` object with a 'hasOne' relation back to the post:

```php
class Entity\Post\Comment extends \Spot\Entity
{
    // ... snip ...

    public static function relations() {
      return array(
          // Comment 'hasOne' post (belongs to a post)
          'post' => array(
              'type' => 'HasOne',
              'entity' => 'Entity\Post',
              'where' => array('id' => ':entity.post_id')
          )
      );
    }
}
```

### HasManyThrough

HasManyThrough is used for many-to-many relationships. An good example is
tagging. A post has many tags, and a tag has many posts. This relation is
a bit more complex than the others, because a HasManyThrough requires a
join table and model.

We need to add the `tags` relation to our `Post` entity, specifying query
conditions for both sides of the relation.

```php
class Entity\Post extends \Spot\Entity
{
    // ... snip ...

    public static function relations()
    {
        return array(
            // Each post 'hasMany' tags `Through` a post_tags table
            'tags' => array(
                'type' => 'HasManyThrough',
                'entity' => 'Entity_Tag',
                'throughEntity' => 'Entity_PostTag',
                'throughWhere' => array('post_id' => ':entity.id'),
                'where' => array('id' => ':throughEntity.tag_id'),
            )
        );
    }
}
```

#### Explanation

The result we want is a collection of `Entity\Tag` objects where the id equals
the `post_tags.tag_id` column. We get this by going through the
`Entity\Post\Tags` entity, using the current loaded post id matching
`post_tags.post_id`.

Another scenario and more detailed explanation is on the [HasManyThrough wiki page](https://github.com/vlucas/Spot/wiki/HasManyThrough-Relations).