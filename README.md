# Vanilla blog test

This is an example frontend for Ubuntu blog post cards.

It is build on top of the [Slim](http://www.slimframework.com/) and [Vanilla](https://docs.vanillaframework.io/) frameworks.

## Building the application

This app contains build frontend components. A build is required.

### Requirements
* Node.js
* npm/yarn

### Install build dependencies and run build

    npm install
    npm run build

## Running the application

### Requirements
* PHP >= 7.2
* PHP JSON extension

**Recommended**
* PHP cURL extension

### Run the app
You may run the app with the built in PHP web server:

    composer install --no-dev
    composer run serve

## Running tests

    composer install
    ./vendor/bin/phpunit

## License
This project is licensed under the MIT License.
