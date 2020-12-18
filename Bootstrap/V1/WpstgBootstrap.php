<?php

namespace WPStaging\Bootstrap\V1;

if (!class_exists(WpstgBootstrap::class)) {
    abstract class WpstgBootstrap
    {
        private $shouldBootstrap = true;
        private $rootPath;
        private $requirements;

        /**
         * WpstgBootstrap constructor.
         *
         * @param string            $rootPath
         * @param WpstgRequirements $requirements
         */
        public function __construct($rootPath, WpstgRequirements $requirements)
        {
            $this->rootPath     = $rootPath;
            $this->requirements = $requirements;
        }

        abstract protected function afterBootstrap();

        public function checkRequirements()
        {
            try {
                $this->requirements->checkRequirements();
            } catch (\Exception $e) {
                $this->shouldBootstrap = false;

                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log(sprintf("[Activation] WP STAGING: %s", $e->getMessage()));
                }
            }
        }

        public function bootstrap()
        {
            // Early bail: Requirements not met.
            if (!$this->shouldBootstrap) {
                return;
            }

            if (file_exists(__DIR__ . '/../autoloader_dev.php')) {
                require_once __DIR__ . '/../autoloader_dev.php';
            } else {
                require_once __DIR__ . '/../autoloader.php';
            }

            $this->afterBootstrap();
        }

        public function passedRequirements()
        {
            return $this->shouldBootstrap;
        }
    }
}

