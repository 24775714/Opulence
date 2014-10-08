# Dependency Injection

## Table of Contents
1. [Introduction](#introduction)
  1. [Explanation of Dependency Injection](#explanation-of-dependency-injection)
  2. [Dependency Injection Container](#dependency-injection-container)
2. [Basic Usage](#basic-usage)
3. [Binding for a Specific Class](#binding-for-a-specific-class)
4. [Creating New Instances](#creating-new-instances)
5. [Creating Singletons](#creating-singletons)
6. [Passing Constructor Primitives](#passing-constructor-primitives)
7. [Using Setters](#using-setters)

## Introduction
#### Explanation of Dependency Injection
*Dependency Injection* refers to the practice of passing a class its dependencies instead of the class creating them on its own.  This is very useful for creating loosely-coupled, testable code.  Let's take a look at an example that doesn't use dependency injection:

```php
class Foo
{
    private $database;

    public function __construct()
    {
        $this->database = new Database();
    }

    public function insertIntoDatabase($query)
    {
        return $this->database->insert($query);
    }
}
```

Databases are complex, and unit testing them is very tricky.  To make unit testing simpler, we could mock the database class so that we don't ever actually query a real database:
```php
class DatabaseMock extends Database
{
    public function insert($query)
    {
        return true;
    }
}
```

The issue with `Foo` is that it creates its own instance of `Database`, so there's no way to pass it `DatabaseMock` without having to rewrite the class just for the test.  The solution is to "inject" the `Database` dependency into `Foo`:
```php
class Foo
{
    private $database;

    public function __construct(Database $database)
    {
        $this->database = $database;
    }

    public function insertIntoDatabase($query)
    {
        return $this->database->insert($query);
    }
}
```

The difference is subtle, but now we can easily inject `DatabaseMock` when writing unit tests:
```php
$database = new DatabaseMock();
$foo = new Foo($database);
echo $foo->insertIntoDatabase("bar"); // "1"
```

By inverting the control of dependencies (meaning classes no longer maintain their own dependencies), we've made our code easier to test.

#### Dependency Injection Container
Hopefully, you can see that injecting dependencies is a simple, yet powerful feature.  Now the question is "Where should I inject the dependencies from?"  The answer is a **dependency injection container** (we'll call it a **container** from here on out).  A container can take a look at a constructor/setter methods and determine what dependencies a class relies on.  It creates a collection of various dependencies and automatically injects them into classes.  One of the coolest features of containers is the ability to bind a concrete class to an interface or abstract class.  In other words, it'll inject the concrete class implementation whenever there's a dependency on its interface or base class.  This frees you to "code to an interface, not an implementation".  At runtime, you can bind classes to interfaces, and execute your code.

## Basic Usage
The **container** looks at type hints in methods to determine the type of dependency a class relies on.  The container even lets you specify values for primitive types, eg strings and numbers.

Let's take a look at a class `A` that has a dependency on `IFoo`:
```php
interface IFoo
{
    public function sayHi();
}

class ConcreteFoo implements IFoo
{
    public function sayHi()
    {
        echo "Hi";
    }
}

class A
{
    private $foo;

    public function __construct(IFoo $foo)
    {
        $this->foo = $foo;
    }

    public function getFoo()
    {
        return $this->foo;
    }
}
```

If we always want to pass in an instance of `ConcreteFoo` when there's a dependency on `IFoo`, we can bind the two:
```php
use RDev\Models\IoC;

$container = new IoC\Container();
$container->bind("IFoo", "ConcreteFoo");
```

Now, whenever a dependency on `IFoo` is detected, the container will inject an instance of `ConcreteFoo`.  To create an instance of `A` with its dependencies set, simply:
```php
$a = $container->createNew("A");
$a->getFoo()->sayHi(); // "Hi"
```

As you can see, the container automatically injected an instance of `ConcreteFoo`.

## Binding for a Specific Class
By default, bindings are registered so that they can be used by all classes.  If you'd like to bind a concrete class to an interface or abstract class for only a specific class, you can create a targeted binding:
```php
use RDev\Models\IoC;

$container = new IoC\Container();
$container->bind("IFoo", "ConcreteFoo", "A");
```

Now, `ConcreteFoo` is only bound to `IFoo` for the target class `A`.

> **Note:** Targeted bindings take precedence over universal bindings.

## Creating New Instances
To create a brand new instance of a class with all of its dependencies injected, you can call `createNew()`:
```php
use RDev\Models\IoC;

$container = new IoC\Container();
$container->bind("IFoo", "ConcreteFoo");
$a1 = $container->createNew("A");
$a2 = $container->createNew("A");
echo $a1 === $a2; // "0"
```

## Creating Singletons
Singletons are shared instances of a class.  No matter how many times you create a singleton, you'll always get the same instance.  To create a singleton of a class with all of its dependencies injected, you can call `createSingleton()`:
```php
use RDev\Models\IoC;

$container = new IoC\Container();
$container->bind("IFoo", "ConcreteFoo");
$a1 = $container->createSingleton("A");
$a2 = $container->createSingleton("A");
echo $a1 === $a2; // "1"
```

## Passing Constructor Primitives
If your constructor depends on some primitive values, you can set them in both the `createNew()` and `createSingleton()` methods:
```php
use RDev\Models\IoC;

class B
{
    private $foo;
    private $additionalMessage;

    public function __construct(IFoo $foo, $additionalMessage)
    {
        $this->foo = $foo;
        $this->additionalMessage = $additionalMessage;
    }

    public function getFoo()
    {
        return $this->foo;
    }

    public function sayAdditionalMessage()
    {
        echo $this->additionalMessage;
    }
}

$container = new IoC\Container();
$container->bind("IFoo", "ConcreteFoo");
$b = $container->createNew("B", ["I love containers!"]);
echo get_class($b->getFoo()); // "ConcreteFoo"
$b->sayAdditionalMessage(); // "I love containers!"
```

Only the primitive values should be passed in the array.  They must appear in the same order as the constructor.

## Using Setters
Sometimes a class needs setter methods to pass in dependencies.  This is possible using both the `createNew()` and `createSingleton()` methods:
```php
use RDev\Models\IoC;

class C
{
    private $foo;
    private $additionalMessage;

    public function __construct()
    {
        // Don't do anything
    }

    public function getFoo()
    {
        return $this->foo;
    }

    public function sayAdditionalMessage()
    {
        echo $this->additionalMessage;
    }

    public function setFoo(IFoo $foo)
    {
        $this->foo = $foo;
    }

    public function setFooAndAdditionalMessage(IFoo $foo, $additionalMessage)
    {
        $this->foo = $foo;
        $this->additionalMessage = $additionalMessage;
    }
}

$container = new IoC\Container();
$container->bind("IFoo", "ConcreteFoo");
$c = $container->createNew("C", [], ["setFoo" => []]);
echo get_class($c->getFoo()); // "ConcreteFoo"
```

If your setter requires primitive values, you can pass them in, too:
```php
use RDev\Models\IoC;

$container = new IoC\Container();
$container->bind("IFoo", "ConcreteFoo");
$c = $container->createNew("C", [], ["setFooAndAdditionalMessage" => ["I love setters!"]]);
echo get_class($c->getFoo()); // "ConcreteFoo"
$c->sayAdditionalMessage(); // "I love setters!"
```