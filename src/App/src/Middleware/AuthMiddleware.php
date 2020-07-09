<?php

declare(strict_types=1);

namespace Frontend\App\Middleware;

use Dot\Rbac\Guard\Exception\RuntimeException;
use Dot\Rbac\Guard\Guard\GuardInterface;
use Dot\Rbac\Guard\Options\RbacGuardOptions;
use Dot\Rbac\Guard\Provider\GuardsProviderInterface;
use Dot\FlashMessenger\FlashMessenger;
use Laminas\Diactoros\Response\RedirectResponse;
use Mezzio\Router\RouterInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Class AuthTeamMiddleware
 * @package App\Admin\Middleware
 *
 */
class AuthMiddleware implements MiddlewareInterface
{

    /** @var RouterInterface $router */
    protected RouterInterface $router;

    /** @var FlashMessenger $messenger */
    protected FlashMessenger $messenger;

    /** @var GuardsProviderInterface $guardProvider */
    protected GuardsProviderInterface $guardProvider;

    /** @var  RbacGuardOptions */
    protected RbacGuardOptions $options;

    /**
     * IdentityMiddleware constructor.
     * @param RouterInterface $router
     * @param FlashMessenger $messenger
     * @param GuardsProviderInterface $guardProvider
     * @param RbacGuardOptions $options
     */
    public function __construct(
        RouterInterface $router,
        FlashMessenger $messenger,
        GuardsProviderInterface $guardProvider,
        RbacGuardOptions $options
    ) {
        $this->router = $router;
        $this->messenger = $messenger;
        $this->guardProvider = $guardProvider;
        $this->options = $options;
    }

    /**
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $guards = $this->guardProvider->getGuards();

        //iterate over guards, which are sorted by priority
        //break on the first one that does not grants access

        $isGranted = $this->options->getProtectionPolicy() === GuardInterface::POLICY_ALLOW;

        foreach ($guards as $guard) {
            if (!$guard instanceof GuardInterface) {
                throw new RuntimeException("Guard is not an instance of " . GuardInterface::class);
            }
            //according to the policy, we whitelist or blacklist matched routes

            $r = $guard->isGranted($request);
            if ($r !== $isGranted) {
                $isGranted = $r;
                break;
            }
        }

        if (!$isGranted) {
            $this->messenger->addWarning(
                'You must sign in first in order to access the requested content',
                'user-login'
            );

            return new RedirectResponse($this->router->generateUri("admin", ['action' => 'login']));
        }

        return $handler->handle($request);
    }
}
