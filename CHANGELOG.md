CHANGELOG
-------------
### v2.1.0, 2016.03.05

* Added unit testing support
* Modified `Opis\Utils\Validator::validate` method
* Modified `Opis\Utils\Placeholder::replace` method

### v2.0.1, 2016.03.03

* Fixed a bug in `Opis\Utils\Url::URI_REGEX`

### v2.0.0, 2015.12.20

* Added `Opis\Utils\Placeholder` class
* Modified `Opis\Utils\Validator` class. Error text are now handled with the help
of the `Placeholder` class. The `register` method is no longer static and the
`getField` method was removed.
* Modified `Opis\Utils\ClassLoad` class. The class can be instantiated now and does no longer
contain static methods.

### v1.1.0, 2015.08.27

* Added `Mutex` class
* Added `ArrayHandler` class

### v1.0.0

* Started CHANGELOG