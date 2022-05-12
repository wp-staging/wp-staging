<?php

namespace WPStaging\Vendor\Aws;

use WPStaging\Vendor\Aws\Api\Validator;
use WPStaging\Vendor\Aws\Api\ApiProvider;
use WPStaging\Vendor\Aws\Api\Service;
use WPStaging\Vendor\Aws\ClientSideMonitoring\ApiCallAttemptMonitoringMiddleware;
use WPStaging\Vendor\Aws\ClientSideMonitoring\ApiCallMonitoringMiddleware;
use WPStaging\Vendor\Aws\ClientSideMonitoring\Configuration;
use WPStaging\Vendor\Aws\Credentials\Credentials;
use WPStaging\Vendor\Aws\Credentials\CredentialsInterface;
use WPStaging\Vendor\Aws\Endpoint\PartitionEndpointProvider;
use WPStaging\Vendor\Aws\Endpoint\UseFipsEndpoint\Configuration as UseFipsEndpointConfiguration;
use WPStaging\Vendor\Aws\Endpoint\UseFipsEndpoint\ConfigurationProvider as UseFipsConfigProvider;
use WPStaging\Vendor\Aws\Endpoint\UseFipsEndpoint\ConfigurationInterface as UseFipsEndpointConfigurationInterface;
use WPStaging\Vendor\Aws\Endpoint\UseDualstackEndpoint\Configuration as UseDualStackEndpointConfiguration;
use WPStaging\Vendor\Aws\Endpoint\UseDualstackEndpoint\ConfigurationProvider as UseDualStackConfigProvider;
use WPStaging\Vendor\Aws\Endpoint\UseDualstackEndpoint\ConfigurationInterface as UseDualStackEndpointConfigurationInterface;
use WPStaging\Vendor\Aws\EndpointDiscovery\ConfigurationInterface;
use WPStaging\Vendor\Aws\EndpointDiscovery\ConfigurationProvider;
use WPStaging\Vendor\Aws\Exception\InvalidRegionException;
use WPStaging\Vendor\Aws\Retry\ConfigurationInterface as RetryConfigInterface;
use WPStaging\Vendor\Aws\Retry\ConfigurationProvider as RetryConfigProvider;
use WPStaging\Vendor\Aws\DefaultsMode\ConfigurationInterface as ConfigModeInterface;
use WPStaging\Vendor\Aws\DefaultsMode\ConfigurationProvider as ConfigModeProvider;
use WPStaging\Vendor\Aws\Signature\SignatureProvider;
use WPStaging\Vendor\Aws\Endpoint\EndpointProvider;
use WPStaging\Vendor\GuzzleHttp\Promise\PromiseInterface;
use WPStaging\Vendor\Aws\Credentials\CredentialProvider;
use InvalidArgumentException as IAE;
use WPStaging\Vendor\Psr\Http\Message\RequestInterface;
/**
 * @internal Resolves a hash of client arguments to construct a client.
 */
