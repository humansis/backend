<?php

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Config\Loader\LoaderInterface;

class AppKernel extends Kernel
{

    use MicroKernelTrait;

    const CONFIG_EXTS = '.{php,xml,yaml,yml}';


    public function getRootDir()
    {
        return __DIR__;
    }

    public function getCacheDir()
    {
        return dirname(__DIR__).'/var/cache/'.$this->getEnvironment();
    }

    public function getLogDir()
    {
        return dirname(__DIR__).'/var/logs';
    }

    public function registerBundles()
    {
        $bundles = [
            new Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new Symfony\Bundle\SecurityBundle\SecurityBundle(),
            new Symfony\Bundle\TwigBundle\TwigBundle(),
            new Symfony\Bundle\MonologBundle\MonologBundle(),
            new Symfony\Bundle\SwiftmailerBundle\SwiftmailerBundle(),
            new Doctrine\Bundle\DoctrineBundle\DoctrineBundle(),
            new Sensio\Bundle\FrameworkExtraBundle\SensioFrameworkExtraBundle(),
            new Doctrine\Bundle\MigrationsBundle\DoctrineMigrationsBundle(),
            new FOS\UserBundle\FOSUserBundle(),
            new FOS\RestBundle\FOSRestBundle(),
            new Nelmio\ApiDocBundle\NelmioApiDocBundle(),
            new Nelmio\CorsBundle\NelmioCorsBundle(),
            new UserBundle\UserBundle(),
            new ProjectBundle\ProjectBundle(),
            new BeneficiaryBundle\BeneficiaryBundle(),
            new DistributionBundle\DistributionBundle(),
            new TransactionBundle\TransactionBundle(),
            new RA\RequestValidatorBundle\RARequestValidatorBundle(),
            new CommonBundle\CommonBundle(),
            new ReportingBundle\ReportingBundle(),
            new Jrk\LevenshteinBundle\JrkLevenshteinBundle(),
            new VoucherBundle\VoucherBundle(),
            new Knp\Bundle\GaufretteBundle\KnpGaufretteBundle(),
            new NewApiBundle\NewApiBundle(),
            new Lexik\Bundle\JWTAuthenticationBundle\LexikJWTAuthenticationBundle(),
            new DH\AuditorBundle\DHAuditorBundle(),
        ];


        if (in_array($this->getEnvironment(), ['local', 'dev', 'test'], true)) {
            $bundles[] = new Symfony\Bundle\DebugBundle\DebugBundle();
            $bundles[] = new Symfony\Bundle\WebProfilerBundle\WebProfilerBundle();
            $bundles[] = new Doctrine\Bundle\FixturesBundle\DoctrineFixturesBundle();

            if ('dev' === $this->getEnvironment()) {
                $bundles[] = new Symfony\Bundle\WebServerBundle\WebServerBundle();
            }
        }

        return $bundles;
    }


    protected function configureContainer(ContainerBuilder $containerBuilder, LoaderInterface $loader)
    {

        $confDir = $this->getProjectDir().'/app/config';
        $loader->load($confDir.'/config'.self::CONFIG_EXTS, 'glob');
        $loader->load($confDir.'/packages/*'.self::CONFIG_EXTS, 'glob');
        if (is_dir($confDir.'/packages/'.$this->environment)) {
            $loader->load($confDir.'/packages/'.$this->environment.'/**/*'.self::CONFIG_EXTS, 'glob');
        }
        $loader->load($confDir.'/services'.self::CONFIG_EXTS, 'glob');
        $loader->load($confDir.'/services_'.$this->environment.self::CONFIG_EXTS, 'glob');
    }




    protected function configureRoutes(\Symfony\Component\Routing\RouteCollectionBuilder $routes)
    {
        $routes->import($this->getRootDir().'/config/routing.yml');
        $environmentRoutingConfig = $this->getRootDir().'/config/routing/'.$this->getEnvironment().'/routing.yml';
        if (is_file($environmentRoutingConfig)) {
            $routes->import($environmentRoutingConfig);
        }
    }
}
