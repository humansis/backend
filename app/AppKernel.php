<?php

use DependencyInjection\Compiler\MapperCompilerPass;
use DH\AuditorBundle\DHAuditorBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Config\Loader\LoaderInterface;

class AppKernel extends Kernel
{
    use MicroKernelTrait;

    final public const CONFIG_EXTS = '.{php,xml,yaml,yml}';

    public function getRootDir(): string
    {
        return __DIR__;
    }

    public function getCacheDir(): string
    {
        return dirname(__DIR__) . '/var/cache/' . $this->getEnvironment();
    }

    public function getLogDir(): string
    {
        return dirname(__DIR__) . '/var/logs';
    }

    public function registerBundles(): iterable
    {
        $bundles = [
            new Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new Symfony\Bundle\SecurityBundle\SecurityBundle(),
            new Symfony\Bundle\TwigBundle\TwigBundle(),
            new Symfony\Bundle\MonologBundle\MonologBundle(),
            new Doctrine\Bundle\DoctrineBundle\DoctrineBundle(),
            new Sensio\Bundle\FrameworkExtraBundle\SensioFrameworkExtraBundle(),
            new Doctrine\Bundle\MigrationsBundle\DoctrineMigrationsBundle(),
            new FOS\RestBundle\FOSRestBundle(),
            new Knp\Bundle\GaufretteBundle\KnpGaufretteBundle(),
            new Lexik\Bundle\JWTAuthenticationBundle\LexikJWTAuthenticationBundle(),
            new DHAuditorBundle(),
        ];

        if (in_array($this->getEnvironment(), ['local', 'dev', 'test'], true)) {
            $bundles[] = new Symfony\Bundle\DebugBundle\DebugBundle();
            $bundles[] = new Symfony\Bundle\WebProfilerBundle\WebProfilerBundle();
            $bundles[] = new Doctrine\Bundle\FixturesBundle\DoctrineFixturesBundle();
        }

        return $bundles;
    }

    protected function configureContainer(ContainerBuilder $containerBuilder, LoaderInterface $loader)
    {
        $confDir = $this->getProjectDir() . '/app/config';
        $loader->load($confDir . '/config' . self::CONFIG_EXTS, 'glob');
        $loader->load($confDir . '/dh_auditor' . self::CONFIG_EXTS, 'glob');
        $loader->load($confDir . '/packages/*' . self::CONFIG_EXTS, 'glob');
        if (is_dir($confDir . '/packages/' . $this->environment)) {
            $loader->load($confDir . '/packages/' . $this->environment . '/**/*' . self::CONFIG_EXTS, 'glob');
        }
        $loader->load($confDir . '/services' . self::CONFIG_EXTS, 'glob');
        $loader->load($confDir . '/services_' . $this->environment . self::CONFIG_EXTS, 'glob');

        $containerBuilder->addCompilerPass(new MapperCompilerPass());
    }

    protected function configureRoutes(\Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator $routes)
    {
        $routes->import($this->getRootDir() . '/config/routing.yml');
        $environmentRoutingConfig = $this->getRootDir() . '/config/routing/' . $this->getEnvironment() . '/routing.yml';
        if (is_file($environmentRoutingConfig)) {
            $routes->import($environmentRoutingConfig);
        }
    }
}
