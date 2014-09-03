<?php 
namespace UBC\Exam\MainBundle\Entity;

use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NoResultException;
// use BeSimple\SsoAuthBundle\Security\Core\User\UserFactoryInterface;
use UBC\Exam\MainBundle\Entity\User;
use BeSimple\SsoAuthBundle\Security\Core\User\UserFactoryInterface;

/**
 * This is the class that creates the user if one is not found by cas
 * 
 * @author loongchan
 *
 */
class UserRepository extends EntityRepository implements UserProviderInterface//, UserFactoryInterface
{
    /**
     * This function is called when user logs into cas.  checks if user exists, if not then creates one (non-PHPdoc)
     * 
     * @see \Symfony\Component\Security\Core\User\UserProviderInterface::loadUserByUsername()
     * 
     * @return \Entities\User
     */
    public function loadUserByUsername($username)
    {
        $q = $this
            ->createQueryBuilder('u')
            ->where('u.username = :username')
            ->setParameter('username', $username)
            ->getQuery();

        try {
            // The Query::getSingleResult() method throws an exception
            // if there is no record matching the criteria.
            $user = $q->getSingleResult();
        } catch (NoResultException $e) {
            /*
             * instead of throwing exception, why not create a user!  WORK-AROUND!!!!!
             * What SHOULD happen is:
             * 1)in security.yml, security.firewalls.ubc_secured_area.trusted_sso.create_users: true
             * 2)uncomment ln 10/14 in this file
             * 3) 
             * 
             */
            $user = $this->createUser($username, array(), array());
            return $user;
        }

        return $user;
    }

    /**
     * I think it's called when refreshing user session.(non-PHPdoc)
     * @see \Symfony\Component\Security\Core\User\UserProviderInterface::refreshUser()
     * 
     * @return \Entities\User
     */
    public function refreshUser(UserInterface $user)
    {
        $class = get_class($user);
        if (!$this->supportsClass($class)) {
            throw new UnsupportedUserException(
                sprintf(
                    'Instances of "%s" are not supported.',
                    $class
                )
            );
        }

        return $this->loadUserByUsername($user->getUsername());
    }

    /**
     * (non-PHPdoc)
     * 
     * @see \Symfony\Component\Security\Core\User\UserProviderInterface::supportsClass()
     * 
     * @param String
     * 
     * @return boolean
     */
    public function supportsClass($class)
    {
        return $this->getEntityName() === $class
            || is_subclass_of($class, $this->getEntityName());
    }
    
    /**
     * function that can be called to create a new user.
     * 
     * @inheritDoc
     * 
     * @return \Entities\User
     */
    public function createUser($username, array $roles, array $attributes)
    {
        $new_user = new User();
        $new_user->setUsername($username);
        $em = $this->getEntityManager();
        $em->persist($new_user);
        $em->flush();

        return $new_user;
    }
}