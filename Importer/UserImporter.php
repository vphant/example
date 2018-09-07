<?php

namespace App\Service\Importer;

use App\Document\User;
use App\Service\MigrationApiClient;
use Doctrine\MongoDB\Collection;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class UserImporter extends AbstractImporter
{
    const TEST_PASSWORD = 'somepassword';

    /**
     * @var UserPasswordEncoderInterface
     */
    protected $passwordEncoder;

    /**
     * Generated test password
     * @var string
     */
    protected $password;

    /**
     * @param DocumentManager $dm
     * @param MigrationApiClient $migrationApiClient
     * @param UserPasswordEncoderInterface $passwordEncoder
     */
    public function __construct(
        DocumentManager $dm,
        MigrationApiClient $migrationApiClient,
        UserPasswordEncoderInterface $passwordEncoder
    ) {
        $this->passwordEncoder = $passwordEncoder;

        parent::__construct($dm, $migrationApiClient);
    }

    /**
     * @inheritdoc
     */
    protected function getUri(): string
    {
        return 'users/';
    }

    /**
     * @inheritdoc
     */
    protected function getName(): string
    {
        return 'user';
    }

    /**
     * @inheritdoc
     */
    protected function getCollection(): Collection
    {
        return $this->dm->getDocumentCollection('App\Document\User');
    }

    /**
     * @inheritdoc
     */
    protected function importItem(array $item): bool
    {
        $user = new User();
        $user
            ->setId($item['id'])
            ->setEmail($this->parseEmail($item))
            ->setFirstname($item['first_name'])
            ->setLastname($item['last_name'])
            ->setIsActive($item['is_active'])
            ->setIsStaff($item['is_staff'])
            ->setIsSuperuser($item['is_superuser'])
            ->setLastLogin($this->parseDate($item['last_login']))
            ->setResourceUri($item['resource_uri'])
            ->setActivationReminderLevel($item['activation_reminder_level'])
            ->setDateJoined($this->parseDate($item['date_joined']))
            ->setPassword($this->generatePassword());

        $this->dm->persist($user);
        unset($user);

        return true;
    }

    /**
     * @param array $object
     * @return string
     */
    protected function parseEmail(array $object)
    {
        if (!empty($object['email'])) {
            return $object['email'];
        }

        return sprintf('%s@example.org', $object['username']);
    }

    /**
     * @return string
     */
    protected function generatePassword()
    {
        if (null === $this->password) {
            $testUser = new User();
            $this->password = $this->passwordEncoder->encodePassword(
                $testUser,
                self::TEST_PASSWORD
            );
            unset($testUser);
        }

        return $this->password;
    }

}
