<?php

declare(strict_types=1);

namespace Frontend\User\Doctrine;

use Frontend\User\Entity\Admin;

/**
 * Class UserAuthentication
 * @package Frontend\Admin\Doctrine
 */
class UserAuthentication
{
    public static function verifyCredential(Admin $user, $inputPassword)
    {
        return password_verify($inputPassword, $user->getPassword());
    }
}
