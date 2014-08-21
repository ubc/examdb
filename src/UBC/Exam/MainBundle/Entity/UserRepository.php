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

class UserRepository extends EntityRepository implements UserProviderInterface//, UserFactoryInterface
{
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
             * instead of throwing exception, why not create a user!  CHEATING!!!!!
             * What SHOULD happen is:
             * 1)in security.yml, security.firewalls.ubc_secured_area.trusted_sso.create_users: true
             * 2)uncomment ln 10/14 in this file
             * 3) 
             * 
             */
            $user = $this->createUser($username, array(), array());
            return $user;
//             $message = sprintf(
//                 'Unable to find an active admin UBCExamMainBundle:User object identified by "%s".',
//                 $username
//             );

//             throw new UsernameNotFoundException($message, 0, $e);
        }

        return $user;
    }

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

    public function supportsClass($class)
    {
        return $this->getEntityName() === $class
            || is_subclass_of($class, $this->getEntityName());
    }
    
    /**
     * @inheritDoc
     */
    public function createUser($username, array $roles, array $attributes) {
    	$new_user = new User();
    	$new_user->setUsername($username);
    	$em = $this->getEntityManager();
    	$em->persist($new_user);
    	$em->flush();

    	return $new_user;
    }
}