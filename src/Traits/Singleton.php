<?php

namespace MMM\Traits;

use Exception;

trait Singleton
{
  private static ?self $instance = null;

  final private function __construct()
  {
    $this->init();
  }

  abstract private function init(): void;

  public static function getInstance(): self
  {
    if (null === self::$instance) {
      self::$instance = new self();
    }
    return self::$instance;
  }

  /**
   * @return void
   * @throws Exception
   */
  final public function __wakeup(): void
  {
    throw new Exception("Cannot unserialize singleton");
  }

  /**
   * Initialize the theme class. Used to protect the constructor.
   * @return void
   */

  final protected function __clone(): void
  {
  }
}