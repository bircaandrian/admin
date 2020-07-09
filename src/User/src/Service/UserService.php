<?php

declare(strict_types=1);

namespace Frontend\User\Service;

use Dot\AnnotatedServices\Annotation\Inject;
use Dot\AnnotatedServices\Annotation\Service;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMException;
use Dot\Mail\Service\MailService;
use Frontend\App\Common\Message;
use Frontend\App\Common\UuidOrderedTimeGenerator;
use Frontend\User\Entity\Admin;
use Frontend\User\Entity\AdminInterface;
use Frontend\User\Entity\AdminRole;
use Frontend\User\Entity\User;
use Frontend\User\Entity\UserDetail;
use Frontend\User\Entity\UserInterface;
use Frontend\User\Entity\UserRole;
use Frontend\User\FormData\UserFormData;
use Frontend\User\Repository\UserRepository;
use Frontend\User\Repository\AdminRoleRepository;
use Laminas\Diactoros\UploadedFile;
use Mezzio\Template\TemplateRendererInterface;

/**
 * Class UserService
 * @package Frontend\Admin\Service
 *
 * @Service()
 */
class UserService implements UserServiceInterface
{
    public const EXTENSIONS = [
        'image/jpg' => 'jpg',
        'image/jpeg' => 'jpg',
        'image/png' => 'png'
    ];

    /** @var EntityManager $em */
    protected EntityManager $em;

    /** @var UserRepository $userRepository */
    protected $userRepository;

    /** @var UserRoleServiceInterface $userRoleService */
    protected UserRoleServiceInterface $userRoleService;

    /** @var AdminRoleRepository $adminRoleRepository */
    protected $adminRoleRepository;

    /** @var TemplateRendererInterface $templateRenderer */
    protected TemplateRendererInterface $templateRenderer;

    /** @var array $config */
    protected array $config;

    /**
     * UserService constructor.
     * @param EntityManager $em
     * @param UserRoleServiceInterface $userRoleService
     * @param TemplateRendererInterface $templateRenderer
     * @param array $config
     *
     * @Inject({EntityManager::class, UserRoleServiceInterface::class, TemplateRendererInterface::class, "config"})
     */
    public function __construct(
        EntityManager $em,
        UserRoleServiceInterface $userRoleService,
        TemplateRendererInterface $templateRenderer,
        array $config = []
    ) {
        $this->em = $em;
        $this->userRepository = $em->getRepository(User::class);
        $this->adminRoleRepository = $em->getRepository(AdminRole::class);
        $this->userRoleService = $userRoleService;
        $this->templateRenderer = $templateRenderer;
        $this->config = $config;
    }

    /**
     * @return UserRepository
     */
    public function getUserRepository(): UserRepository
    {
        return $this->userRepository;
    }

