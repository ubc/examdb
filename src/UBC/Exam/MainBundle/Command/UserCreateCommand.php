<?php


namespace UBC\Exam\MainBundle\Command;


use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use UBC\Exam\MainBundle\Entity\User;

class UserCreateCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('exam:user:create')
            ->setDescription('Create a user in the system')
            ->addArgument(
                'username',
                InputArgument::REQUIRED,
                'Username for the new user'
            )
            ->addArgument(
                'password',
                InputArgument::OPTIONAL,
                'Password for the new user'
            )
            ->addArgument(
                'role',
                InputArgument::OPTIONAL,
                'Role for the new user',
                'ROLE_USER'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $createOrUpdated = 'updated';
        $username = $input->getArgument('username');
        $password = $input->getArgument('password') ?
            $input->getArgument('password') :
            self::randomPassword();

        // check if user exists
        $repo = $this->getContainer()->get('doctrine')->getRepository('UBCExamMainBundle:User');
        $user = $repo->findOneByUsername($username);
        if (!$user) {
            $user = new User();
            $createOrUpdated = 'created';
        }

        $user->setUsername($username);
        $user->setPassword($password);
        $user->setRoleString($input->getArgument('role'));

        $em = $this->getContainer()->get('doctrine')->getManager();
        $em->persist($user);
        $em->flush();

        $passwordMsg = $input->getArgument('password') ?
            '' :
            " with password '$password'";
        $output->writeln("User $username has been $createOrUpdated successfully$passwordMsg.");
    }

    private static function randomPassword() {
        $alphabet = "abcdefghijklmnopqrstuwxyzABCDEFGHIJKLMNOPQRSTUWXYZ0123456789";
        $pass = array(); //remember to declare $pass as an array
        $alphaLength = strlen($alphabet) - 1; //put the length -1 in cache
        for ($i = 0; $i < 8; $i++) {
            $n = rand(0, $alphaLength);
            $pass[] = $alphabet[$n];
        }
        return implode($pass); //turn the array into a string
    }
}