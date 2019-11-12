<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webkul\UVDesk\CoreFrameworkBundle\Providers;

use Symfony\Component\Ldap\Ldap;
use Symfony\Component\Ldap\Entry;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Ldap\LdapInterface;
use Webkul\UVDesk\CoreFrameworkBundle\Entity\User;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Bundle\SecurityBundle\Security\FirewallMap;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\FirewallMapInterface;
use Symfony\Component\Ldap\Exception\ConnectionException;
use Webkul\UVDesk\CoreFrameworkBundle\Entity\SupportRole;
use Webkul\UVDesk\CoreFrameworkBundle\Entity\UserInstance;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\Exception\InvalidArgumentException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;


/**
 * LdapUserProvider is a simple user provider on top of ldap.
 *
 * @author Grégoire Pineau <lyrixx@lyrixx.info>
 * @author Charles Sarrazin <charles@sarraz.in>
 */
class LdapUserProvider implements UserProviderInterface
{   
    private $firewall;
    private $container;
    private $entityManager;
    private $requestStack;
    private $ldap;
    private $baseDn;
    private $searchDn;
    private $searchPassword;
    private $usernameAttribute;
    private $defaultSearch;
    private $passwordAttribute;

    public function __construct(FirewallMap $firewall, ContainerInterface $container, EntityManagerInterface $entityManager, RequestStack $requestStack)
    {   
        $this->container = $container;
        $this->firewall = $firewall;
        $this->requestStack = $requestStack;
        $this->entityManager = $entityManager;

        $serverConfigs = [
            'host' => $this->container->getParameter('uvdesk.ldap.connection.host'),
            'port' => $this->container->getParameter('uvdesk.ldap.connection.port'),
            'encryption' => $this->container->getParameter('uvdesk.ldap.connection.encryption'),
            'options' => $this->container->getParameter('uvdesk.ldap.connection.options'),
        ];
        $this->ldap = Ldap::create('ext_ldap', $serverConfigs);
        
        $this->baseDn = $this->container->getParameter('uvdesk.ldap.base_dn');
        $this->searchDn = $this->container->getParameter('uvdesk.ldap.search_dn');
        $this->searchPassword = $this->container->getParameter('uvdesk.ldap.search_password');
        $this->usernameAttribute = $this->container->getParameter('uvdesk.ldap.username_attribute');
        $this->passwordAttribute = $this->container->getParameter('uvdesk.ldap.password_attribute');
        $this->defaultSearch = "({$this->usernameAttribute}={username})";
    }

    /**
     * {@inheritdoc}
     */
    public function loadUserByUsername($username)
    {   
        try {
            $this->ldap->bind($this->searchDn, $this->searchPassword);
            $username = $this->ldap->escape($username, '', LdapInterface::ESCAPE_FILTER);
            $query = str_replace('{username}', $username, $this->defaultSearch);
            $search = $this->ldap->query($this->baseDn, $query);
        } catch (ConnectionException $e) {
            throw new UsernameNotFoundException(sprintf('User "%s" not found.', $username), 0, $e);
        }
        $entries = $search->execute();
        $count = \count($entries);
        if (!$count) {
            throw new UsernameNotFoundException(sprintf('User "%s" not found.', $username));
        }

        if ($count > 1) {
            throw new UsernameNotFoundException('More than one user found');
        }
        $entry = $entries[0];

        try {
            if (null !== $this->usernameAttribute) {
                $username = $this->getAttributeValue($entry, $this->usernameAttribute);
            }
        } catch (InvalidArgumentException $e) {
        }

        return $this->loadUser($username, $entry);
    }

    /**
     * {@inheritdoc}
     */
    public function refreshUser(UserInterface $user)
    {
        if ($this->supportsClass(get_class($user))) {
            return $this->loadUserByUsername($user->getEmail());
        }

        throw new UnsupportedUserException('Invalid user type');
    }

    /**
     * {@inheritdoc}
     */
    public function supportsClass($class)
    {   
        try {
            $this->ldap->bind($this->searchDn, $this->searchPassword);
        } catch(ConnectionException $e) {
            // @TODO: Log errors to log file for debugging
            return false;
        }

        return User::class === $class;
    }

    /**
     * Fetches a required unique attribute value from an LDAP entry.
     *
     * @param Entry|null $entry
     * @param string     $attribute
     */
    protected function getAttributeValue(Entry $entry, $attribute)
    {
        if (!$entry->hasAttribute($attribute)) {
            throw new InvalidArgumentException(sprintf('Missing attribute "%s" for user "%s".', $attribute, $entry->getDn()));
        }

        $values = $entry->getAttribute($attribute);

        if (1 !== \count($values)) {
            throw new InvalidArgumentException(sprintf('Attribute "%s" has multiple values.', $attribute));
        }

        return $values[0];
    }

    /**
     * Loads a user from an LDAP entry.
     *
     * @param string $username
     *
     * @return User
     */
    protected function loadUser($username, Entry $entry)
    {
        if (null !== $this->passwordAttribute) {
            $password = $this->getAttributeValue($entry, $this->passwordAttribute);
        }
        if (empty($password)) {
            throw new UsernameNotFoundException("Partial details");
        }
        
        //Refreshing user from database
        $user = $this->loadUserByUsernameFromDatabase($username);
        if (!empty($user)) {

            return $user;
        }

        throw new UsernameNotFoundException(sprintf('Username "%s" does not exist.', $username));
    }

    protected function loadUserByUsernameFromDatabase($username)
    {
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('user, userInstance')
            ->from('UVDeskCoreFrameworkBundle:User', 'user')
            ->leftJoin('UVDeskCoreFrameworkBundle:UserInstance', 'userInstance', 'WITH', 'user.id = userInstance.user')
            ->leftJoin('userInstance.supportRole', 'supportRole')
            ->where('user.email = :email')->setParameter('email', trim($username))
            ->andWhere('userInstance.isActive = :isActive')->setParameter('isActive', true)
            ->setMaxResults(1);

        // Retrieve user instances based on active firewall
        $activeFirewall = $this->firewall->getFirewallConfig($this->requestStack->getCurrentRequest())->getName();

        switch (strtolower($activeFirewall)) {
            case 'member':
            case 'back_support':
                $queryBuilder
                    ->andWhere('supportRole.id = :roleOwner OR supportRole.id = :roleAdmin OR supportRole.id = :roleAgent')
                    ->setParameter('roleOwner', 1)
                    ->setParameter('roleAdmin', 2)
                    ->setParameter('roleAgent', 3);
                break;
            case 'customer':
            case 'front_support':
                $queryBuilder
                    ->andWhere('supportRole.id = :roleCustomer')
                    ->setParameter('roleCustomer', 4);
                break;
            default:
                return null;
                break;
        }
        
        $response = $queryBuilder->getQuery()->getResult();

        try {
            if (!empty($response) && is_array($response)) {
                list($user, $userInstance) = $response;

                // Set currently active instance
                $user->setCurrentInstance($userInstance);
                $user->setRoles((array) $userInstance->getSupportRole()->getCode());

                return $user;
            }
        } catch (\Exception $e) {
            // Do nothing...
        }

        return null;
    }
}