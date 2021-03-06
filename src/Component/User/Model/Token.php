<?php declare(strict_types=1);

namespace App\Component\User\Model;

use App\Component\Dto\UserTransfer;
use App\Component\User\Persistence\UserEntityManagerInterface;

final class Token implements TokenInterface
{
    private const LENGTH = 32;

    public function __construct(
        private readonly UserEntityManagerInterface $userEntityManager
    )
    {
    }

    public function save(string $userMail): UserTransfer
    {
        if (filter_var($userMail, FILTER_VALIDATE_EMAIL) === false) {
            throw new \RuntimeException(
                sprintf('Email "%s" is not valid!', $userMail)
            );
        }

        $userTransfer = new UserTransfer();

        $userTransfer->token = bin2hex(random_bytes(self::LENGTH));
        $userTransfer->email= $userMail;


        $userTransfer =  $this->userEntityManager->saveUser($userTransfer);

        if($userTransfer->id > 0) {
            return $userTransfer;
        }

        throw new \RuntimeException('Error on save user');
    }
}
