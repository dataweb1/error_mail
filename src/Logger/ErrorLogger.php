<?php

namespace Drupal\error_mail\Logger;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\Core\Render\Renderer;
use Psr\Log\LoggerInterface;

/**
 *
 */
class ErrorLogger implements LoggerInterface {

  /**
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected LoggerChannelFactoryInterface $loggerFactory;

  /**
   * @var \Drupal\Core\Render\Renderer
   */
  private Renderer $renderer;

  /**
   * @var mixed
   */
  private mixed $mailTo;

  /**
   * @var array
   */
  private array $levels;

  /**
   *
   */
  public function __construct(ConfigFactoryInterface $config_factory, Renderer $renderer) {
    $this->renderer = $renderer;

    $config = $config_factory->get('error_mail.settings');
    $this->mailTo = $config->get('mail_to');

    $this->levels = [
      RfcLogLevel::EMERGENCY,
      RfcLogLevel::ALERT,
      RfcLogLevel::CRITICAL,
      RfcLogLevel::ERROR,
    ];
  }

  /**
   * @throws \Exception
   */
  public function log($level, $message, array $context = []): void {
    // Check if the log level is 'emergency', 'alert', 'critical', 'error'.
    if (in_array($level, $this->levels)) {
      // Handle the error email.
      $this->sendErrorMail($context);
    }
  }

  /**
   * @param $error_context
   *
   * @return void
   * @throws \Exception
   */
  protected function sendErrorMail(array $error_context): void {
    // No mail address, no error mail.
    if ($this->mailTo == '') {
      return;
    }

    // Set mail properties.
    $module = 'error_mail';
    $key = 'error_mail_' . time();
    $to = $this->mailTo;
    $body = $this->getMailBody($error_context);

    $params = [
      'from_name' => 'Error Notification Center',
      'from_mail' => $this->mailTo,
      'body' => $body,
      'subject' => 'Error Report',
      'theme' => 'error_mail',
      'headers' => [
        'Content-Type' => 'text/html; charset=UTF-8; format=flowed; delsp=yes',
      ],
    ];

    $langcode = \Drupal::currentUser()->getPreferredLangcode();

    // Call service this way instead of via dependency injection which can you
    // circular dependancy injections like e.g "plugin.manager.mail ->
    // logger.factory -> error_mail.error_logger".
    \Drupal::service('plugin.manager.mail')->mail($module, $key, $to, $langcode, $params);
  }

  /**
   * @param array $error_context
   *
   * @return string
   * @throws \Exception
   */
  public function getMailBody(array $error_context): string {
    $error_context['@backtrace_string'] = $this->getExceptionTraceAsString($error_context['backtrace']);
    $formattableMarkup = new FormattableMarkup('%function: @message (line %line of %file) <pre>@backtrace_string</pre>', $error_context);
    $body = $formattableMarkup->__toString();

    $currentRequest = \Drupal::requestStack()->getCurrentRequest();
    $settings = [
      '#theme' => 'item_list',
      '#list_type' => 'ul',
      '#items' => [
        'URI: ' . $currentRequest->getUri(),
        'Referer: ' . $currentRequest->server->get('HTTP_REFERER'),
        'User: ' . \Drupal::currentUser()->getAccountName() . ' (' . \Drupal::currentUser()->getEmail() . ')',
      ],
    ];

    $body .= $this->renderer->render($settings);

    return $body;
  }

  /**
   * @param array $backtrace
   *
   * @return string
   */
  public function getExceptionTraceAsString(array $backtrace): string {
    $rtn = "";
    $count = 0;
    foreach ($backtrace as $frame) {

      $args = "";
      if (isset($frame['args'])) {
        $args = [];
        foreach ($frame['args'] as $arg) {
          if (is_string($arg)) {
            $args[] = "'" . $arg . "'";
          }
          elseif (is_array($arg)) {
            $args[] = "Array";
          }
          elseif (is_null($arg)) {
            $args[] = 'NULL';
          }
          elseif (is_bool($arg)) {
            $args[] = ($arg) ? "true" : "false";
          }
          elseif (is_object($arg)) {
            $args[] = get_class($arg);
          }
          elseif (is_resource($arg)) {
            $args[] = get_resource_type($arg);
          }
          else {
            $args[] = $arg;
          }
        }
        $args = implode(', ', $args);
      }
      $current_file = "[internal function]";
      if (isset($frame['file'])) {
        $current_file = $frame['file'];
      }
      $current_line = "";
      if (isset($frame['line'])) {
        $current_line = $frame['line'];
      }
      $rtn .= sprintf("#%s %s(%s): %s(%s)\n",
        $count,
        $current_file,
        $current_line,
        $frame['function'] ?? '',
        $args);
      $count++;
    }
    return $rtn;
  }

  /**
   * {@inheritDoc}
   */
  public function get($channel) {
    // @todo Implement get() method.
  }

  /**
   * {@inheritDoc}
   */
  public function addLogger(LoggerInterface $logger, $priority = 0) {
    // @todo Implement addLogger() method.
  }

  /**
   * {@inheritDoc}
   */
  public function emergency($message, array $context = []): void {
    // @todo Implement emergency() method.
  }

  /**
   * {@inheritDoc}
   */
  public function alert($message, array $context = []): void {
    // @todo Implement alert() method.
  }

  /**
   * {@inheritDoc}
   */
  public function critical($message, array $context = []): void {
    // @todo Implement critical() method.
  }

  /**
   * {@inheritDoc}
   */
  public function error($message, array $context = []): void {
    // @todo Implement error() method.
  }

  /**
   * {@inheritDoc}
   */
  public function warning($message, array $context = []): void {
    // @todo Implement warning() method.
  }

  /**
   * {@inheritDoc}
   */
  public function notice($message, array $context = []): void {
    // @todo Implement notice() method.
  }

  /**
   * {@inheritDoc}
   */
  public function info($message, array $context = []): void {
    // @todo Implement info() method.
  }

  /**
   * {@inheritDoc}
   */
  public function debug($message, array $context = []): void {
    // @todo Implement debug() method.
  }

}