class ClientResolver
{
    /** @var array */
    private $argDefinitions;
    /** @var array Map of types to a corresponding function */
    private static $typeMap = ['resource' => 'is_resource', 'callable' => 'is_callable', 'int' => 'is_int', 'bool' => 'is_bool', 'string' => 'is_string', 'object' => 'is_object', 'array' => 'is_array'];
    private static $defaultArgs = ['service' => ['type' => 'value', 'valid' => ['string'], 'doc' => 'Name of the service to utilize. This value will be supplied by default when using one of the SDK clients (e.g., Aws\\S3\\S3Client).', 'required' => \true, 'internal' => \true], 'exception_class' => ['type' => 'value', 'valid' => ['string'], 'doc' => 'Exception class to create when an error occurs.', 'default' => 'WPStaging\\Vendor\\Aws\\Exception\\AwsException', 'internal' => \true], 'scheme' => ['type' => 'value', 'valid' => ['string'], 'default' => 'https', 'doc' => 'URI scheme to use when connecting connect. The SDK will utilize "https" endpoints (i.e., utilize SSL/TLS connections) by default. You can attempt to connect to a service over an unencrypted "http" endpoint by setting ``scheme`` to "http".'], 'disable_host_prefix_injection' => ['type' => 'value', 'valid' => ['bool'], 'doc' => 'Set to true to disable host prefix injection logic for services that use it. This disables the entire prefix injection, including the portions supplied by user-defined parameters. Setting this flag will have no effect on services that do not use host prefix injection.', 'default' => \false], 'endpoint' => ['type' => 'value', 'valid' => ['string'], 'doc' => 'The full URI of the webservice. This is only required when connecting to a custom endpoint (e.g., a local version of S3).', 'fn' => [__CLASS__, '_apply_endpoint']], 'region' => ['type' => 'value', 'valid' => ['string'], 'required' => [__CLASS__, '_missing_region'], 'doc' => 'Region to connect to. See http://docs.aws.amazon.com/general/latest/gr/rande.html for a list of available regions.'], 'version' => ['type' => 'value', 'valid' => ['string'], 'required' => [__CLASS__, '_missing_version'], 'doc' => 'The version of the webservice to utilize (e.g., 2006-03-01).'], 'signature_provider' => ['type' => 'value', 'valid' => ['callable'], 'doc' => 'A callable that accepts a signature version name (e.g., "v4"), a service name, and region, and  returns a SignatureInterface object or null. This provider is used to create signers utilized by the client. See Aws\\Signature\\SignatureProvider for a list of built-in providers', 'default' => [__CLASS__, '_default_signature_provider']], 'api_provider' => ['type' => 'value', 'valid' => ['callable'], 'doc' => 'An optional PHP callable that accepts a type, service, and version argument, and returns an array of corresponding configuration data. The type value can be one of api, waiter, or paginator.', 'fn' => [__CLASS__, '_apply_api_provider'], 'default' => [\WPStaging\Vendor\Aws\Api\ApiProvider::class, 'defaultProvider']], 'configuration_mode' => ['type' => 'value', 'valid' => [\WPStaging\Vendor\Aws\DefaultsMode\ConfigurationInterface::class, \WPStaging\Vendor\Aws\CacheInterface::class, 'string', 'closure'], 'doc' => "Sets the default configuration mode. Otherwise provide an instance of Aws\\DefaultsMode\\ConfigurationInterface, an instance of  Aws\\CacheInterface, or a string containing a valid mode", 'fn' => [__CLASS__, '_apply_defaults'], 'default' => [\WPStaging\Vendor\Aws\DefaultsMode\ConfigurationProvider::class, 'defaultProvider']], 'use_fips_endpoint' => ['type' => 'value', 'valid' => ['bool', \WPStaging\Vendor\Aws\Endpoint\UseFipsEndpoint\Configuration::class, \WPStaging\Vendor\Aws\CacheInterface::class, 'callable'], 'doc' => 'Set to true to enable the use of FIPS pseudo regions', 'fn' => [__CLASS__, '_apply_use_fips_endpoint'], 'default' => [__CLASS__, '_default_use_fips_endpoint']], 'use_dual_stack_endpoint' => ['type' => 'value', 'valid' => ['bool', \WPStaging\Vendor\Aws\Endpoint\UseDualstackEndpoint\Configuration::class, \WPStaging\Vendor\Aws\CacheInterface::class, 'callable'], 'doc' => 'Set to true to enable the use of dual-stack endpoints', 'fn' => [__CLASS__, '_apply_use_dual_stack_endpoint'], 'default' => [__CLASS__, '_default_use_dual_stack_endpoint']], 'endpoint_provider' => ['type' => 'value', 'valid' => ['callable'], 'fn' => [__CLASS__, '_apply_endpoint_provider'], 'doc' => 'An optional PHP callable that accepts a hash of options including a "service" and "region" key and returns NULL or a hash of endpoint data, of which the "endpoint" key is required. See Aws\\Endpoint\\EndpointProvider for a list of built-in providers.', 'default' => [__CLASS__, '_default_endpoint_provider']], 'serializer' => ['default' => [__CLASS__, '_default_serializer'], 'fn' => [__CLASS__, '_apply_serializer'], 'internal' => \true, 'type' => 'value', 'valid' => ['callable']], 'signature_version' => ['type' => 'config', 'valid' => ['string'], 'doc' => 'A string representing a custom signature version to use with a service (e.g., v4). Note that per/operation signature version MAY override this requested signature version.', 'default' => [__CLASS__, '_default_signature_version']], 'signing_name' => ['type' => 'config', 'valid' => ['string'], 'doc' => 'A string representing a custom service name to be used when calculating a request signature.', 'default' => [__CLASS__, '_default_signing_name']], 'signing_region' => ['type' => 'config', 'valid' => ['string'], 'doc' => 'A string representing a custom region name to be used when calculating a request signature.', 'default' => [__CLASS__, '_default_signing_region']], 'profile' => ['type' => 'config', 'valid' => ['string'], 'doc' => 'Allows you to specify which profile to use when credentials are created from the AWS credentials file in your HOME directory. This setting overrides the AWS_PROFILE environment variable. Note: Specifying "profile" will cause the "credentials" and "use_aws_shared_config_files" keys to be ignored.', 'fn' => [__CLASS__, '_apply_profile']], 'credentials' => ['type' => 'value', 'valid' => [\WPStaging\Vendor\Aws\Credentials\CredentialsInterface::class, \WPStaging\Vendor\Aws\CacheInterface::class, 'array', 'bool', 'callable'], 'doc' => 'Specifies the credentials used to sign requests. Provide an Aws\\Credentials\\CredentialsInterface object, an associative array of "key", "secret", and an optional "token" key, `false` to use null credentials, or a callable credentials provider used to create credentials or return null. See Aws\\Credentials\\CredentialProvider for a list of built-in credentials providers. If no credentials are provided, the SDK will attempt to load them from the environment.', 'fn' => [__CLASS__, '_apply_credentials'], 'default' => [__CLASS__, '_default_credential_provider']], 'endpoint_discovery' => ['type' => 'value', 'valid' => [\WPStaging\Vendor\Aws\EndpointDiscovery\ConfigurationInterface::class, \WPStaging\Vendor\Aws\CacheInterface::class, 'array', 'callable'], 'doc' => 'Specifies settings for endpoint discovery. Provide an instance of Aws\\EndpointDiscovery\\ConfigurationInterface, an instance Aws\\CacheInterface, a callable that provides a promise for a Configuration object, or an associative array with the following keys: enabled: (bool) Set to true to enable endpoint discovery, false to explicitly disable it. Defaults to false; cache_limit: (int) The maximum number of keys in the endpoints cache. Defaults to 1000.', 'fn' => [__CLASS__, '_apply_endpoint_discovery'], 'default' => [__CLASS__, '_default_endpoint_discovery_provider']], 'stats' => ['type' => 'value', 'valid' => ['bool', 'array'], 'default' => \false, 'doc' => 'Set to true to gather transfer statistics on requests sent. Alternatively, you can provide an associative array with the following keys: retries: (bool) Set to false to disable reporting on retries attempted; http: (bool) Set to true to enable collecting statistics from lower level HTTP adapters (e.g., values returned in GuzzleHttp\\TransferStats). HTTP handlers must support an http_stats_receiver option for this to have an effect; timer: (bool) Set to true to enable a command timer that reports the total wall clock time spent on an operation in seconds.', 'fn' => [__CLASS__, '_apply_stats']], 'retries' => ['type' => 'value', 'valid' => ['int', \WPStaging\Vendor\Aws\Retry\ConfigurationInterface::class, \WPStaging\Vendor\Aws\CacheInterface::class, 'callable', 'array'], 'doc' => "Configures the retry mode and maximum number of allowed retries for a client (pass 0 to disable retries). Provide an integer for 'legacy' mode with the specified number of retries. Otherwise provide an instance of Aws\\Retry\\ConfigurationInterface, an instance of  Aws\\CacheInterface, a callable function, or an array with the following keys: mode: (string) Set to 'legacy', 'standard' (uses retry quota management), or 'adapative' (an experimental mode that adds client-side rate limiting to standard mode); max_attempts: (int) The maximum number of attempts for a given request. ", 'fn' => [__CLASS__, '_apply_retries'], 'default' => [\WPStaging\Vendor\Aws\Retry\ConfigurationProvider::class, 'defaultProvider']], 'validate' => ['type' => 'value', 'valid' => ['bool', 'array'], 'default' => \true, 'doc' => 'Set to false to disable client-side parameter validation. Set to true to utilize default validation constraints. Set to an associative array of validation options to enable specific validation constraints.', 'fn' => [__CLASS__, '_apply_validate']], 'debug' => ['type' => 'value', 'valid' => ['bool', 'array'], 'doc' => 'Set to true to display debug information when sending requests. Alternatively, you can provide an associative array with the following keys: logfn: (callable) Function that is invoked with log messages; stream_size: (int) When the size of a stream is greater than this number, the stream data will not be logged (set to "0" to not log any stream data); scrub_auth: (bool) Set to false to disable the scrubbing of auth data from the logged messages; http: (bool) Set to false to disable the "debug" feature of lower level HTTP adapters (e.g., verbose curl output).', 'fn' => [__CLASS__, '_apply_debug']], 'csm' => ['type' => 'value', 'valid' => [\WPStaging\Vendor\Aws\ClientSideMonitoring\ConfigurationInterface::class, 'callable', 'array', 'bool'], 'doc' => 'CSM options for the client. Provides a callable wrapping a promise, a boolean "false", an instance of ConfigurationInterface, or an associative array of "enabled", "host", "port", and "client_id".', 'fn' => [__CLASS__, '_apply_csm'], 'default' => [\WPStaging\Vendor\Aws\ClientSideMonitoring\ConfigurationProvider::class, 'defaultProvider']], 'http' => ['type' => 'value', 'valid' => ['array'], 'default' => [], 'doc' => 'Set to an array of SDK request options to apply to each request (e.g., proxy, verify, etc.).'], 'http_handler' => ['type' => 'value', 'valid' => ['callable'], 'doc' => 'An HTTP handler is a function that accepts a PSR-7 request object and returns a promise that is fulfilled with a PSR-7 response object or rejected with an array of exception data. NOTE: This option supersedes any provided "handler" option.', 'fn' => [__CLASS__, '_apply_http_handler']], 'handler' => ['type' => 'value', 'valid' => ['callable'], 'doc' => 'A handler that accepts a command object, request object and returns a promise that is fulfilled with an Aws\\ResultInterface object or rejected with an Aws\\Exception\\AwsException. A handler does not accept a next handler as it is terminal and expected to fulfill a command. If no handler is provided, a default Guzzle handler will be utilized.', 'fn' => [__CLASS__, '_apply_handler'], 'default' => [__CLASS__, '_default_handler']], 'ua_append' => ['type' => 'value', 'valid' => ['string', 'array'], 'doc' => 'Provide a string or array of strings to send in the User-Agent header.', 'fn' => [__CLASS__, '_apply_user_agent'], 'default' => []], 'idempotency_auto_fill' => ['type' => 'value', 'valid' => ['bool', 'callable'], 'doc' => 'Set to false to disable SDK to populate parameters that enabled \'idempotencyToken\' trait with a random UUID v4 value on your behalf. Using default value \'true\' still allows parameter value to be overwritten when provided. Note: auto-fill only works when cryptographically secure random bytes generator functions(random_bytes, openssl_random_pseudo_bytes or mcrypt_create_iv) can be found. You may also provide a callable source of random bytes.', 'default' => \true, 'fn' => [__CLASS__, '_apply_idempotency_auto_fill']], 'use_aws_shared_config_files' => ['type' => 'value', 'valid' => ['bool'], 'doc' => 'Set to false to disable checking for shared aws config files usually located in \'~/.aws/config\' and \'~/.aws/credentials\'.  This will be ignored if you set the \'profile\' setting.', 'default' => \true]];
    /**
     * Gets an array of default client arguments, each argument containing a
     * hash of the following:
     *
     * - type: (string, required) option type described as follows:
     *   - value: The default option type.
     *   - config: The provided value is made available in the client's
     *     getConfig() method.
     * - valid: (array, required) Valid PHP types or class names. Note: null
     *   is not an allowed type.
     * - required: (bool, callable) Whether or not the argument is required.
     *   Provide a function that accepts an array of arguments and returns a
     *   string to provide a custom error message.
     * - default: (mixed) The default value of the argument if not provided. If
     *   a function is provided, then it will be invoked to provide a default
     *   value. The function is provided the array of options and is expected
     *   to return the default value of the option. The default value can be a
     *   closure and can not be a callable string that is not  part of the
     *   defaultArgs array.
     * - doc: (string) The argument documentation string.
     * - fn: (callable) Function used to apply the argument. The function
     *   accepts the provided value, array of arguments by reference, and an
     *   event emitter.
     *
     * Note: Order is honored and important when applying arguments.
     *
     * @return array
     */
    public static function getDefaultArguments()
    {
        return self::$defaultArgs;
    }
    /**
     * @param array $argDefinitions Client arguments.
     */
    public function __construct(array $argDefinitions)
    {
        $this->argDefinitions = $argDefinitions;
    }
    /**
     * Resolves client configuration options and attached event listeners.
     * Check for missing keys in passed arguments
     *
     * @param array       $args Provided constructor arguments.
     * @param HandlerList $list Handler list to augment.
     *
     * @return array Returns the array of provided options.
     * @throws \InvalidArgumentException
     * @see Aws\AwsClient::__construct for a list of available options.
     */
    public function resolve(array $args, \WPStaging\Vendor\Aws\HandlerList $list)
    {
        $args['config'] = [];
        foreach ($this->argDefinitions as $key => $a) {
            // Add defaults, validate required values, and skip if not set.
            if (!isset($args[$key])) {
                if (isset($a['default'])) {
                    // Merge defaults in when not present.
                    if (\is_callable($a['default']) && (\is_array($a['default']) || $a['default'] instanceof \Closure)) {
                        $args[$key] = $a['default']($args);
                    } else {
                        $args[$key] = $a['default'];
                    }
                } elseif (empty($a['required'])) {
                    continue;
                } else {
                    $this->throwRequired($args);
                }
            }
            // Validate the types against the provided value.
            foreach ($a['valid'] as $check) {
                if (isset(self::$typeMap[$check])) {
                    $fn = self::$typeMap[$check];
                    if ($fn($args[$key])) {
                        goto is_valid;
                    }
                } elseif ($args[$key] instanceof $check) {
                    goto is_valid;
                }
            }
            $this->invalidType($key, $args[$key]);
            // Apply the value
            is_valid:
            if (isset($a['fn'])) {
                $a['fn']($args[$key], $args, $list);
            }
            if ($a['type'] === 'config') {
                $args['config'][$key] = $args[$key];
            }
        }
        return $args;
    }
    /**
     * Creates a verbose error message for an invalid argument.
     *
     * @param string $name        Name of the argument that is missing.
     * @param array  $args        Provided arguments
     * @param bool   $useRequired Set to true to show the required fn text if
     *                            available instead of the documentation.
     * @return string
     */
    private function getArgMessage($name, $args = [], $useRequired = \false)
    {
        $arg = $this->argDefinitions[$name];
        $msg = '';
        $modifiers = [];
        if (isset($arg['valid'])) {
            $modifiers[] = \implode('|', $arg['valid']);
        }
        if (isset($arg['choice'])) {
            $modifiers[] = 'One of ' . \implode(', ', $arg['choice']);
        }
        if ($modifiers) {
            $msg .= '(' . \implode('; ', $modifiers) . ')';
        }
        $msg = \wordwrap("{$name}: {$msg}", 75, "\n  ");
        if ($useRequired && \is_callable($arg['required'])) {
            $msg .= "\n\n  ";
            $msg .= \str_replace("\n", "\n  ", \call_user_func($arg['required'], $args));
        } elseif (isset($arg['doc'])) {
            $msg .= \wordwrap("\n\n  {$arg['doc']}", 75, "\n  ");
        }
        return $msg;
    }
    /**
     * Throw when an invalid type is encountered.
     *
     * @param string $name     Name of the value being validated.
     * @param mixed  $provided The provided value.
     * @throws \InvalidArgumentException
     */
    private function invalidType($name, $provided)
    {
        $expected = \implode('|', $this->argDefinitions[$name]['valid']);
        $msg = "Invalid configuration value " . "provided for \"{$name}\". Expected {$expected}, but got " . describe_type($provided) . "\n\n" . $this->getArgMessage($name);
        throw new \InvalidArgumentException($msg);
    }
    /**
     * Throws an exception for missing required arguments.
     *
     * @param array $args Passed in arguments.
     * @throws \InvalidArgumentException
     */
    private function throwRequired(array $args)
    {
        $missing = [];
        foreach ($this->argDefinitions as $k => $a) {
            if (empty($a['required']) || isset($a['default']) || isset($args[$k])) {
                continue;
            }
            $missing[] = $this->getArgMessage($k, $args, \true);
        }
        $msg = "Missing required client configuration options: \n\n";
        $msg .= \implode("\n\n", $missing);
        throw new \InvalidArgumentException($msg);
    }
    public static function _apply_retries($value, array &$args, \WPStaging\Vendor\Aws\HandlerList $list)
    {
        // A value of 0 for the config option disables retries
        if ($value) {
            $config = \WPStaging\Vendor\Aws\Retry\ConfigurationProvider::unwrap($value);
            if ($config->getMode() === 'legacy') {
                // # of retries is 1 less than # of attempts
                $decider = \WPStaging\Vendor\Aws\RetryMiddleware::createDefaultDecider($config->getMaxAttempts() - 1);
                $list->appendSign(\WPStaging\Vendor\Aws\Middleware::retry($decider, null, $args['stats']['retries']), 'retry');
            } else {
                $list->appendSign(\WPStaging\Vendor\Aws\RetryMiddlewareV2::wrap($config, ['collect_stats' => $args['stats']['retries']]), 'retry');
            }
        }
    }
    public static function _apply_defaults($value, array &$args, \WPStaging\Vendor\Aws\HandlerList $list)
    {
        $config = \WPStaging\Vendor\Aws\DefaultsMode\ConfigurationProvider::unwrap($value);
        if ($config->getMode() !== 'legacy') {
            if (!isset($args['retries']) && !\is_null($config->getRetryMode())) {
                $args['retries'] = ['mode' => $config->getRetryMode()];
            }
            if (!isset($args['sts_regional_endpoints']) && !\is_null($config->getStsRegionalEndpoints())) {
                $args['sts_regional_endpoints'] = ['mode' => $config->getStsRegionalEndpoints()];
            }
            if (!isset($args['s3_us_east_1_regional_endpoint']) && !\is_null($config->getS3UsEast1RegionalEndpoints())) {
                $args['s3_us_east_1_regional_endpoint'] = ['mode' => $config->getS3UsEast1RegionalEndpoints()];
            }
            if (!isset($args['http'])) {
                $args['http'] = [];
            }
            if (!isset($args['http']['connect_timeout']) && !\is_null($config->getConnectTimeoutInMillis())) {
                $args['http']['connect_timeout'] = $config->getConnectTimeoutInMillis() / 1000;
            }
            if (!isset($args['http']['timeout']) && !\is_null($config->getHttpRequestTimeoutInMillis())) {
                $args['http']['timeout'] = $config->getHttpRequestTimeoutInMillis() / 1000;
            }
        }
    }
    public static function _apply_credentials($value, array &$args)
    {
        if (\is_callable($value)) {
            return;
        }
        if ($value instanceof \WPStaging\Vendor\Aws\Credentials\CredentialsInterface) {
            $args['credentials'] = \WPStaging\Vendor\Aws\Credentials\CredentialProvider::fromCredentials($value);
        } elseif (\is_array($value) && isset($value['key']) && isset($value['secret'])) {
            $args['credentials'] = \WPStaging\Vendor\Aws\Credentials\CredentialProvider::fromCredentials(new \WPStaging\Vendor\Aws\Credentials\Credentials($value['key'], $value['secret'], isset($value['token']) ? $value['token'] : null, isset($value['expires']) ? $value['expires'] : null));
        } elseif ($value === \false) {
            $args['credentials'] = \WPStaging\Vendor\Aws\Credentials\CredentialProvider::fromCredentials(new \WPStaging\Vendor\Aws\Credentials\Credentials('', ''));
            $args['config']['signature_version'] = 'anonymous';
        } elseif ($value instanceof \WPStaging\Vendor\Aws\CacheInterface) {
            $args['credentials'] = \WPStaging\Vendor\Aws\Credentials\CredentialProvider::defaultProvider($args);
        } else {
            throw new \InvalidArgumentException('Credentials must be an instance of ' . 'Aws\\Credentials\\CredentialsInterface, an associative ' . 'array that contains "key", "secret", and an optional "token" ' . 'key-value pairs, a credentials provider function, or false.');
        }
    }
    public static function _default_credential_provider(array $args)
    {
        return \WPStaging\Vendor\Aws\Credentials\CredentialProvider::defaultProvider($args);
    }
    public static function _apply_csm($value, array &$args, \WPStaging\Vendor\Aws\HandlerList $list)
    {
        if ($value === \false) {
            $value = new \WPStaging\Vendor\Aws\ClientSideMonitoring\Configuration(\false, \WPStaging\Vendor\Aws\ClientSideMonitoring\ConfigurationProvider::DEFAULT_HOST, \WPStaging\Vendor\Aws\ClientSideMonitoring\ConfigurationProvider::DEFAULT_PORT, \WPStaging\Vendor\Aws\ClientSideMonitoring\ConfigurationProvider::DEFAULT_CLIENT_ID);
            $args['csm'] = $value;
        }
        $list->appendBuild(\WPStaging\Vendor\Aws\ClientSideMonitoring\ApiCallMonitoringMiddleware::wrap($args['credentials'], $value, $args['region'], $args['api']->getServiceId()), 'ApiCallMonitoringMiddleware');
        $list->appendAttempt(\WPStaging\Vendor\Aws\ClientSideMonitoring\ApiCallAttemptMonitoringMiddleware::wrap($args['credentials'], $value, $args['region'], $args['api']->getServiceId()), 'ApiCallAttemptMonitoringMiddleware');
    }
    public static function _apply_api_provider(callable $value, array &$args)
    {
        $api = new \WPStaging\Vendor\Aws\Api\Service(\WPStaging\Vendor\Aws\Api\ApiProvider::resolve($value, 'api', $args['service'], $args['version']), $value);
        if (empty($args['config']['signing_name']) && isset($api['metadata']['signingName'])) {
            $args['config']['signing_name'] = $api['metadata']['signingName'];
        }
        $args['api'] = $api;
        $args['parser'] = \WPStaging\Vendor\Aws\Api\Service::createParser($api);
        $args['error_parser'] = \WPStaging\Vendor\Aws\Api\Service::createErrorParser($api->getProtocol(), $api);
    }
    public static function _apply_endpoint_provider(callable $value, array &$args)
    {
        if (!isset($args['endpoint'])) {
            $endpointPrefix = isset($args['api']['metadata']['endpointPrefix']) ? $args['api']['metadata']['endpointPrefix'] : $args['service'];
            // Check region is a valid host label when it is being used to
            // generate an endpoint
            if (!self::isValidRegion($args['region'])) {
                throw new \WPStaging\Vendor\Aws\Exception\InvalidRegionException('Region must be a valid RFC' . ' host label.');
            }
            $serviceEndpoints = \is_array($value) && isset($value['services'][$args['service']]['endpoints']) ? $value['services'][$args['service']]['endpoints'] : null;
            if (isset($serviceEndpoints[$args['region']]['deprecated'])) {
                \trigger_error("The service " . $args['service'] . "has " . " deprecated the region " . $args['region'] . ".", \E_USER_WARNING);
            }
            $args['region'] = \WPStaging\Vendor\Aws\strip_fips_pseudo_regions($args['region']);
            // Invoke the endpoint provider and throw if it does not resolve.
            $result = \WPStaging\Vendor\Aws\Endpoint\EndpointProvider::resolve($value, ['service' => $endpointPrefix, 'region' => $args['region'], 'scheme' => $args['scheme'], 'options' => self::getEndpointProviderOptions($args)]);
            $args['endpoint'] = $result['endpoint'];
            if (empty($args['config']['signature_version']) && isset($result['signatureVersion'])) {
                $args['config']['signature_version'] = $result['signatureVersion'];
            }
            if (empty($args['config']['signing_region']) && isset($result['signingRegion'])) {
                $args['config']['signing_region'] = $result['signingRegion'];
            }
            if (empty($args['config']['signing_name']) && isset($result['signingName'])) {
                $args['config']['signing_name'] = $result['signingName'];
            }
        }
    }
    public static function _apply_endpoint_discovery($value, array &$args)
    {
        $args['endpoint_discovery'] = $value;
    }
    public static function _default_endpoint_discovery_provider(array $args)
    {
        return \WPStaging\Vendor\Aws\EndpointDiscovery\ConfigurationProvider::defaultProvider($args);
    }
    public static function _apply_use_fips_endpoint($value, array &$args)
    {
        if ($value instanceof \WPStaging\Vendor\Aws\CacheInterface) {
            $value = \WPStaging\Vendor\Aws\Endpoint\UseFipsEndpoint\ConfigurationProvider::defaultProvider($args);
        }
        if (\is_callable($value)) {
            $value = $value();
        }
        if ($value instanceof \WPStaging\Vendor\GuzzleHttp\Promise\PromiseInterface) {
            $value = $value->wait();
        }
        if ($value instanceof \WPStaging\Vendor\Aws\Endpoint\UseFipsEndpoint\ConfigurationInterface) {
            $args['config']['use_fips_endpoint'] = $value;
        } else {
            // The Configuration class itself will validate other inputs
            $args['config']['use_fips_endpoint'] = new \WPStaging\Vendor\Aws\Endpoint\UseFipsEndpoint\Configuration($value);
        }
    }
    public static function _default_use_fips_endpoint(array &$args)
    {
        return \WPStaging\Vendor\Aws\Endpoint\UseFipsEndpoint\ConfigurationProvider::defaultProvider($args);
    }
    public static function _apply_use_dual_stack_endpoint($value, array &$args)
    {
        if ($value instanceof \WPStaging\Vendor\Aws\CacheInterface) {
            $value = \WPStaging\Vendor\Aws\Endpoint\UseDualstackEndpoint\ConfigurationProvider::defaultProvider($args);
        }
        if (\is_callable($value)) {
            $value = $value();
        }
        if ($value instanceof \WPStaging\Vendor\GuzzleHttp\Promise\PromiseInterface) {
            $value = $value->wait();
        }
        if ($value instanceof \WPStaging\Vendor\Aws\Endpoint\UseDualstackEndpoint\ConfigurationInterface) {
            $args['config']['use_dual_stack_endpoint'] = $value;
        } else {
            // The Configuration class itself will validate other inputs
            $args['config']['use_dual_stack_endpoint'] = new \WPStaging\Vendor\Aws\Endpoint\UseDualstackEndpoint\Configuration($value, $args['region']);
        }
    }
    public static function _default_use_dual_stack_endpoint(array &$args)
    {
        return \WPStaging\Vendor\Aws\Endpoint\UseDualstackEndpoint\ConfigurationProvider::defaultProvider($args);
    }
    public static function _apply_serializer($value, array &$args, \WPStaging\Vendor\Aws\HandlerList $list)
    {
        $list->prependBuild(\WPStaging\Vendor\Aws\Middleware::requestBuilder($value), 'builder');
    }
    public static function _apply_debug($value, array &$args, \WPStaging\Vendor\Aws\HandlerList $list)
    {
        if ($value !== \false) {
            $list->interpose(new \WPStaging\Vendor\Aws\TraceMiddleware($value === \true ? [] : $value, $args['api']));
        }
    }
    public static function _apply_stats($value, array &$args, \WPStaging\Vendor\Aws\HandlerList $list)
    {
        // Create an array of stat collectors that are disabled (set to false)
        // by default. If the user has passed in true, enable all stat
        // collectors.
        $defaults = \array_fill_keys(['http', 'retries', 'timer'], $value === \true);
        $args['stats'] = \is_array($value) ? \array_replace($defaults, $value) : $defaults;
        if ($args['stats']['timer']) {
            $list->prependInit(\WPStaging\Vendor\Aws\Middleware::timer(), 'timer');
        }
    }
    public static function _apply_profile($_, array &$args)
    {
        $args['credentials'] = \WPStaging\Vendor\Aws\Credentials\CredentialProvider::ini($args['profile']);
    }
    public static function _apply_validate($value, array &$args, \WPStaging\Vendor\Aws\HandlerList $list)
    {
        if ($value === \false) {
            return;
        }
        $validator = $value === \true ? new \WPStaging\Vendor\Aws\Api\Validator() : new \WPStaging\Vendor\Aws\Api\Validator($value);
        $list->appendValidate(\WPStaging\Vendor\Aws\Middleware::validation($args['api'], $validator), 'validation');
    }
    public static function _apply_handler($value, array &$args, \WPStaging\Vendor\Aws\HandlerList $list)
    {
        $list->setHandler($value);
    }
    public static function _default_handler(array &$args)
    {
        return new \WPStaging\Vendor\Aws\WrappedHttpHandler(default_http_handler(), $args['parser'], $args['error_parser'], $args['exception_class'], $args['stats']['http']);
    }
    public static function _apply_http_handler($value, array &$args, \WPStaging\Vendor\Aws\HandlerList $list)
    {
        $args['handler'] = new \WPStaging\Vendor\Aws\WrappedHttpHandler($value, $args['parser'], $args['error_parser'], $args['exception_class'], $args['stats']['http']);
    }
    public static function _apply_user_agent($inputUserAgent, array &$args, \WPStaging\Vendor\Aws\HandlerList $list)
    {
        //Add SDK version
        $xAmzUserAgent = ['aws-sdk-php/' . \WPStaging\Vendor\Aws\Sdk::VERSION];
        //If on HHVM add the HHVM version
        if (\defined('WPStaging\\Vendor\\HHVM_VERSION')) {
            $xAmzUserAgent[] = 'HHVM/' . HHVM_VERSION;
        }
        //Set up the updated user agent
        $legacyUserAgent = $xAmzUserAgent;
        //Add OS version
        $disabledFunctions = \explode(',', \ini_get('disable_functions'));
        if (\function_exists('php_uname') && !\in_array('php_uname', $disabledFunctions, \true)) {
            $osName = "OS/" . \php_uname('s') . '/' . \php_uname('r');
            if (!empty($osName)) {
                $legacyUserAgent[] = $osName;
            }
        }
        //Add the language version
        $legacyUserAgent[] = 'lang/php/' . \phpversion();
        //Add exec environment if present
        if ($executionEnvironment = \getenv('AWS_EXECUTION_ENV')) {
            $legacyUserAgent[] = $executionEnvironment;
        }
        //Add the input to the end
        if ($inputUserAgent) {
            if (!\is_array($inputUserAgent)) {
                $inputUserAgent = [$inputUserAgent];
            }
            $inputUserAgent = \array_map('strval', $inputUserAgent);
            $legacyUserAgent = \array_merge($legacyUserAgent, $inputUserAgent);
            $xAmzUserAgent = \array_merge($xAmzUserAgent, $inputUserAgent);
        }
        $args['ua_append'] = $legacyUserAgent;
        $list->appendBuild(static function (callable $handler) use($xAmzUserAgent, $legacyUserAgent) {
            return function (\WPStaging\Vendor\Aws\CommandInterface $command, \WPStaging\Vendor\Psr\Http\Message\RequestInterface $request) use($handler, $legacyUserAgent, $xAmzUserAgent) {
                return $handler($command, $request->withHeader('X-Amz-User-Agent', \implode(' ', \array_merge($xAmzUserAgent, $request->getHeader('X-Amz-User-Agent'))))->withHeader('User-Agent', \implode(' ', \array_merge($legacyUserAgent, $request->getHeader('User-Agent')))));
            };
        });
    }
    public static function _apply_endpoint($value, array &$args, \WPStaging\Vendor\Aws\HandlerList $list)
    {
        $args['endpoint'] = $value;
    }
    public static function _apply_idempotency_auto_fill($value, array &$args, \WPStaging\Vendor\Aws\HandlerList $list)
    {
        $enabled = \false;
        $generator = null;
        if (\is_bool($value)) {
            $enabled = $value;
        } elseif (\is_callable($value)) {
            $enabled = \true;
            $generator = $value;
        }
        if ($enabled) {
            $list->prependInit(\WPStaging\Vendor\Aws\IdempotencyTokenMiddleware::wrap($args['api'], $generator), 'idempotency_auto_fill');
        }
    }
    public static function _default_endpoint_provider(array $args)
    {
        $options = self::getEndpointProviderOptions($args);
        return \WPStaging\Vendor\Aws\Endpoint\PartitionEndpointProvider::defaultProvider($options)->getPartition($args['region'], $args['service']);
    }
    public static function _default_serializer(array $args)
    {
        return \WPStaging\Vendor\Aws\Api\Service::createSerializer($args['api'], $args['endpoint']);
    }
    public static function _default_signature_provider()
    {
        return \WPStaging\Vendor\Aws\Signature\SignatureProvider::defaultProvider();
    }
    public static function _default_signature_version(array &$args)
    {
        if (isset($args['config']['signature_version'])) {
            return $args['config']['signature_version'];
        }
        $args['__partition_result'] = isset($args['__partition_result']) ? isset($args['__partition_result']) : \call_user_func(\WPStaging\Vendor\Aws\Endpoint\PartitionEndpointProvider::defaultProvider(), ['service' => $args['service'], 'region' => $args['region']]);
        return isset($args['__partition_result']['signatureVersion']) ? $args['__partition_result']['signatureVersion'] : $args['api']->getSignatureVersion();
    }
    public static function _default_signing_name(array &$args)
    {
        if (isset($args['config']['signing_name'])) {
            return $args['config']['signing_name'];
        }
        $args['__partition_result'] = isset($args['__partition_result']) ? isset($args['__partition_result']) : \call_user_func(\WPStaging\Vendor\Aws\Endpoint\PartitionEndpointProvider::defaultProvider(), ['service' => $args['service'], 'region' => $args['region']]);
        if (isset($args['__partition_result']['signingName'])) {
            return $args['__partition_result']['signingName'];
        }
        if ($signingName = $args['api']->getSigningName()) {
            return $signingName;
        }
        return $args['service'];
    }
    public static function _default_signing_region(array &$args)
    {
        if (isset($args['config']['signing_region'])) {
            return $args['config']['signing_region'];
        }
        $args['__partition_result'] = isset($args['__partition_result']) ? isset($args['__partition_result']) : \call_user_func(\WPStaging\Vendor\Aws\Endpoint\PartitionEndpointProvider::defaultProvider(), ['service' => $args['service'], 'region' => $args['region']]);
        return isset($args['__partition_result']['signingRegion']) ? $args['__partition_result']['signingRegion'] : $args['region'];
    }
    public static function _missing_version(array $args)
    {
        $service = isset($args['service']) ? $args['service'] : '';
        $versions = \WPStaging\Vendor\Aws\Api\ApiProvider::defaultProvider()->getVersions($service);
        $versions = \implode("\n", \array_map(function ($v) {
            return "* \"{$v}\"";
        }, $versions)) ?: '* (none found)';
        return <<<EOT
A "version" configuration value is required. Specifying a version constraint
ensures that your code will not be affected by a breaking change made to the
service. For example, when using Amazon S3, you can lock your API version to
"2006-03-01".

Your build of the SDK has the following version(s) of "{$service}": {$versions}

You may provide "latest" to the "version" configuration value to utilize the
most recent available API version that your client's API provider can find.
Note: Using 'latest' in a production application is not recommended.

A list of available API versions can be found on each client's API documentation
page: http://docs.aws.amazon.com/aws-sdk-php/v3/api/index.html. If you are
unable to load a specific API version, then you may need to update your copy of
the SDK.
EOT;
    }
    public static function _missing_region(array $args)
    {
        $service = isset($args['service']) ? $args['service'] : '';
        return <<<EOT
A "region" configuration value is required for the "{$service}" service
(e.g., "us-west-2"). A list of available public regions and endpoints can be
found at http://docs.aws.amazon.com/general/latest/gr/rande.html.
EOT;
    }
    /**
     * Extracts client options for the endpoint provider to its own array
     *
     * @param array $args
     * @return array
     */
    private static function getEndpointProviderOptions(array $args)
    {
        $options = [];
        $optionKeys = ['sts_regional_endpoints', 's3_us_east_1_regional_endpoint'];
        $configKeys = ['use_dual_stack_endpoint', 'use_fips_endpoint'];
        foreach ($optionKeys as $key) {
            if (isset($args[$key])) {
                $options[$key] = $args[$key];
            }
        }
        foreach ($configKeys as $key) {
            if (isset($args['config'][$key])) {
                $options[$key] = $args['config'][$key];
            }
        }
        return $options;
    }
    /**
     * Validates a region to be used for endpoint construction
     *
     * @param $region
     * @return bool
     */
    private static function isValidRegion($region)
    {
        return is_valid_hostlabel($region);
    }
}
