<?php

namespace WPStaging\Framework\Hosting;

use WPStaging\Framework\Network\SsrfProtection;
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

 
    const PASSWORD_CODES = [401, 403];

 
    private $sites;

 
    private $ssrfProtection;

 
    private $isLiveSiteUnreachableFromItself;





    public function __construct(Sites $sites, SsrfProtection $ssrfProtection)
    {
        $this->sites          = $sites;
        $this->ssrfProtection = $ssrfProtection;
    }











    public function scheduleCheck(string $cloneId, int $delay = self::DELAY_AFTER_JOB)
    {
        if (empty($cloneId) || wp_next_scheduled(self::ACTION_CHECK_STAGING_SITE, [$cloneId])) {
            return;
        }

        wp_schedule_single_event(time() + $delay, self::ACTION_CHECK_STAGING_SITE, [$cloneId]);
    }











    public function checkStagingSite(string $cloneId): string
    {
        $url = $this->getStagingSiteUrl($cloneId);
        if (empty($url)) {
            return self::HEALTH_UNKNOWN;
        }

        if ($this->ssrfProtection->isBlockedUrl($url)) {
            return $this->storeResult($cloneId, self::HEALTH_UNKNOWN, $url, ['code' => 0, 'body' => '']);
        }

        $answer = $this->request($url);
        if ($this->answerCountsAsStagingSiteOpening($url, $answer)) {
            return $this->storeResult($cloneId, self::HEALTH_REACHABLE, $url, $answer);
        }

        if ($answer['code'] === 0 || $answer['code'] >= 500 || strpos($url, '/index.php') !== false) {
            return $this->storeResult($cloneId, self::HEALTH_UNHEALTHY, $url, $answer);
        }

        $urlWithIndexPhp = $url . '/index.php';
        $indexPhpAnswer  = $this->request($urlWithIndexPhp);
        if ($this->answerCountsAsStagingSiteOpening($urlWithIndexPhp, $indexPhpAnswer)) {
            return $this->storeResult($cloneId, self::HEALTH_REACHABLE, $urlWithIndexPhp, $indexPhpAnswer);
        }

        return $this->storeResult($cloneId, self::HEALTH_UNHEALTHY, $url, $answer);
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
    }





    private function getStagingSiteUrl(string $cloneId): string
    {
        $stagingSites = $this->sites->tryGettingStagingSites();
        if (empty($stagingSites[$cloneId]['url'])) {
            return '';
        }

        return rtrim((string)$stagingSites[$cloneId]['url'], '/\\');
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

        return $this->pageMentionsFolder($body, $folder);
    }












    private function folderFromAddress(string $url): string
    {
        return trim(str_replace('/index.php', '', (string)parse_url($url, PHP_URL_PATH)), '/');
    }












    private function pageMentionsFolder(string $body, string $folder): bool
    {
        return strpos($body, '/' . $folder . '/') !== false;
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














    private function storeResult(string $cloneId, string $health, string $url, array $answer): string
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
                'reason' => $this->failureReason($answer['code']),
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
