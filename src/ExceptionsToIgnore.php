<?php

namespace Drupal\error_mail;

/**
 * ExceptionsToIgnore enum.
 */
enum ExceptionsToIgnore: string {
  case HttpException = 'Symfony\Component\HttpKernel\Exception\HttpException';

  /**
   * @param string $class
   * @return bool
   */
  public static function ignore(string $class): bool {
    foreach (ExceptionsToIgnore::cases() as $case) {
      if ($case->value === $class) {
        return TRUE;
      }
    }
    return FALSE;
  }

}
