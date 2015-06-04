<?php 
namespace UBC\Exam\MainBundle\Entity;

use Gorg\Bundle\CasBundle\Security\Firewall\CasUserProviderInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NoResultException;

/**
 * This is the class that creates the user if one is not found by cas
 * 
 * @author Loong Chan <loong.chan@ubc.ca>
 *
 */
class UserRepository extends EntityRepository implements CasUserProviderInterface
{
    /**
     * This function is called when user logs into cas.  checks if user exists, if not then creates one (non-PHPdoc)
     *
     * @param String $username
     *
     * @see \Symfony\Component\Security\Core\User\UserProviderInterface::loadUserByUsername()
     *
     * @return mixed|UserInterface
     */
    public function loadUserByUsername($username)
    {
        $q = $this
            ->createQueryBuilder('u')
            ->where('u.username = :username')
            ->setParameter('username', $username)
            ->getQuery();
            //->useResultCache(true, 3600);

        try {
            // The Query::getSingleResult() method throws an exception
            // if there is no record matching the criteria.
            $user = $q->getSingleResult();
        } catch (NoResultException $e) {
            $ex = new UsernameNotFoundException();
            $ex->setUsername($username);
            throw $ex;
        }

        return $user;
    }

    /**
     * I think it's called when refreshing user session.(non-PHPdoc)
     *
     * @param UserInterface $user
     *
     * @see \Symfony\Component\Security\Core\User\UserProviderInterface::refreshUser()
     *
     * @return mixed|UserInterface
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
     * @param String $class
     * 
     * @see \Symfony\Component\Security\Core\User\UserProviderInterface::supportsClass()
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
     * @param TokenInterface $token
     *
     * @inheritDoc
     *
     * @return mixed|UserInterface
     */
    public function createUser(TokenInterface $token)
    {
        $newUser = new User();
        $newUser->setUsername($token->getUsername());
        $newUser->setRoles($token->getRoles());
        $em = $this->getEntityManager();
        $em->persist($newUser);
        $em->flush();

        return $newUser;
    }
}