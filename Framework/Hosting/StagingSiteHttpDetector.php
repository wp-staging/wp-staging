<?php

namespace WPStaging\Framework\Hosting;

use WPStaging\Framework\Network\SsrfProtection;
use WPStaging\Staging\Dto\StagingSiteDto;
use WPStaging\Staging\PrefixOwnership;
use WPStaging\Staging\Sites;




class StagingSiteHttpDetector
{
 
    const ACTION_CHECK_STAGING_SITE = 'wpstg_check_staging_site_health';

 
    const DIAGNOSTICS_BODY_LIMIT = 4096;

 
    const REQUEST_TIMEOUT = 8;

 
    const MAX_REDIRECTS = 5;

 
    const DELAY_BETWEEN_SITES = 5 * MINUTE_IN_SECONDS;

 
    const DELAY_AFTER_JOB = 30;

 
    const HEALTH_UNKNOWN = 'unknown';

 
    const HEALTH_REACHABLE = 'reachable';

 
    const HEALTH_UNHEALTHY = 'unhealthy';

 
    const REASON_LIVE_SITE = 'live-site';

 
    const REASON_BAD_ANSWER = 'bad-answer';

 
    const REASON_FILES_MISSING = 'files-missing';

 
    const REASON_TABLES_MISSING = 'tables-missing';

 
    const WORDPRESS_FILES = ['index.php', 'wp-load.php', 'wp-includes/version.php'];

 
    const FOLDER_LISTING_TITLE = '<title>Index of /';

 
    const PASSWORD_CODES = [401, 403];

 
    private $sites;

 
    private $ssrfProtection;

 
    private $prefixOwnership;

 
    private $isLiveSiteUnreachableFromItself;






    public function __construct(Sites $sites, SsrfProtection $ssrfProtection, PrefixOwnership $prefixOwnership)
    {
        $this->sites           = $sites;
        $this->ssrfProtection  = $ssrfProtection;
        $this->prefixOwnership = $prefixOwnership;
    }











    public function scheduleCheck(string $cloneId, int $delay = self::DELAY_AFTER_JOB)
    {
        $this->scheduleCheckOnce([$cloneId], $delay);
    }







    public function scheduleCheckOfNewStagingSite(string $cloneId)
    {
        $this->scheduleCheckOnce([$cloneId, true], self::DELAY_AFTER_JOB);
    }
















    public function checkStagingSite(string $cloneId, bool $mayLabelUnlabelledSiteUnhealthy = false): string
    {
        $url = $this->getStagingSiteUrl($cloneId);
        if (empty($url)) {
            return self::HEALTH_UNKNOWN;
        }

        $missingPart = $this->findMissingPartOfStagingSite($cloneId);
        if ($missingPart !== '') {
            $measurement = ['health' => self::HEALTH_UNHEALTHY, 'url' => $url, 'answer' => ['code' => 0, 'body' => ''], 'reason' => $missingPart];

            return $this->recordMeasurement($cloneId, $measurement, $mayLabelUnlabelledSiteUnhealthy);
        }

        if ($this->ssrfProtection->isBlockedUrl($url)) {
            return $this->storeResult($cloneId, self::HEALTH_UNKNOWN, $url, ['code' => 0, 'body' => '']);
        }

        $measurement = $this->measureStagingSite($url);
        if ($measurement['health'] === self::HEALTH_UNHEALTHY) {
            $measurement = $this->measureStagingSite($url);
        }

        return $this->recordMeasurement($cloneId, $measurement, $mayLabelUnlabelledSiteUnhealthy);
    }







    public function scheduleChecksForAllStagingSites()
    {
        $stagingSites = $this->sites->tryGettingStagingSites();
        if (empty($stagingSites)) {
            return;
        }

        $delay = 0;
        foreach (array_keys($stagingSites) as $cloneId) {
            $this->scheduleCheck((string)$cloneId, $delay);
            $delay += self::DELAY_BETWEEN_SITES;
        }
    }








    public function isStagingSiteUnhealthy(string $cloneId): bool
    {
        $stagingSites = $this->sites->tryGettingStagingSites();

        return isset($stagingSites[$cloneId]['health']) && $stagingSites[$cloneId]['health'] === self::HEALTH_UNHEALTHY;
    }







    public function unscheduleCheck(string $cloneId)
    {
        wp_clear_scheduled_hook(self::ACTION_CHECK_STAGING_SITE, [$cloneId]);
        wp_clear_scheduled_hook(self::ACTION_CHECK_STAGING_SITE, [$cloneId, true]);
    }






