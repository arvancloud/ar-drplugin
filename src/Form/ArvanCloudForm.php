<?php


namespace Drupal\ar_drplugin\Form;


use Drupal\ar_drplugin\ArvanCloudPurge;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Site\Settings;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ArvanCloudForm extends ConfigFormBase
{

    /**
     * Config settings.
     *
     * @var string
     */
    const SETTINGS = 'arvancloud_form.settings';
    /**
     * Log activity when user enter credentials.
     *
     * @var \Drupal\Core\Logger\LoggerChannelInterface
     */
    protected $logger;

    /**
     * ArvanCloud purge constructor.
     *
     * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
     *   The factory for configuration objects.
     * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
     *   The logger.
     */
    public function __construct(ConfigFactoryInterface $config_factory, LoggerChannelInterface $logger) {
        parent::__construct($config_factory);
        $this->logger = $logger;
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
     * {@inheritdoc}
     */
    protected function getEditableConfigNames(): array {
        return [
            static::SETTINGS,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getFormId():string {
        return 'arvancloud_form';
    }

    /**
     * Build the form.
     *
     * @param array $form
     *   Form array.
     * @param \Drupal\Core\Form\FormStateInterface $form_state
     *   Form State interface.
     *
     * @return array
     *   Return array.
     */
    public function buildForm(array $form, FormStateInterface $form_state):array {
        $config = $this->config(static::SETTINGS);

        $form['arvancloud_form']['domain'] = [
             '#type' => 'textfield',
             '#title' => t('Domain'),
             '#size' => 60,
             '#required' => TRUE,
             '#default_value' => $this->isOverridden('domain') ?: $config->get('domain')??$_SERVER['HTTP_HOST'],
             '#disabled' => $this->isOverridden('domain'),
             '#attributes' => [
                 'placeholder' => [
                     'Domain',
                 ],
             ],
             '#description' => t('Enter Site Domain.'),
         ];

        $form['arvancloud_form']['api_token'] = [
            '#type' => 'textfield',
            '#title' => t('API Token'),
            '#size' => 60,
            '#required' => TRUE,
            '#default_value' => $this->isOverridden('api_token') ?: $config->get('api_token'),
            '#disabled' => $this->isOverridden('api_token'),
            '#attributes' => [
                'placeholder' => [
                    'API Token',
                ],
            ],
            '#description' => t('Enter ArvanCloud User API Token.'),
        ];
        $form['arvancloud_form']['status'] = array(
            '#title' => t('Cache Status'),
            '#type' => 'select',
            '#required' => TRUE,
            '#default_value' => $this->isOverridden('status') ?: $config->get('status'),
            '#description' => 'Select Cache Status.',
            '#options' => array('off'=>'off', 'uri'=>'uri', 'query_string'=>'query_string', 'advance'=>'advance'),
        );
        return parent::buildForm($form, $form_state);

    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state): void {
        // Save all configs on submit.
        $this->config(self::SETTINGS)
            ->set('api_token', $form_state->getValue('api_token'))
            ->set('domain', $form_state->getValue('domain'))
            ->set('status', $form_state->getValue('status'))
            ->save();
        if ($res=ArvanCloudPurge::setStatus($form_state->getValue('domain'),$form_state->getValue('api_token'),$form_state->getValue('status'))!=200){
            $this->messenger()->addError($this->t('API Key is Invalid!.'.$res));
        }
        parent::submitForm($form, $form_state);

    }

    /**
     * Check if config variable is overridden by the settings.php.
     *
     * @param string $name
     *   Check for the field value.
     *
     * @return mixed
     *   Return the value
     */
    protected function isOverridden(string $name) {
        $arvanCloudCredentials = Settings::get('arvancloud_credentials');
        if (!empty($arvanCloudCredentials[$name])) {
            return $arvanCloudCredentials[$name];
        }
        return FALSE;
    }

}