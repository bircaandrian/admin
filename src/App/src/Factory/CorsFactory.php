<?php

declare(strict_types=1);

namespace Frontend\App\Factory;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Tuupola\Middleware\CorsMiddleware;
use Laminas\Diactoros\Response\JsonResponse;

/**
 * Class CorsFactory
 * @package Frontend\App\Factory
 */
class CorsFactory
{
    /**
     * @param ContainerInterface $container
     * @return CorsMiddleware
     */
    public function __invoke(ContainerInterface $container)
    {
        return new CorsMiddleware(
            $container->get('config')['cors']
        );
    }

    /**
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @param array $arguments
     * @return JsonResponse
     */
    public static function error(RequestInterface $request, ResponseInterface $response, array $arguments = [])
    {
        return new JsonResponse($arguments);
    }
}
