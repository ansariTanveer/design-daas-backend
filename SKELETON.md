# Skeleton Application 2024-02-19-01

## Introduction

This file is intended to give an overview of the skeleton application, which features it includes and
how it is intended to be used and extended in an actual project.
The version number in the title can be used to determine whether a specific project has the latest changes
of the skeleton application applied to it.

## Files and directories that are part of the skeleton application and should not be modified

The `Main` namespace of the application represent the core of the runtime environment,
project specific development should happen somewhere else.

- `src/Main/`
- `src/Util/Main/`
- `tests/src/Arch/Main/`
- `tests/src/Integration/Main/`
- `tests/src/Unit/Main/`

The application bootstrap and core container configuration can be extended by adding additional
configuration files. Per convention the skeleton application ships its configuration in
files that are indexed with a leading zero, project specific additions should happen in separate
configurations files with an index of 1000 and upwards.

- `config/bootstrap.php`
- `config/bootstrap/0*`
- `config/container/0*`
- `config/container/cli/0*`
- `config/container/http/0*`
- `tests/prepare-application/0*`
- `tests/prepare-application/cli/0*`
- `tests/prepare-application/http/0*`

## Basic module definition and documentation

The `README.md` file contains a section called `Modules` which should be used to describe the
basic structure/architecture of the project and help a developer with orientation.

In addition to the description of the modules in that section, respective architecture tests (in `tests/src/Arch/`)
should be implemented to help in making sure that source code complies with the intended architecture.

## Environment variables and configuration

Application configuration is done using environment variables that are mapped into Dependency Injection container
values in `config/boostrap/*.env.php` files.

To allow proper testing it is important to not access the environment variables directly using `$_SERVER`, `getenv()`
or similar methods, but only read the values as explained above by mapping them in `config/boostrap/*.env.php` files
from the passed `$server` argument.

To add additional environment variables that can be used for configuration these have to be added in multiple places:

- in a `config/boostrap/*.env.php` file for the actual mapping
- in `public/.htaccess` to pass the environment variable from Apache to PHP (only required for HTTP requests)
- in `config/required_env.txt` in case the existence of the environment variable should be checked during
  startup of the Docker container
- in `scripts/generate-application-env.sh` to have the environment variable configured in the
  Docker development environment (this change only takes effect once `make init` is executed again)

## Cronjobs

Cronjobs are executed as a separate service using the tool `yacron`, the configuration is located
in `config/yacron.yml` and the services are defined in `docker-compose.yml` as `cron-dev` and `cron-release-test`
which execute the command `docker-cron` (`docker/php/apache/docker-cron`).

The times in `config/yacron.yml` are in the timezone of the Docker container that `yacron` runs in, which is UTC
by default and also should not be changed.

Avoid long-running cronjobs like listening to a message queue, doing extensive filesystem operations or
calling external services. Preferably limit the cronjobs to send messages to a message queue and let the consumers
do the actual work.

Missed cronjobs (for example because the service was not running) are NOT tracked and therefore will NOT be run
later (before the next trigger time of the cronjob). If the business logic requires missed cronjobs to be executed
as soon as possible, another (project specific) solution must be implemented.
A possible solution could be to define a cronjob with a short interval (for example every minute or every 5 minutes)
that triggers a check for actions that are (over-)due and then queues these to a message queue to be executed as soon
as possible.

## Internal Message Queues / Background processing

A database backed message queue is available using which allows sending and receiving of strings as messages.
Access the service using `\Application\Common\InternalMessageBroker\InternalMessageBrokerInterface`.

The queue service uses the same database connection as Doctrine, so when inside a transaction messages will only
be persisted (and therefore send) once the transaction is committed. This allows to have messages only send when
the corresponding transaction (for example HTTP request) completes successful.

Receiving (consuming) messages should be done as a CLI command that then can be started as a separate Docker container.

Sending/receiving messages happens asynchronously, so it is not possible to directly receive a response to a message
in the same PHP process.

## Events

In contrast to messages (sent to messages queues), which are processed asynchronously, events can be used to allow
other parts of the application to be notified about specific events.

Since events are processed synchronously (in the same PHP process), it is also possible to send an event and then
let subscribers modify some data of the event. This modified data will be available to the initial sender of the event
once all subscribers have finished processing.

Sending events can be done using `\Psr\EventDispatcher\EventDispatcherInterface`, the application is configured
to use the `Symfony Event System` internally.

To be recognized as an event subscriber a class must be named `*EventSubscriber` (have the correct suffix) and
implement `\Symfony\Component\EventDispatcher\EventSubscriberInterface`.

An example for an event subscriber is the class `\Application\Core\Main\ExceptionHandlerEventSubscriber`.
For more details about how to implement an event subscriber see
https://symfony.com/doc/5.x/event_dispatcher.html#creating-an-event-subscriber.

## Logging

Logging can be done using `\Psr\Log\LoggerInterface`, the application is configured to use `Monolog` internally.

The log messages are send depending on the configuration of the environment variables
`APPLICATION_LOG_STDOUT` and `APPLICATION_LOG_FILE`, with the minimum logged severity defined by the environment
variable `APPLICATION_LOG_LEVEL`.

Additional log targets can be added to the configuration setting `application.log.primary_handler_list` (in which by
default the handlers for `APPLICATION_LOG_STDOUT` and `APPLICATION_LOG_FILE` are present).

There is also the (by default empty) configuration setting `application.log.error_details_handler_list`, which can
be used for additional log targets that only get used if a log message is at least of severity `error`, but in that
case all log messages of that request (even prior ones of lower severity) get logged. This can be used to have specific
log targets that only get log messages of processes that caused errors and not all the clutter log messages of
successful processes.
