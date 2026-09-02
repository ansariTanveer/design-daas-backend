<?php

namespace Application\Core\User;

use Application\Core\User\Exception\UserServiceException;
use Application\Core\User\Service\UserService;
use Symfony\Component\Console\Output\OutputInterface;
use DI\Annotation\Inject;

class AdminCliCommands
{
    /** @Inject() */
    private UserService $userService;

    /**
     * Create an admin
     *
     * @command admin:create
     * @param OutputInterface $output
     * @param string $username The username of the admin
     * @param string $email The email address of the admin
     * @param string $password The password of the admin
     * @return int
     */
    public function adminCreateCommand(
        OutputInterface $output,
        string $username,
        string $email,
        string $password
    ): int {
        try {
            $this->userService->registerAdmin($username, $email, $password);
            return 0;
        } catch (UserServiceException $e) {
            $output->writeln($e->getMessage());
            return 1;
        }
    }
}
