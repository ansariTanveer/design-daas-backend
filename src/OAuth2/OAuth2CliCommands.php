<?php

namespace Application\Core\OAuth2;

use Application\Common\SymfonyConsole\ConsoleOutput;
use Application\Core\Util\OAuth2\Repository\ClientRepository;
use Application\Core\Util\OAuth2\Model\PersistedClient;
use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
use Defuse\Crypto\Key;
use DI\Annotation\Inject;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Console\Output\OutputInterface;

final class OAuth2CliCommands
{
    /** @Inject( "config.oauth2.private_key_file" ) */
    private ?string $privateKeyFile;

    /** @Inject( "config.oauth2.public_key_file" ) */
    private ?string $publicKeyFile;

    /** @Inject( "config.oauth2.encryption_key_file" ) */
    private ?string $encryptionKeyFile;

    /** @Inject() */
    private ClientRepository $clientRepository;

    /** @Inject() */
    private EntityManagerInterface $em;

    /**
     * Generate keys required by the OAuth2 server
     *
     * @command oauth2:genkeys
     * @param OutputInterface $output
     * @return int
     * @throws EnvironmentIsBrokenException
     */
    public function generateKeysAction(OutputInterface $output): int
    {
        $output = ConsoleOutput::wrapOutput($output);

        if ($this->privateKeyFile === null || $this->publicKeyFile === null || $this->encryptionKeyFile === null) {
            $output->stdErr()->writeln(
                'Key files are not set correctly, will not continue'
            );
            return 1;
        }

        assert(is_string($this->privateKeyFile));
        assert(is_string($this->publicKeyFile));
        if (file_exists($this->privateKeyFile) || file_exists($this->publicKeyFile)) {
            $output->stdErr()->writeln(
                'Key files already exist, will not generate new keys'
            );
        } else {
            $this->generateKeyFiles(
                $output,
                $this->privateKeyFile,
                $this->publicKeyFile
            );
        }

        assert(is_string($this->encryptionKeyFile));
        if (file_exists($this->encryptionKeyFile)) {
            $output->stdErr()->writeln(
                'Encryption key already exists, will not generate new one'
            );
        } else {
            $encryptionKey = Key::createNewRandomKey()->saveToAsciiSafeString();
            file_put_contents($this->encryptionKeyFile, $encryptionKey);
            $output->writeln(
                'IMPORTANT: Created encryption key with default permissions, ' .
                'you may want to review/fix them to be protect the key file!'
            );
        }

        return 0;
    }

    private function generateKeyFiles(
        OutputInterface $output,
        string $privateKeyFile,
        string $publicKeyFile
    ): void {
        /** @var string $rsaKey */
        $rsaKey = openssl_pkey_new(
            [
                'private_key_bits' => 2048,
                'private_key_type' => OPENSSL_KEYTYPE_RSA,
            ]
        );
        $privateKeyResource = openssl_pkey_get_private($rsaKey);
        assert($privateKeyResource !== false);

        openssl_pkey_export($privateKeyResource, $privateKey);

        $details = openssl_pkey_get_details($privateKeyResource);
        assert(is_array($details) && array_key_exists('key', $details));
        $publicKey = $details['key'];

        file_put_contents($privateKeyFile, $privateKey);

        file_put_contents($publicKeyFile, $publicKey);

        $output->writeln(
            'IMPORTANT: Created key files with default permissions, ' .
            'you may want to review/fix them to be protect the ' .
            'private key file!'
        );
    }

    /**
     * List existing OAuth2 clients
     *
     * @command oauth2:list-clients
     * @param OutputInterface $output
     * @return int
     */
    public function listClientsAction(OutputInterface $output): int
    {
        $clients = $this->clientRepository->findAll();
        foreach ($clients as $client) {
            $this->outputClient($output, $client);
        }
        return 0;
    }

    /**
     * Add an OAuth2 client
     *
     * @command oauth2:add-client
     * @param OutputInterface $output
     * @param string $name
     * @param bool $confidential
     * @param bool $withSecret
     * @return int
     * @throws Exception
     */
    public function addClientAction(OutputInterface $output, string $name, bool $confidential, bool $withSecret): int
    {
        $client = new PersistedClient(
            bin2hex(random_bytes(20)), // 20 bytes = 40 hex characters
            $name,
            [],
            $confidential,
            $withSecret ?
                bin2hex(random_bytes(40)) : // 40 bytes = 80 hex characters
                null
        );
        $this->em->persist($client);
        $this->em->flush();

        $this->outputClient($output, $client);

        return 0;
    }

    /**
     * Delete an OAuth2 client
     *
     * @command oauth2:delete-client
     * @param OutputInterface $output
     * @param string $identifier
     * @return int
     */
    public function deleteClientAction(OutputInterface $output, string $identifier): int
    {
        $output = ConsoleOutput::wrapOutput($output);
        $client = $this->clientRepository->find($identifier);

        if ($client === null) {
            $output->stdErr()->writeln('Client not found');
            return 1;
        }

        $this->outputClient($output, $client);

        $this->em->remove($client);
        $this->em->flush();

        $output->writeln('Client deleted');
        return 0;
    }

    private function outputClient(OutputInterface $output, PersistedClient $client): void
    {
        $output->writeln(sprintf('Name: %1$s', $client->name()));
        $output->writeln(sprintf('Identifier: %1$s', $client->identifier()));
        $output->writeln(sprintf('Secret: %1$s', $client->secret()));
        $output->writeln(sprintf('Confidential: %1$d', $client->isConfidential()));
        $output->writeln(sprintf('Redirect URIs: %1$s', json_encode($client->redirectUris())));
        $output->writeln('');
    }
}
