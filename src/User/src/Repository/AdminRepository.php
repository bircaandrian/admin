<?php

declare(strict_types=1);

namespace Frontend\User\Repository;

use Frontend\App\Repository\AbstractRepository;
use Frontend\User\Entity\Admin;
use Ramsey\Uuid\Doctrine\UuidBinaryOrderedTimeType;

/**
 * Class AdminRepository
 * @package Frontend\User\Repository
 */
class AdminRepository extends AbstractRepository
{
    /**
     * @param Admin $admin
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function saveAdmin(Admin $admin)
    {
        $this->getEntityManager()->persist($admin);
        $this->getEntityManager()->flush();
    }

    /**
     * @param Admin $admin
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function deleteAdmin(Admin $admin)
    {
        $this->getEntityManager()->remove($admin);
        $this->getEntityManager()->flush();
    }

    /**
     * @param string $email
     * @param string $username
     * @return int|mixed|string|null
     */
    public function exists(?string $email = '', ?string $username = '')
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('user')
            ->from(Admin::class, 'user');

        if (!empty($email)) {
            $qb->where('user.email = :email')->setParameter('email', $email);
        }
        if (!empty($username)) {
            $qb->orWhere('user.username = :username')->setParameter('username', $username);
        }

        try {
            return $qb->getQuery()->getSingleResult();
        } catch (\Exception $exception) {
            return null;
        }
    }

    /**
     * @param int $offset
     * @param int $limit
     * @param string|null $search
     * @param string $sort
     * @param string $order
     * @return int|mixed|string
     */
    public function getAdmins(
        int $offset = 0,
        int $limit = 30,
        string $search = null,
        string $sort = 'created',
        string $order = 'desc'
    ) {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('admin')
            ->from(Admin::class, 'admin');

        if (!is_null($search)) {
            $qb->where($qb->expr()->like('admin.email', ':search'))
                ->setParameter('search', '%' . $search . '%');
        }

        $qb->setFirstResult($offset)
            ->setMaxResults($limit);
        $qb->orderBy('admin.' . $sort, $order);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param string|null $search
     * @return int|mixed|string
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function countAdmins(string $search = null)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('count(admin)')
            ->from(Admin::class, 'admin');

        if (!is_null($search)) {
            $qb->where($qb->expr()->like('admin.email', ':search'))
                ->setParameter('search', '%' . $search . '%');
        }

        return $qb->getQuery()->getSingleScalarResult();
    }
}