<?php

declare(strict_types=1);

use Dot\Mail\Factory\MailOptionsAbstractFactory;
use Dot\Mail\Factory\MailServiceAbstractFactory;
use Dot\Mail\Service\MailService;
use Frontend\App\Factory\CorsFactory;
use Dot\ErrorHandler\ErrorHandlerInterface;
use Dot\ErrorHandler\LogErrorHandler;
use Frontend\App\Middleware\AuthMiddleware;
use Frontend\App\Factory\AuthMiddlewareFactory;

return [
    // Provides application-wide services.
    // We recommend using fully-qualified class names whenever possible as
    // service names.
    'dependencies' => [
        // Use 'aliases' to alias a service name to another service. The
        // key is the alias name, the value is the service to which it points.
        'aliases' => [
            ErrorHandlerInterface::class => LogErrorHandler::class,
            \Dot\Authorization\AuthorizationInterface::class => \Dot\Rbac\Authorization\AuthorizationService::class,
            \Laminas\Authentication\AuthenticationService::class => 'doctrine.authenticationservice.orm_default',
            MailService::class => 'dot-mail.service.default',
        ],
        // Use 'invokables' for constructor-less services, or services that do
        // not require arguments to the constructor. Map a service name to the
        // class name.
        'invokables' => [
            // Fully\Qualified\InterfaceName::class => Fully\Qualified\ClassName::class,
        ],
        // Use 'factories' for services provided by callbacks/factory classes.
        'factories'  => [
            'dot-mail.options.default' => MailOptionsAbstractFactory::class,
            'dot-mail.service.default' => MailServiceAbstractFactory::class,
            Tuupola\Middleware\CorsMiddleware::class => CorsFactory::class,
            AuthMiddleware::class => AuthMiddlewareFactory::class,
        ],
    ],
];
