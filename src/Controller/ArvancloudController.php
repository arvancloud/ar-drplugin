<?php


namespace Drupal\ar_drplugin\Controller;


use Drupal\ar_drplugin\ArvanCloudPurge;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Site\Settings;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

class ArvancloudController extends ControllerBase
{
    /**
     * Store the settings var.
     *
     *
     */
    private $arPurgeSettings = [];

    /**
     * Get the config.
     *
     * @var \Drupal\Core\Config\ConfigFactoryInterface
     */
    protected $configFactory;

    /**
     * The logger.
     *
     * @var \Drupal\Core\Logger\LoggerChannelInterface
     */
    protected $logger;

    /**
     * Get the config and logger.
     *
     * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
     *   Config Factory.
     * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
     *   Drupal Logger.
     */
    public function __construct(ConfigFactoryInterface $config_factory, LoggerChannelInterface $logger) {
        $this->configFactory = $config_factory;
        $this->logger = $logger;
        $this->getCredentials();
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container) {
        return new static(
            $container->get('config.factory'),
            $container->get('arvancloud.logger.channel.arvancloud')
        );
    }

    /**
     * Stay on the same page.
     */
    public function getCurrentUrl() {
        $request = \Drupal::request();
        return $request->server->get('HTTP_REFERER');
    }

    /**
     * Get the credentials.
     */
    public function getCredentials() {
        // Get credentials from settings.
        $arvanCloudCredentials = Settings::get('arvancloud_credentials');
        // Store credentials.
        if (!empty($arvanCloudCredentials)) {
            $this->arPurgeSettings = $arvanCloudCredentials;
            return $arvanCloudCredentials;
        }
        return NULL;
    }

    /**
     * Purge ArvanCloud cache.
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     *   Redirect back to the previous url.
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function purgeAll(): RedirectResponse {
        $domain = $this->getValueFromSettingsOrConfig('domain');
        $apiToken = $this->getValueFromSettingsOrConfig('api_token');

        if ($domain != NULL && $apiToken != NULL) {
            $results = ArvanCloudPurge::arPurgeCache($domain, $apiToken);
            if ($results == 200) {
                $this->messenger()->addMessage($this->t('ArvanCloud was purged successfully.'));
            }
            else {
                $this->messenger()->addError($this->t('An error happened while clearing ArvanCloud, check drupal log for more info.'));
            }
        }
        else {
            $this->messenger()->addError($this->t('Please insert ArvanCloud credentials.'));
        }

        return new RedirectResponse($this->getCurrentUrl());

    }

    /**
     * Check if config variable is overridden by the settings.php.
     *
     * @param string $name
     *   Check the value.
     *
     * @return array|bool|mixed|null
     *   Return the value either from settings or config.
     */
    protected function getValueFromSettingsOrConfig(string $name) {
        $valueFromSettings = $this->getCredentials();
        $valueFromConfig   = $this->configFactory->get('arvancloud_form.settings');
        return $valueFromSettings[$name] ?: $valueFromConfig->get($name);
    }

}