    /**
     * @param UserFormData $data
     * @return UserInterface
     * @throws ORMException
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function createUser(UserFormData $data): UserInterface
    {
        if ($this->exists($data->identity)) {
            throw new ORMException('An account with this identity already exists.');
        }

        $user = new User();
        $user->setPassword(password_hash($data->password, PASSWORD_DEFAULT))->setIdentity($data->identity);

        $detail = new UserDetail();
        $detail->setUser($user)->setFirstName($data->firstName)->setLastName($data->lastName);

        $user->setDetail($detail);

        if (!empty($data->status)) {
            $user->setStatus($data->status);
        }

        if (!empty($data->roleUuid)) {
            $role = $this->userRoleService->getUserRoleRepository()->getRole($data->roleUuid);
            if (!$role instanceof UserRole) {
                throw new \Exception('Role with uuid : ' . $data->roleUuid . ' not found!');
            }
            $user->addRole($role);
        } else {
            $role = $this->userRoleService->getUserRoleRepository()->findOneBy(['name' => UserRole::ROLE_USER]);
            if ($role instanceof UserRole) {
                $user->addRole($role);
            }
        }

        if (empty($user->getRoles())) {
            throw new \Exception('User account must have at least one role');
        }

        $this->userRepository->saveUser($user);

        return $user;
    }


    /**
     * @param User $user
     * @param UserFormData $data
     * @return User
     * @throws ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function updateUser(User $user, UserFormData $data)
    {
        if (!empty($data->identity)) {
            if ($this->exists($data->identity) && $data->identity !== $user->getIdentity()) {
                throw new ORMException('An account with this identity already exists.');
            }
            $user->setIdentity($data->identity);
        }

        if (!empty($data->password)) {
            $user->setPassword(
                password_hash($data->password, PASSWORD_DEFAULT)
            );
        }

        if (!empty($data->status)) {
            $user->setStatus($data->status);
        }

        if (!empty($data->firstName)) {
            $user->getDetail()->setFirstName($data->firstName);
        }

        if (!empty($data->lastName)) {
            $user->getDetail()->setLastName($data->lastName);
        }

        if (!empty($data->roleUuid)) {
            $user->resetRoles();
            $role = $this->userRoleService->getUserRoleRepository()->findOneBy(['uuid' => $data->roleUuid]);
            if ($role instanceof UserRole) {
                $user->addRole($role);
            }
        }
        if (empty($user->getRoles())) {
            throw new \Exception('User accounts must have at least one role.');
        }

        $this->userRepository->saveUser($user);

        return $user;
    }

    /**
     * @param string $identity
     * @return bool
     */
    public function exists(string $identity = '')
    {
        return !is_null(
            $this->userRepository->exists($identity)
        );
    }

    /**
     * @param int $offset
     * @param int $limit
     * @param string|null $search
     * @param string $sort
     * @param string $order
     * @return array
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getUsers(
        int $offset = 0,
        int $limit = 30,
        string $search = null,
        string $sort = 'created',
        string $order = 'desc'
    ) {
        $result = [
            'rows' => [],
            'total' => $this->getUserRepository()->countUsers($search)
        ];
        $users = $this->getUserRepository()->getUsers($offset, $limit, $search, $sort, $order);

        /** @var User $user */
        foreach ($users as $user) {
            $roles = [];
            /** @var UserRole $role */
            foreach ($user->getRoles() as $role) {
                $roles[] = $role->getName();
            }

            $result['rows'][] = [
                'uuid' => $user->getUuid()->toString(),
                'identity' => $user->getIdentity(),
                'firstName' => $user->getDetail()->getFirstName(),
                'lastName' => $user->getDetail()->getLastname(),
                'roles' => implode(", ", $roles),
                'isDeleted' => $user->getIsDeleted(),
                'status' => $user->getStatus(),
                'created' => $user->getCreated()->format("Y-m-d")
            ];
        }

        return $result;
    }

    /**
     * @param array $params
     * @return Admin|null
     */
    public function findOneBy(array $params = []): ?Admin
    {
        if (empty($params)) {
            return null;
        }

        /** @var Admin $user */
        $user = $this->userRepository->findOneBy($params);

        return $user;
    }

    /**
     * @param string $email
     * @return array
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getRoleNamesByEmail(string $email)
    {
        $roleList = [];

        /** @var Admin $user */
        $user = $this->userRepository->getUserByEmail($email);

        if (!empty($user)) {
            /** @var AdminRole $role */
            foreach ($user->getRoles() as $role) {
                $roleList[] = $role->getName();
            }
        }

        return $roleList;
    }

    /**
     * @return array
     */
    public function getAdminFormProcessedRoles()
    {
        $roles = [];
        $result = $this->adminRoleRepository->getRoles();

        if (!empty($result)) {
            /** @var AdminRole $role */
            foreach ($result as $role) {
                $roles[$role->getUuid()->toString()] = $role->getName();
            }
        }

        return $roles;
    }

    /**
     * @return array
     */
    public function getUserFormProcessedRoles()
    {
        $roles = [];
        $result = $this->userRoleService->getUserRoleRepository()->getRoles();

        if (!empty($result)) {
            /** @var UserRole $role */
            foreach ($result as $role) {
                $roles[$role->getUuid()->toString()] = $role->getName();
            }
        }

        return $roles;
    }
}