    private function scheduleCheckOnce(array $checkArguments, int $delay)
    {
        $cloneId = $checkArguments[0];
        if (empty($cloneId) || wp_next_scheduled(self::ACTION_CHECK_STAGING_SITE, $checkArguments)) {
            return;
        }

        wp_schedule_single_event(time() + $delay, self::ACTION_CHECK_STAGING_SITE, $checkArguments);
    }





    private function getStagingSiteUrl(string $cloneId): string
    {
        $stagingSites = $this->sites->tryGettingStagingSites();
        if (empty($stagingSites[$cloneId]['url'])) {
            return '';
        }

        return rtrim((string)$stagingSites[$cloneId]['url'], '/\\');
    }







    private function stagingSiteHasHealthLabel(string $cloneId): bool
    {
        $stagingSites = $this->sites->tryGettingStagingSites();

        return in_array($stagingSites[$cloneId]['health'] ?? '', [self::HEALTH_REACHABLE, self::HEALTH_UNHEALTHY], true);
    }








    private function findMissingPartOfStagingSite(string $cloneId): string
    {
        $stagingSite = $this->sites->getStagingSiteDtoByCloneId($cloneId);
        if ($this->stagingSiteIsKnownToLackWordPressFiles($stagingSite)) {
            return self::REASON_FILES_MISSING;
        }

        if ($this->stagingSiteIsKnownToLackSettingsTable($stagingSite)) {
            return self::REASON_TABLES_MISSING;
        }

        return '';
    }








    private function stagingSiteIsKnownToLackWordPressFiles(StagingSiteDto $stagingSite): bool
    {
        if ($stagingSite->getPath() === '') {
            return false;
        }

        $folder = trailingslashit($stagingSite->getPath());
        if (!is_dir($folder) || !is_readable($folder)) {
            return false;
        }

        foreach (self::WORDPRESS_FILES as $file) {
            if (!is_file($folder . $file)) {
                return true;
            }
        }

        return false;
    }








