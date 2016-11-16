[demo]: https://github.com/rainner/biscuit-app
[twitter]: http://twitter.com/raintek_
[mit]: http://www.opensource.org/licenses/mit-license.php

# Biscuit PHP

Biscuit PHP is a small stand-alone collection of classes that work together as a framework to help build small server-side applications with PHP.
It's intended to be minimal with no need for dependencies and comes with the basic tools needed for building a small RESTful api, or web application backend.
See the table below for what is included.

### Demo Application

For a demo application that uses this framework, refer to another repo that serves as a boilerplate to get started using this framework: [Biscuit-APP][demo]

### Current Namespaces

This is a table that lists the current Biscuit PHP framework namespaces and what each is responsible for.

| Namespace            | Description                                                             |
| -------------------- | ----------------------------------------------------------------------- |
| `Biscuit\Boot\*`     | Classes for managing error handling and runtime environment options.    |
| `Biscuit\Crypt\*`    | Classes for hashing, encrypting and decrypting strings.                 |
| `Biscuit\Data\*`     | Classes for handling, parsing, serializing, storing and rendering data. |
| `Biscuit\Db\*`       | Classes for handling database connection using a common interface.      |
| `Biscuit\Http\*`     | Classes for handling routing, request data, responses and server info.  |
| `Biscuit\Session\*`  | Classes for managing session storage and user authentication.           |
| `Biscuit\Storage\*`  | Classes for dealing with folders/files on the system and images.        |
| `Biscuit\Utils\*`    | Common utility classes for various other purposes.                      |

### Installation &amp; Setup

**Composer:** Run the following composer commands to include this package and install the dependency for your project:

````php
$ composer require rainner/biscuit-php 2.*
````

**Manual:** Clone this repo somewhere in your project and use the included autoloader to load the included classes:

````php
$ cd /path/to/project/libs
$ git clone https://github.com/rainner/biscuit-php.git
````

````php
<?php
require( './libs/biscuit-php/autoloader.php' )
````

### Author

Rainner Lins: [@raintek_][twitter]

### License

Licensed under [MIT][mit].


