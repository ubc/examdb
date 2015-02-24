<?php

use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Config\Loader\LoaderInterface;
use Stof\DoctrineExtensionsBundle\StofDoctrineExtensionsBundle;

ini_set('date.timezone', 'America/Vancouver');

class AppKernel extends Kernel
{
    public function registerBundles()
    {
        $bundles = array(
            new Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new Symfony\Bundle\SecurityBundle\SecurityBundle(),
            new Symfony\Bundle\TwigBundle\TwigBundle(),
            new Symfony\Bundle\MonologBundle\MonologBundle(),
            new Symfony\Bundle\SwiftmailerBundle\SwiftmailerBundle(),
            new Symfony\Bundle\AsseticBundle\AsseticBundle(),
            new Doctrine\Bundle\DoctrineBundle\DoctrineBundle(),
            new Sensio\Bundle\FrameworkExtraBundle\SensioFrameworkExtraBundle(),
            new UBC\Exam\MainBundle\UBCExamMainBundle(),
            new Stof\DoctrineExtensionsBundle\StofDoctrineExtensionsBundle(),
//            new BeSimple\SsoAuthBundle\BeSimpleSsoAuthBundle(),
            new Gorg\Bundle\CasBundle\GorgCasBundle(),  //also need to add "gorg/cas-bundle": "master" to the required part of composer.json,
            new UBC\LtCommonsBundle\UBCLtCommonsBundle(),
            new Sensio\Bundle\BuzzBundle\SensioBuzzBundle(),
            new Lexik\Bundle\FormFilterBundle\LexikFormFilterBundle(),
            new JordiLlonch\Bundle\CrudGeneratorBundle\JordiLlonchCrudGeneratorBundle(),
        );

        if (in_array($this->getEnvironment(), array('dev', 'test'))) {
            $bundles[] = new Symfony\Bundle\WebProfilerBundle\WebProfilerBundle();
            $bundles[] = new Sensio\Bundle\DistributionBundle\SensioDistributionBundle();
            $bundles[] = new Sensio\Bundle\GeneratorBundle\SensioGeneratorBundle();
            $bundles[] = new h4cc\AliceFixturesBundle\h4ccAliceFixturesBundle();
        }
        if (in_array($this->getEnvironment(), array('test'))) {
            $bundles[] = new Liip\FunctionalTestBundle\LiipFunctionalTestBundle();
        }

        return $bundles;
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load(__DIR__.'/config/config_'.$this->getEnvironment().'.yml');
    }

    public function getLogDir()
    {
        if (false !== getEnv('OPENSHIFT_LOG_DIR')) {
            return getEnv('OPENSHIFT_LOG_DIR');
        } else {
            return parent::getLogDir();
        }
    }
}