    private function stagingSiteIsKnownToLackSettingsTable(StagingSiteDto $stagingSite): bool
    {
        global $wpdb;

        $prefix = $stagingSite->getUsedPrefix();
        if ($prefix === '' || $stagingSite->getIsExternalDatabase() || $this->readPrefixFromStagingSiteConfig($stagingSite) !== $prefix) {
            return false;
        }

        $settingsTable = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $wpdb->esc_like($prefix . 'options')));

        return $settingsTable === null && $wpdb->last_error === '';
    }








    private function readPrefixFromStagingSiteConfig(StagingSiteDto $stagingSite)
    {
        if ($stagingSite->getPath() === '') {
            return null;
        }

        return $this->prefixOwnership->readConfigPrefix(
            trailingslashit($stagingSite->getPath()) . 'wp-config.php',
            $stagingSite->getDatabaseName(),
            $stagingSite->getIsCustomDatabaseConnection() ? $stagingSite->getDatabaseServer() : DB_HOST
        );
    }







    private function recordMeasurement(string $cloneId, array $measurement, bool $mayLabelUnlabelledSiteUnhealthy): string
    {
        if ($measurement['health'] === self::HEALTH_UNHEALTHY && !$mayLabelUnlabelledSiteUnhealthy && !$this->stagingSiteHasHealthLabel($cloneId)) {
            return self::HEALTH_UNKNOWN;
        }

        return $this->storeResult($cloneId, $measurement['health'], $measurement['url'], $measurement['answer'], $measurement['reason'] ?? '');
    }







    private function measureStagingSite(string $url): array
    {
        $answer = $this->request($url);
        if ($this->answerCountsAsStagingSiteOpening($url, $answer)) {
            return ['health' => self::HEALTH_REACHABLE, 'url' => $url, 'answer' => $answer];
        }

        if ($answer['code'] === 0 || $answer['code'] >= 500 || strpos($url, '/index.php') !== false) {
            return ['health' => self::HEALTH_UNHEALTHY, 'url' => $url, 'answer' => $answer];
        }

        $urlWithIndexPhp = $url . '/index.php';
        $indexPhpAnswer  = $this->request($urlWithIndexPhp);
        if ($this->answerCountsAsStagingSiteOpening($urlWithIndexPhp, $indexPhpAnswer)) {
            return ['health' => self::HEALTH_REACHABLE, 'url' => $urlWithIndexPhp, 'answer' => $indexPhpAnswer];
        }

        return ['health' => self::HEALTH_UNHEALTHY, 'url' => $url, 'answer' => $answer];
    }







    private function request(string $url): array
    {
        for ($redirect = 0; $redirect <= self::MAX_REDIRECTS; $redirect++) {
            $response = $this->requestWithoutRedirect($url);
            if (empty($response) || is_wp_error($response)) {
                return ['code' => 0, 'body' => ''];
            }

            $code     = (int)wp_remote_retrieve_response_code($response);
            $location = wp_remote_retrieve_header($response, 'location');
            if ($code < 300 || $code >= 400 || !is_string($location) || $location === '') {
                return ['code' => $code, 'body' => (string)wp_remote_retrieve_body($response)];
            }

            $url = \WP_Http::make_absolute_url($location, $url);
        }

        return ['code' => 0, 'body' => ''];
    }





    private function requestWithoutRedirect(string $url)
    {
        if ($this->ssrfProtection->isBlockedUrl($url)) {
            return null;
        }

        return $this->ssrfProtection->runRequestWithPinnedIp($url, function () use ($url) {
            return wp_remote_get($url, [
                'timeout'     => self::REQUEST_TIMEOUT,
                'redirection' => 0,
                'sslverify'   => false,
            ]);
        });
    }









    private function answerCountsAsStagingSiteOpening(string $url, array $answer): bool
    {
        if ($answer['code'] === 0) {
            return $this->isLiveSiteUnreachableFromItself();
        }

        if (in_array($answer['code'], self::PASSWORD_CODES, true)) {
            return true;
        }

        if ($answer['code'] < 200 || $answer['code'] >= 300) {
            return false;
        }

        return $this->isAnswerFromStagingSite($url, $answer['body']);
    }








    private function isLiveSiteUnreachableFromItself(): bool
    {
        if ($this->isLiveSiteUnreachableFromItself === null) {
            $this->isLiveSiteUnreachableFromItself = $this->request(rtrim(home_url(), '/\\'))['code'] === 0;
        }

        return $this->isLiveSiteUnreachableFromItself;
    }









    private function isAnswerFromStagingSite(string $url, string $body): bool
    {
        $folder = $this->folderFromAddress($url);
        if ($folder === '') {
            return true;
        }

        $answeringSite = $this->askWhichSiteAnswered($url);
        if ($answeringSite !== '') {
            return $this->folderFromAddress($answeringSite) === $folder;
        }

        return !$this->pageListsTheFolderFiles($body) && $this->pageMentionsFolder($body, $folder);
    }












    private function folderFromAddress(string $url): string
    {
        return trim(str_replace('/index.php', '', (string)parse_url($url, PHP_URL_PATH)), '/');
    }












    private function pageMentionsFolder(string $body, string $folder): bool
    {
        return strpos($body, '/' . $folder . '/') !== false;
    }











    private function pageListsTheFolderFiles(string $body): bool
    {
        return stripos($body, self::FOLDER_LISTING_TITLE) !== false;
    }









    private function askWhichSiteAnswered(string $url): string
    {
        $separator = strpos($url, '?') === false ? '?' : '&';
        $answer    = $this->request($url . $separator . 'rest_route=/&_fields=url');
        if ($answer['code'] < 200 || $answer['code'] >= 300) {
            return '';
        }

        $identity = json_decode($answer['body'], true);

        return isset($identity['url']) && is_string($identity['url']) ? $identity['url'] : '';
    }















    private function storeResult(string $cloneId, string $health, string $url, array $answer, string $reason = ''): string
    {
        wp_cache_delete(Sites::STAGING_SITES_OPTION, 'options');

        $stagingSites = $this->sites->tryGettingStagingSites();
        if (!isset($stagingSites[$cloneId])) {
            return $health;
        }

        $stagingSites[$cloneId] = array_merge($stagingSites[$cloneId], [
            'health'            => $health,
            'healthUrl'         => $url,
            'healthDiagnostics' => $health === self::HEALTH_UNHEALTHY ? [
                'code'   => $answer['code'],
                'body'   => $this->truncateAnswerBodyToValidUtf8($answer['body']),
                'reason' => $reason !== '' ? $reason : $this->failureReason($answer['code']),
            ] : [],
        ]);

        $this->sites->updateStagingSites($stagingSites);

        return $health;
    }









    private function truncateAnswerBodyToValidUtf8(string $body): string
    {
        $bodyStart = substr($body, 0, self::DIAGNOSTICS_BODY_LIMIT);

        return htmlspecialchars_decode(htmlspecialchars($bodyStart, ENT_NOQUOTES | ENT_SUBSTITUTE, 'UTF-8'), ENT_NOQUOTES);
    }








    private function failureReason(int $statusCode): string
    {
        return $statusCode >= 200 && $statusCode < 300 ? self::REASON_LIVE_SITE : self::REASON_BAD_ANSWER;
    }
}
