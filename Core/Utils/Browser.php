<?php

namespace WPStaging\Core\Utils;

use WPStaging\Framework\Facades\Sanitize;

/**
 * Modified to remove var
 * Chris Christoff on 12/26/2012
 * Changes: Changes vars to publics
 *
 * Modified to work for EDD by
 * Chris Christoff on 12/23/2012
 * Changes: Removed the browser string return and added spacing. Also removed return HTML formatting.
 *
 * Modified to add formatted User Agent string for EDD System Info by
 * Chris Christoff on 12/23/2012
 * Changes: Split user string and add formatting so we can print a nicely
 * formatted user agent string on the EDD System Info
 *
 * File: Browser.php
 * Author: Chris Schuld (http://chrisschuld.com/)
 * Last Modified: August 20th, 2010
 * @version 1.9
 * @package PegasusPHP
 *
 * Copyright (C) 2008-2010 Chris Schuld  (chris@chrisschuld.com)
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details at:
 * http://www.gnu.org/copyleft/gpl.html
 *
 *
 * Typical Usage:
 *
 *   $browser = new Browser();
 *   if( $browser->getBrowser() == Browser::BROWSER_FIREFOX && $browser->getVersion() >= 2 ) {
 *      echo 'You have FireFox version 2 or greater';
 *   }
 *
 * User Agents Sampled from: http://www.useragentstring.com/
 *
 * This implementation is based on the original work from Gary White
 * http://apptools.com/phptools/browser/
 *
 * UPDATES:
 *
 * 2010-08-20 (v1.9):
 *  + Added MSN Explorer Browser (legacy)
 *  + Added Bing/MSN Robot (Thanks Rob MacDonald)
 *  + Added the Android Platform (PLATFORM_ANDROID)
 *  + Fixed issue with Android 1.6/2.2 (Thanks Tom Hirashima)
 *
 * 2010-04-27 (v1.8):
 *  + Added iPad Support
 *
 * 2010-03-07 (v1.7):
 *  + *MAJOR* Rebuild (preg_match and other "slow" routine removal(s))
 *  + Almost allof Gary's original code has been replaced
 *  + Large PHPUNIT testing environment created to validate new releases and additions
 *  + Added FreeBSD Platform
 *  + Added OpenBSD Platform
 *  + Added NetBSD Platform
 *  + Added SunOS Platform
 *  + Added OpenSolaris Platform
 *  + Added support of the Iceweazel Browser
 *  + Added isChromeFrame() call to check if chromeframe is in use
 *  + Moved the Opera check in front of the Firefox check due to legacy Opera User Agents
 *  + Added the __toString() method (Thanks Deano)
 *
 * 2009-11-15:
 *  + Updated the checkes for Firefox
 *  + Added the NOKIA platform
 *  + Added Checks for the NOKIA brower(s)
 *
 * 2009-11-08:
 *  + PHP 5.3 Support
 *  + Added support for BlackBerry OS and BlackBerry browser
 *  + Added support for the Opera Mini browser
 *  + Added additional documenation
 *  + Added support for isRobot() and isMobile()
 *  + Added support for Opera version 10
 *  + Added support for deprecated Netscape Navigator version 9
 *  + Added support for IceCat
 *  + Added support for Shiretoko
 *
 * 2010-04-27 (v1.8):
 *  + Added iPad Support
 *
 * 2009-08-18:
 *  + Updated to support PHP 5.3 - removed all deprecated function calls
 *  + Updated to remove all double quotes (") -- converted to single quotes (')
 *
 * 2009-04-27:
 *  + Updated the IE check to remove a typo and bug (thanks John)
 *
 * 2009-04-22:
 *  + Added detection for GoogleBot
 *  + Added detection for the W3C Validator.
 *  + Added detection for Yahoo! Slurp
 *
 * 2009-03-14:
 *  + Added detection for iPods.
 *  + Added Platform detection for iPhones
 *  + Added Platform detection for iPods
 *
 * 2009-02-16: (Rick Hale)
 *  + Added version detection for Android phones.
 *
 * 2008-12-09:
 *  + Removed unused constant
 *
 * 2008-11-07:
 *  + Added Google's Chrome to the detection list
 *  + Added isBrowser(string) to the list of functions special thanks to
 *    Daniel 'mavrick' Lang for the function concept (http://mavrick.id.au)
 *
 *
 * Gary White noted: "Since browser detection is so unreliable, I am
 * no longer maintaining this script. You are free to use and or
 * modify/update it as you want, however the author assumes no
 * responsibility for the accuracy of the detected values."
 *
 * Anyone experienced with Gary's script might be interested in these notes:
 *
 *   Added class constants
 *   Added detection and version detection for Google's Chrome
 *   Updated the version detection for Amaya
 *   Updated the version detection for Firefox
 *   Updated the version detection for Lynx
 *   Updated the version detection for WebTV
 *   Updated the version detection for NetPositive
 *   Updated the version detection for IE
 *   Updated the version detection for OmniWeb
 *   Updated the version detection for iCab
 *   Updated the version detection for Safari
 *   Updated Safari to remove mobile devices (iPhone)
 *   Added detection for iPhone
 *   Added detection for robots
 *   Added detection for mobile devices
 *   Added detection for BlackBerry
 *   Removed Netscape checks (matches heavily with firefox & mozilla)
 *
 */

class Browser
{
    private $userAgent   = '';
    private $browserName = '';
    private $version     = '';
    private $platform    = '';
    private $os          = '';
    private $isAol       = false;
    private $isMobile    = false;
    private $isRobot     = false;
    private $aolVersion  = '';

    public $BROWSER_UNKNOWN = 'unknown';
    public $VERSION_UNKNOWN = 'unknown';

    public $BROWSER_OPERA              = 'Opera';                     // Http://www.opera.com/
    public $BROWSER_OPERA_MINI         = 'Opera Mini';                // Http://www.opera.com/mini/
    public $BROWSER_WEBTV              = 'WebTV';                     // Http://www.webtv.net/pc/
    public $BROWSER_IE                 = 'Internet Explorer';         // Http://www.microsoft.com/ie/
    public $BROWSER_POCKET_IE          = 'Pocket Internet Explorer';  // Http://en.wikipedia.org/wiki/Internet_Explorer_Mobile
    public $BROWSER_KONQUEROR          = 'Konqueror';                 // Http://www.konqueror.org/
    public $BROWSER_ICAB               = 'iCab';                      // Http://www.icab.de/
    public $BROWSER_OMNIWEB            = 'OmniWeb';                   // Http://www.omnigroup.com/applications/omniweb/
    public $BROWSER_FIREBIRD           = 'Firebird';                  // Http://www.ibphoenix.com/
    public $BROWSER_FIREFOX            = 'Firefox';                   // Http://www.mozilla.com/en-US/firefox/firefox.html
    public $BROWSER_ICEWEASEL          = 'Iceweasel';                 // Http://www.geticeweasel.org/
    public $BROWSER_SHIRETOKO          = 'Shiretoko';                 // Http://wiki.mozilla.org/Projects/shiretoko
    public $BROWSER_MOZILLA            = 'Mozilla';                   // Http://www.mozilla.com/en-US/
    public $BROWSER_AMAYA              = 'Amaya';                     // Http://www.w3.org/Amaya/
    public $BROWSER_LYNX               = 'Lynx';                      // Http://en.wikipedia.org/wiki/Lynx
    public $BROWSER_SAFARI             = 'Safari';                    // Http://apple.com
    public $BROWSER_IPHONE             = 'iPhone';                    // Http://apple.com
    public $BROWSER_IPOD               = 'iPod';                      // Http://apple.com
    public $BROWSER_IPAD               = 'iPad';                      // Http://apple.com
    public $BROWSER_CHROME             = 'Chrome';                    // Http://www.google.com/chrome
    public $BROWSER_ANDROID            = 'Android';                   // Http://www.android.com/
    public $BROWSER_GOOGLEBOT          = 'GoogleBot';                 // Http://en.wikipedia.org/wiki/Googlebot
    public $BROWSER_SLURP              = 'Yahoo! Slurp';              // Http://en.wikipedia.org/wiki/Yahoo!_Slurp
    public $BROWSER_W3CVALIDATOR       = 'W3C Validator';             // Http://validator.w3.org/
    public $BROWSER_BLACKBERRY         = 'BlackBerry';                // Http://www.blackberry.com/
    public $BROWSER_ICECAT             = 'IceCat';                    // Http://en.wikipedia.org/wiki/GNU_IceCat
    public $BROWSER_NOKIA_S60          = 'Nokia S60 OSS Browser';     // Http://en.wikipedia.org/wiki/Web_Browser_for_S60
    public $BROWSER_NOKIA              = 'Nokia Browser';             // * all other WAP-based browsers on the Nokia Platform
    public $BROWSER_MSN                = 'MSN Browser';               // Http://explorer.msn.com/
    public $BROWSER_MSNBOT             = 'MSN Bot';                   // Http://search.msn.com/msnbot.htm, Http://en.wikipedia.org/wiki/Msnbot  (used for Bing as well)
    public $BROWSER_NETSCAPE_NAVIGATOR = 'Netscape Navigator';        // Http://browser.netscape.com/ (DEPRECATED)
    public $BROWSER_GALEON             = 'Galeon';                    // Http://galeon.sourceforge.net/ (DEPRECATED)
    public $BROWSER_NETPOSITIVE        = 'NetPositive';               // Http://en.wikipedia.org/wiki/NetPositive (DEPRECATED)
    public $BROWSER_PHOENIX            = 'Phoenix';                   // Http://en.wikipedia.org/wiki/History_of_Mozilla_Firefox (DEPRECATED)

    public $PLATFORM_UNKNOWN     = 'unknown';
    public $PLATFORM_WINDOWS     = 'Windows';
    public $PLATFORM_WINDOWS_CE  = 'Windows CE';
    public $PLATFORM_APPLE       = 'Apple';
    public $PLATFORM_LINUX       = 'Linux';
    public $PLATFORM_OS2         = 'OS/2';
    public $PLATFORM_BEOS        = 'BeOS';
    public $PLATFORM_IPHONE      = 'iPhone';
    public $PLATFORM_IPOD        = 'iPod';
    public $PLATFORM_IPAD        = 'iPad';
    public $PLATFORM_BLACKBERRY  = 'BlackBerry';
    public $PLATFORM_NOKIA       = 'Nokia';
    public $PLATFORM_FREEBSD     = 'FreeBSD';
    public $PLATFORM_OPENBSD     = 'OpenBSD';
    public $PLATFORM_NETBSD      = 'NetBSD';
    public $PLATFORM_SUNOS       = 'SunOS';
    public $PLATFORM_OPENSOLARIS = 'OpenSolaris';
    public $PLATFORM_ANDROID     = 'Android';

    public $OPERATING_SYSTEM_UNKNOWN = 'unknown';

    public function __construct($useragent = "")
    {
        $this->reset();
        if ($useragent != "") {
            $this->setUserAgent($useragent);
        } else {
            $this->determine();
        }
    }

    /**
     * Reset all properties
     * @return void
     */
    public function reset()
    {
        $this->userAgent   = isset($_SERVER['HTTP_USER_AGENT']) ? Sanitize::sanitizeString($_SERVER['HTTP_USER_AGENT']) : "";
        $this->browserName = $this->BROWSER_UNKNOWN;
        $this->version     = $this->VERSION_UNKNOWN;
        $this->platform    = $this->PLATFORM_UNKNOWN;
        $this->os          = $this->OPERATING_SYSTEM_UNKNOWN;
        $this->isAol       = false;
        $this->isMobile    = false;
        $this->isRobot     = false;
        $this->aolVersion  = $this->VERSION_UNKNOWN;
    }

    /**
     * Check to see if the specific browser is valid
     * @param string $browserName
     * @return bool Returns true if the browser is the specified browser
     */
    public function isBrowser($browserName): bool
    {
        return( strcasecmp($this->browserName, trim($browserName)) == 0);
    }

    /**
     * The name of the browser.  All return types are from the class contants
     * @return string Name of the browser
     */
    public function getBrowser(): string
    {
        return $this->browserName;
    }

    /**
     * Set the name of the browser
     * @param $browser The name of the Browser
     * @return void
     */
    public function setBrowser($browser)
    {
        $this->browserName = $browser;
    }

    /**
     * The name of the platform.  All return types are from the class contants
     * @return string Name of the browser
     */
    public function getPlatform(): string
    {
        return $this->platform;
    }

    /**
     * Set the name of the platform
     * @param $platform The name of the Platform
     * @return void
     */
    public function setPlatform($platform)
    {
        $this->platform = $platform;
    }

    /**
     * The version of the browser.
     * @return string Version of the browser (will only contain alpha-numeric characters and a period)
     */
    public function getVersion(): string
    {
        return $this->version;
    }

    /**
     * Set the version of the browser
     * @param $version The version of the Browser
     * @return void
     */
    public function setVersion($version)
    {
        $this->version = preg_replace('/[^0-9,.,a-z,A-Z-]/', '', $version);
    }

    /**
     * The version of AOL.
     * @return string Version of AOL (will only contain alpha-numeric characters and a period)
     */
    public function getAolVersion(): string
    {
        return $this->aolVersion;
    }

    /**
     * Set the version of AOL
     * @param $version The version of AOL
     * @return void
     */
    public function setAolVersion($version)
    {
        $this->aolVersion = preg_replace('/[^0-9,.,a-z,A-Z]/', '', $version);
    }

    /**
     * Is the browser from AOL?
     * @return bool True if the browser is from AOL otherwise false
     */
    public function isAol(): bool
    {
        return $this->isAol;
    }

    /**
     * Is the browser from a mobile device?
     * @return bool True if the browser is from a mobile device otherwise false
     */
    public function isMobile(): bool
    {
        return $this->isMobile;
    }

    /**
     * Is the browser from a robot (ex Slurp,GoogleBot)?
     * @return bool True if the browser is from a robot otherwise false
     */
    public function isRobot(): bool
    {
        return $this->isRobot;
    }

    /**
     * Set the browser to be from AOL
     * @param $isAol
     * @return void
     */
    public function setAol($isAol)
    {
        $this->isAol = $isAol;
    }

    /**
     * Set the Browser to be mobile
     * @param bool $value is the browser a mobile brower or not
     * @return void
     */
    public function setMobile($value = true)
    {
        $this->isMobile = $value;
    }

    /**
     * Set the Browser to be a robot
     * @param bool $value is the browser a robot or not
     * @return void
     */
    public function setRobot($value = true)
    {
        $this->isRobot = $value;
    }

    /**
     * Get the user agent value in use to determine the browser
     * @return string The user agent from the HTTP header
     */
    public function getUserAgent(): string
    {
        return $this->userAgent;
    }

    /**
     * Set the user agent value (the construction will use the HTTP header value - this will overwrite it)
     * @param $agentString The value for the User Agent
     * @return void
     */
    public function setUserAgent($agentString)
    {
        $this->reset();
        $this->userAgent = $agentString;
        $this->determine();
    }

    /**
     * Used to determine if the browser is actually "chromeframe"
     * @since 1.7
     * @return bool True if the browser is using chromeframe
     */
    public function isChromeFrame(): bool
    {
        return( strpos($this->userAgent, "chromeframe") !== false );
    }

    /**
     * Returns a formatted string with a summary of the details of the browser.
     * @return string formatted string with a summary of the browser
     */
    public function __toString(): string
    {
        $text1    = $this->getUserAgent(); //grabs the UA (user agent) string
        $UAline1  = substr($text1, 0, 32); //the first line we print should only be the first 32 characters of the UA string
        $text2    = $this->getUserAgent();//now we grab it again and save it to a string
        $towrapUA = str_replace($UAline1, '', $text2);//the rest of the printoff (other than first line) is equivolent
        // To the whole string minus the part we printed off. IE
        // User Agent:      thefirst32charactersfromUAline1
        //                  the rest of it is now stored in
        //                  $text2 to be printed off
        // But we need to add spaces before each line that is split other than line 1
        $space = '';
        for ($i = 0; $i < 25; $i++) {
            $space .= ' ';
        }

        // Now we split the remaining string of UA ($text2) into lines that are prefixed by spaces for formatting
        $wordwrapped = chunk_split($towrapUA, 32, "\n $space");
        return str_pad("Platform:", 56, ' ', STR_PAD_RIGHT) . print_r($this->getPlatform(), true) . PHP_EOL .
               str_pad("Browser Name:", 56, ' ', STR_PAD_RIGHT) . print_r($this->getBrowser(), true) . PHP_EOL .
               str_pad("Browser Version:", 56, ' ', STR_PAD_RIGHT) . print_r($this->getVersion(), true) . PHP_EOL .
               str_pad("User Agent String:", 56, ' ', STR_PAD_RIGHT) . print_r($UAline1 . ' ' . $towrapUA, true) . PHP_EOL;
    }

    /**
     * Protected routine to calculate and determine what the browser is in use (including platform)
     * @return void
     */
    public function determine()
    {
        $this->checkPlatform();
        $this->checkBrowsers();
        $this->checkForAol();
    }

    /**
     * Protected routine to determine the browser type
     * @return bool True if the browser was detected otherwise false
     */
    public function checkBrowsers(): bool
    {
        return (
           // Well-known, well-used
           // Special Notes:
           // (1) Opera must be checked before FireFox due to the odd
           //     user agents used in some older versions of Opera
           // (2) WebTV is strapped onto Internet Explorer so we must
           //     check for WebTV before IE
           // (3) (deprecated) Galeon is based on Firefox and needs to be
           //     tested before Firefox is tested
           // (4) OmniWeb is based on Safari so OmniWeb check must occur
           //     before Safari
           // (5) Netscape 9+ is based on Firefox so Netscape checks
           //     before FireFox are necessary
           $this->checkBrowserWebTv() ||
           $this->checkBrowserInternetExplorer() ||
           $this->checkBrowserOpera() ||
           $this->checkBrowserGaleon() ||
           $this->checkBrowserNetscapeNavigator9Plus() ||
           $this->checkBrowserFirefox() ||
           $this->checkBrowserChrome() ||
           $this->checkBrowserOmniWeb() ||

           // Common mobile
           $this->checkBrowserAndroid() ||
           $this->checkBrowseriPad() ||
           $this->checkBrowseriPod() ||
           $this->checkBrowseriPhone() ||
           $this->checkBrowserBlackBerry() ||
           $this->checkBrowserNokia() ||

           // Common bots
           $this->checkBrowserGoogleBot() ||
           $this->checkBrowserMSNBot() ||
           $this->checkBrowserSlurp() ||

           // WebKit base check (post mobile and others)
           $this->checkBrowserSafari() ||

           // Everyone else
           $this->checkBrowserNetPositive() ||
           $this->checkBrowserFirebird() ||
           $this->checkBrowserKonqueror() ||
           $this->checkBrowserIcab() ||
           $this->checkBrowserPhoenix() ||
           $this->checkBrowserAmaya() ||
           $this->checkBrowserLynx() ||

           $this->checkBrowserShiretoko() ||
           $this->checkBrowserIceCat() ||
           $this->checkBrowserW3CValidator() ||
           $this->checkBrowserMozilla() /* Mozilla is such an open standard that you must check it last */
        );
    }

    /**
     * Determine if the user is using a BlackBerry (last updated 1.7)
     * @return bool True if the browser is the BlackBerry browser otherwise false
     */
    public function checkBrowserBlackBerry(): bool
    {
        if (stripos($this->userAgent, 'blackberry') !== false) {
            $aresult  = explode("/", stristr($this->userAgent, "BlackBerry"));
            $aversion = explode(' ', $aresult[1]);
            $this->setVersion($aversion[0]);
            $this->browserName = $this->BROWSER_BLACKBERRY;
            $this->setMobile(true);
            return true;
        }

        return false;
    }

    /**
     * Determine if the user is using an AOL User Agent (last updated 1.7)
     * @return bool True if the browser is from AOL otherwise false
     */
    public function checkForAol(): bool
    {
        $this->setAol(false);
        $this->setAolVersion($this->VERSION_UNKNOWN);

        if (stripos($this->userAgent, 'aol') !== false) {
            $aversion = explode(' ', stristr($this->userAgent, 'AOL'));
            $this->setAol(true);
            $this->setAolVersion(preg_replace('/[^0-9\.a-z]/i', '', $aversion[1]));
            return true;
        }

        return false;
    }

    /**
     * Determine if the browser is the GoogleBot or not (last updated 1.7)
     * @return bool True if the browser is the GoogletBot otherwise false
     */
    public function checkBrowserGoogleBot(): bool
    {
        if (stripos($this->userAgent, 'googlebot') !== false) {
            $aresult  = explode('/', stristr($this->userAgent, 'googlebot'));
            $aversion = explode(' ', $aresult[1]);
            $this->setVersion(str_replace(';', '', $aversion[0]));
            $this->browserName = $this->BROWSER_GOOGLEBOT;
            $this->setRobot(true);
            return true;
        }

        return false;
    }

    /**
     * Determine if the browser is the MSNBot or not (last updated 1.9)
     * @return bool True if the browser is the MSNBot otherwise false
     */
    public function checkBrowserMSNBot(): bool
    {
        if (stripos($this->userAgent, "msnbot") !== false) {
            $aresult  = explode("/", stristr($this->userAgent, "msnbot"));
            $aversion = explode(" ", $aresult[1]);
            $this->setVersion(str_replace(";", "", $aversion[0]));
            $this->browserName = $this->BROWSER_MSNBOT;
            $this->setRobot(true);
            return true;
        }

        return false;
    }

    /**
     * Determine if the browser is the W3C Validator or not (last updated 1.7)
     * @return bool True if the browser is the W3C Validator otherwise false
     */
    public function checkBrowserW3CValidator(): bool
    {
        if (stripos($this->userAgent, 'W3C-checklink') !== false) {
            $aresult  = explode('/', stristr($this->userAgent, 'W3C-checklink'));
            $aversion = explode(' ', $aresult[1]);
            $this->setVersion($aversion[0]);
            $this->browserName = $this->BROWSER_W3CVALIDATOR;
            return true;
        } elseif (stripos($this->userAgent, 'W3C_Validator') !== false) {
            // Some of the Validator versions do not delineate w/ a slash - add it back in
            $ua       = str_replace("W3C_Validator ", "W3C_Validator/", $this->userAgent);
            $aresult  = explode('/', stristr($ua, 'W3C_Validator'));
            $aversion = explode(' ', $aresult[1]);
            $this->setVersion($aversion[0]);
            $this->browserName = $this->BROWSER_W3CVALIDATOR;
            return true;
        }
        return false;
    }

    /**
     * Determine if the browser is the Yahoo! Slurp Robot or not (last updated 1.7)
     * @return bool True if the browser is the Yahoo! Slurp Robot otherwise false
     */
    public function checkBrowserSlurp(): bool
    {
        if (stripos($this->userAgent, 'slurp') !== false) {
            $aresult  = explode('/', stristr($this->userAgent, 'Slurp'));
            $aversion = explode(' ', $aresult[1]);
            $this->setVersion($aversion[0]);
            $this->browserName = $this->BROWSER_SLURP;
            $this->setRobot(true);
            $this->setMobile(false);
            return true;
        }

        return false;
    }

    /**
     * Determine if the browser is Internet Explorer or not (last updated 1.7)
     * @return bool True if the browser is Internet Explorer otherwise false
     */
    public function checkBrowserInternetExplorer(): bool
    {
        // Test for v1 - v1.5 IE
        if (stripos($this->userAgent, 'microsoft internet explorer') !== false) {
            $this->setBrowser($this->BROWSER_IE);
            $this->setVersion('1.0');
            $aresult = stristr($this->userAgent, '/');
            if (preg_match('/308|425|426|474|0b1/i', $aresult)) {
                $this->setVersion('1.5');
            }

            return true;
        } elseif (stripos($this->userAgent, 'msie') !== false && stripos($this->userAgent, 'opera') === false) { // Test for versions > 1.5
            // See if the browser is the odd MSN Explorer
            if (stripos($this->userAgent, 'msnb') !== false) {
                $aresult = explode(' ', stristr(str_replace(';', '; ', $this->userAgent), 'MSN'));
                $this->setBrowser($this->BROWSER_MSN);
                $this->setVersion(str_replace(['(',')',';'], '', $aresult[1]));
                return true;
            }

            $aresult = explode(' ', stristr(str_replace(';', '; ', $this->userAgent), 'msie'));
            $this->setBrowser($this->BROWSER_IE);
            $this->setVersion(str_replace(['(',')',';'], '', $aresult[1]));
            return true;
        } elseif (stripos($this->userAgent, 'mspie') !== false || stripos($this->userAgent, 'pocket') !== false) { // Test for Pocket IE
            $aresult = explode(' ', stristr($this->userAgent, 'mspie'));
            $this->setPlatform($this->PLATFORM_WINDOWS_CE);
            $this->setBrowser($this->BROWSER_POCKET_IE);
            $this->setMobile(true);

            if (stripos($this->userAgent, 'mspie') !== false) {
                $this->setVersion($aresult[1]);
            } else {
                $aversion = explode('/', $this->userAgent);
                $this->setVersion($aversion[1]);
            }
            return true;
        }
        return false;
    }

    /**
     * Determine if the browser is Opera or not (last updated 1.7)
     * @return bool True if the browser is Opera otherwise false
     */
    public function checkBrowserOpera(): bool
    {
        if (stripos($this->userAgent, 'opera mini') !== false) {
            $resultant = stristr($this->userAgent, 'opera mini');
            if (preg_match('/\//', $resultant)) {
                $aresult  = explode('/', $resultant);
                $aversion = explode(' ', $aresult[1]);
                $this->setVersion($aversion[0]);
            } else {
                $aversion = explode(' ', stristr($resultant, 'opera mini'));
                $this->setVersion($aversion[1]);
            }
            $this->browserName = $this->BROWSER_OPERA_MINI;
            $this->setMobile(true);
            return true;
        } elseif (stripos($this->userAgent, 'opera') !== false) {
            $resultant = stristr($this->userAgent, 'opera');
            if (preg_match('/Version\/(10.*)$/', $resultant, $matches)) {
                $this->setVersion($matches[1]);
            } elseif (preg_match('/\//', $resultant)) {
                $aresult  = explode('/', str_replace("(", " ", $resultant));
                $aversion = explode(' ', $aresult[1]);
                $this->setVersion($aversion[0]);
            } else {
                $aversion = explode(' ', stristr($resultant, 'opera'));
                $this->setVersion(isset($aversion[1]) ? $aversion[1] : "");
            }
            $this->browserName = $this->BROWSER_OPERA;
            return true;
        }
        return false;
    }

    /**
     * Determine if the browser is Chrome or not (last updated 1.7)
     * @return bool True if the browser is Chrome otherwise false
     */
    public function checkBrowserChrome(): bool
    {
        if (stripos($this->userAgent, 'Chrome') !== false) {
            $aresult  = explode('/', stristr($this->userAgent, 'Chrome'));
            $aversion = explode(' ', $aresult[1]);
            $this->setVersion($aversion[0]);
            $this->setBrowser($this->BROWSER_CHROME);
            return true;
        }

        return false;
    }

    /**
     * Determine if the browser is WebTv or not (last updated 1.7)
     * @return bool True if the browser is WebTv otherwise false
     */
    public function checkBrowserWebTv(): bool
    {
        if (stripos($this->userAgent, 'webtv') !== false) {
            $aresult  = explode('/', stristr($this->userAgent, 'webtv'));
            $aversion = explode(' ', $aresult[1]);
            $this->setVersion($aversion[0]);
            $this->setBrowser($this->BROWSER_WEBTV);
            return true;
        }

        return false;
    }

    /**
     * Determine if the browser is NetPositive or not (last updated 1.7)
     * @return bool True if the browser is NetPositive otherwise false
     */
    public function checkBrowserNetPositive(): bool
    {
        if (stripos($this->userAgent, 'NetPositive') !== false) {
            $aresult  = explode('/', stristr($this->userAgent, 'NetPositive'));
            $aversion = explode(' ', $aresult[1]);
            $this->setVersion(str_replace(['(',')',';'], '', $aversion[0]));
            $this->setBrowser($this->BROWSER_NETPOSITIVE);
            return true;
        }

        return false;
    }

    /**
     * Determine if the browser is Galeon or not (last updated 1.7)
     * @return bool True if the browser is Galeon otherwise false
     */
    public function checkBrowserGaleon(): bool
    {
        if (stripos($this->userAgent, 'galeon') !== false) {
            $aresult  = explode(' ', stristr($this->userAgent, 'galeon'));
            $aversion = explode('/', $aresult[0]);
            $this->setVersion($aversion[1]);
            $this->setBrowser($this->BROWSER_GALEON);
            return true;
        }

        return false;
    }

    /**
     * Determine if the browser is Konqueror or not (last updated 1.7)
     * @return bool True if the browser is Konqueror otherwise false
     */
    public function checkBrowserKonqueror(): bool
    {
        if (stripos($this->userAgent, 'Konqueror') !== false) {
            $aresult  = explode(' ', stristr($this->userAgent, 'Konqueror'));
            $aversion = explode('/', $aresult[0]);
            $this->setVersion($aversion[1]);
            $this->setBrowser($this->BROWSER_KONQUEROR);
            return true;
        }

        return false;
    }

    /**
     * Determine if the browser is iCab or not (last updated 1.7)
     * @return bool True if the browser is iCab otherwise false
     */
    public function checkBrowserIcab(): bool
    {
        if (stripos($this->userAgent, 'icab') !== false) {
            $aversion = explode(' ', stristr(str_replace('/', ' ', $this->userAgent), 'icab'));
            $this->setVersion($aversion[1]);
            $this->setBrowser($this->BROWSER_ICAB);
            return true;
        }

        return false;
    }

    /**
     * Determine if the browser is OmniWeb or not (last updated 1.7)
     * @return bool True if the browser is OmniWeb otherwise false
     */
    public function checkBrowserOmniWeb(): bool
    {
        if (stripos($this->userAgent, 'omniweb') !== false) {
            $aresult  = explode('/', stristr($this->userAgent, 'omniweb'));
            $aversion = explode(' ', isset($aresult[1]) ? $aresult[1] : "");
            $this->setVersion($aversion[0]);
            $this->setBrowser($this->BROWSER_OMNIWEB);
            return true;
        }

        return false;
    }

    /**
     * Determine if the browser is Phoenix or not (last updated 1.7)
     * @return bool True if the browser is Phoenix otherwise false
     */
    public function checkBrowserPhoenix(): bool
    {
        if (stripos($this->userAgent, 'Phoenix') !== false) {
            $aversion = explode('/', stristr($this->userAgent, 'Phoenix'));
            $this->setVersion($aversion[1]);
            $this->setBrowser($this->BROWSER_PHOENIX);
            return true;
        }

        return false;
    }

    /**
     * Determine if the browser is Firebird or not (last updated 1.7)
     * @return bool True if the browser is Firebird otherwise false
     */
    public function checkBrowserFirebird(): bool
    {
        if (stripos($this->userAgent, 'Firebird') !== false) {
            $aversion = explode('/', stristr($this->userAgent, 'Firebird'));
            $this->setVersion($aversion[1]);
            $this->setBrowser($this->BROWSER_FIREBIRD);
            return true;
        }

        return false;
    }

    /**
     * Determine if the browser is Netscape Navigator 9+ or not (last updated 1.7)
     * NOTE: (http://browser.netscape.com/ - Official support ended on March 1st, 2008)
     * @return bool True if the browser is Netscape Navigator 9+ otherwise false
     */
    public function checkBrowserNetscapeNavigator9Plus(): bool
    {
        if (stripos($this->userAgent, 'Firefox') !== false && preg_match('/Navigator\/([^ ]*)/i', $this->userAgent, $matches)) {
            $this->setVersion($matches[1]);
            $this->setBrowser($this->BROWSER_NETSCAPE_NAVIGATOR);
            return true;
        } elseif (stripos($this->userAgent, 'Firefox') === false && preg_match('/Netscape6?\/([^ ]*)/i', $this->userAgent, $matches)) {
            $this->setVersion($matches[1]);
            $this->setBrowser($this->BROWSER_NETSCAPE_NAVIGATOR);
            return true;
        }
        return false;
    }

    /**
     * Determine if the browser is Shiretoko or not (https://wiki.mozilla.org/Projects/shiretoko) (last updated 1.7)
     * @return bool True if the browser is Shiretoko otherwise false
     */
    public function checkBrowserShiretoko(): bool
    {
        if (stripos($this->userAgent, 'Mozilla') !== false && preg_match('/Shiretoko\/([^ ]*)/i', $this->userAgent, $matches)) {
            $this->setVersion($matches[1]);
            $this->setBrowser($this->BROWSER_SHIRETOKO);
            return true;
        }

        return false;
    }

    /**
     * Determine if the browser is Ice Cat or not (http://en.wikipedia.org/wiki/GNU_IceCat) (last updated 1.7)
     * @return bool True if the browser is Ice Cat otherwise false
     */
    public function checkBrowserIceCat(): bool
    {
        if (stripos($this->userAgent, 'Mozilla') !== false && preg_match('/IceCat\/([^ ]*)/i', $this->userAgent, $matches)) {
            $this->setVersion($matches[1]);
            $this->setBrowser($this->BROWSER_ICECAT);
            return true;
        }

        return false;
    }

    /**
     * Determine if the browser is Nokia or not (last updated 1.7)
     * @return bool True if the browser is Nokia otherwise false
     */
    public function checkBrowserNokia(): bool
    {
        if (preg_match("/Nokia([^\/]+)\/([^ SP]+)/i", $this->userAgent, $matches)) {
            $this->setVersion($matches[2]);
            if (stripos($this->userAgent, 'Series60') !== false || strpos($this->userAgent, 'S60') !== false) {
                $this->setBrowser($this->BROWSER_NOKIA_S60);
            } else {
                $this->setBrowser($this->BROWSER_NOKIA);
            }
            $this->setMobile(true);
            return true;
        }

        return false;
    }

    /**
     * Determine if the browser is Firefox or not (last updated 1.7)
     * @return bool True if the browser is Firefox otherwise false
     */
    public function checkBrowserFirefox(): bool
    {
        if (stripos($this->userAgent, 'safari') === false) {
            if (preg_match("/Firefox[\/ \(]([^ ;\)]+)/i", $this->userAgent, $matches)) {
                $this->setVersion($matches[1]);
                $this->setBrowser($this->BROWSER_FIREFOX);
                return true;
            } elseif (preg_match("/Firefox$/i", $this->userAgent, $matches)) {
                $this->setVersion("");
                $this->setBrowser($this->BROWSER_FIREFOX);
                return true;
            }
        }

        return false;
    }

    /**
     * Determine if the browser is Firefox or not (last updated 1.7)
     * @return bool True if the browser is Firefox otherwise false
     */
    public function checkBrowserIceweasel(): bool
    {
        if (stripos($this->userAgent, 'Iceweasel') !== false) {
            $aresult  = explode('/', stristr($this->userAgent, 'Iceweasel'));
            $aversion = explode(' ', $aresult[1]);
            $this->setVersion($aversion[0]);
            $this->setBrowser($this->BROWSER_ICEWEASEL);
            return true;
        }

        return false;
    }

    /**
     * Determine if the browser is Mozilla or not (last updated 1.7)
     * @return bool True if the browser is Mozilla otherwise false
     */
    public function checkBrowserMozilla(): bool
    {
        if (stripos($this->userAgent, 'mozilla') !== false && preg_match('/rv:[0-9].[0-9][a-b]?/i', $this->userAgent) && stripos($this->userAgent, 'netscape') === false) {
            $aversion = explode(' ', stristr($this->userAgent, 'rv:'));
            preg_match('/rv:[0-9].[0-9][a-b]?/i', $this->userAgent, $aversion);
            $this->setVersion(str_replace('rv:', '', $aversion[0]));
            $this->setBrowser($this->BROWSER_MOZILLA);
            return true;
        } elseif (stripos($this->userAgent, 'mozilla') !== false && preg_match('/rv:[0-9]\.[0-9]/i', $this->userAgent) && stripos($this->userAgent, 'netscape') === false) {
            $aversion = explode('', stristr($this->userAgent, 'rv:'));
            $this->setVersion(str_replace('rv:', '', $aversion[0])); // @phpstan-ignore-line
            $this->setBrowser($this->BROWSER_MOZILLA);
            return true;
        } elseif (stripos($this->userAgent, 'mozilla') !== false && preg_match('/mozilla\/([^ ]*)/i', $this->userAgent, $matches) && stripos($this->userAgent, 'netscape') === false) {
            $this->setVersion($matches[1]);
            $this->setBrowser($this->BROWSER_MOZILLA);
            return true;
        }
        return false;
    }

    /**
     * Determine if the browser is Lynx or not (last updated 1.7)
     * @return bool True if the browser is Lynx otherwise false
     */
    public function checkBrowserLynx(): bool
    {
        if (stripos($this->userAgent, 'lynx') !== false) {
            $aresult  = explode('/', stristr($this->userAgent, 'Lynx'));
            $aversion = explode(' ', (isset($aresult[1]) ? $aresult[1] : ""));
            $this->setVersion($aversion[0]);
            $this->setBrowser($this->BROWSER_LYNX);
            return true;
        }

        return false;
    }

    /**
     * Determine if the browser is Amaya or not (last updated 1.7)
     * @return bool True if the browser is Amaya otherwise false
     */
    public function checkBrowserAmaya(): bool
    {
        if (stripos($this->userAgent, 'amaya') !== false) {
            $aresult  = explode('/', stristr($this->userAgent, 'Amaya'));
            $aversion = explode(' ', $aresult[1]);
            $this->setVersion($aversion[0]);
            $this->setBrowser($this->BROWSER_AMAYA);
            return true;
        }

        return false;
    }

    /**
     * Determine if the browser is Safari or not (last updated 1.7)
     * @return bool True if the browser is Safari otherwise false
     */
    public function checkBrowserSafari(): bool
    {
        if (stripos($this->userAgent, 'Safari') !== false && stripos($this->userAgent, 'iPhone') === false && stripos($this->userAgent, 'iPod') === false) {
            $aresult = explode('/', stristr($this->userAgent, 'Version'));
            if (isset($aresult[1])) {
                $aversion = explode(' ', $aresult[1]);
                $this->setVersion($aversion[0]);
            } else {
                $this->setVersion($this->VERSION_UNKNOWN);
            }
            $this->setBrowser($this->BROWSER_SAFARI);
            return true;
        }

        return false;
    }

    /**
     * Determine if the browser is iPhone or not (last updated 1.7)
     * @return bool True if the browser is iPhone otherwise false
     */
    public function checkBrowseriPhone(): bool
    {
        if (stripos($this->userAgent, 'iPhone') !== false) {
            $aresult = explode('/', stristr($this->userAgent, 'Version'));
            if (isset($aresult[1])) {
                $aversion = explode(' ', $aresult[1]);
                $this->setVersion($aversion[0]);
            } else {
                $this->setVersion($this->VERSION_UNKNOWN);
            }
            $this->setMobile(true);
            $this->setBrowser($this->BROWSER_IPHONE);
            return true;
        }

        return false;
    }

    /**
     * Determine if the browser is iPod or not (last updated 1.7)
     * @return bool True if the browser is iPod otherwise false
     */
    public function checkBrowseriPad(): bool
    {
        if (stripos($this->userAgent, 'iPad') !== false) {
            $aresult = explode('/', stristr($this->userAgent, 'Version'));
            if (isset($aresult[1])) {
                $aversion = explode(' ', $aresult[1]);
                $this->setVersion($aversion[0]);
            } else {
                $this->setVersion($this->VERSION_UNKNOWN);
            }
            $this->setMobile(true);
            $this->setBrowser($this->BROWSER_IPAD);
            return true;
        }

        return false;
    }

    /**
     * Determine if the browser is iPod or not (last updated 1.7)
     * @return bool True if the browser is iPod otherwise false
     */
    public function checkBrowseriPod(): bool
    {
        if (stripos($this->userAgent, 'iPod') !== false) {
            $aresult = explode('/', stristr($this->userAgent, 'Version'));
            if (isset($aresult[1])) {
                $aversion = explode(' ', $aresult[1]);
                $this->setVersion($aversion[0]);
            } else {
                $this->setVersion($this->VERSION_UNKNOWN);
            }
            $this->setMobile(true);
            $this->setBrowser($this->BROWSER_IPOD);
            return true;
        }

        return false;
    }

    /**
     * Determine if the browser is Android or not (last updated 1.7)
     * @return bool True if the browser is Android otherwise false
     */
    public function checkBrowserAndroid(): bool
    {
        if (stripos($this->userAgent, 'Android') !== false) {
            $aresult = explode(' ', stristr($this->userAgent, 'Android'));
            if (isset($aresult[1])) {
                $aversion = explode(' ', $aresult[1]);
                $this->setVersion($aversion[0]);
            } else {
                $this->setVersion($this->VERSION_UNKNOWN);
            }
            $this->setMobile(true);
            $this->setBrowser($this->BROWSER_ANDROID);
            return true;
        }

        return false;
    }

    /**
     * Determine the user's platform (last updated 1.7)
     * @return void
     */
    public function checkPlatform()
    {
        if (stripos($this->userAgent, 'windows') !== false) {
            $this->platform = $this->PLATFORM_WINDOWS;
        } elseif (stripos($this->userAgent, 'iPad') !== false) {
            $this->platform = $this->PLATFORM_IPAD;
        } elseif (stripos($this->userAgent, 'iPod') !== false) {
            $this->platform = $this->PLATFORM_IPOD;
        } elseif (stripos($this->userAgent, 'iPhone') !== false) {
            $this->platform = $this->PLATFORM_IPHONE;
        } elseif (stripos($this->userAgent, 'mac') !== false) {
            $this->platform = $this->PLATFORM_APPLE;
        } elseif (stripos($this->userAgent, 'android') !== false) {
            $this->platform = $this->PLATFORM_ANDROID;
        } elseif (stripos($this->userAgent, 'linux') !== false) {
            $this->platform = $this->PLATFORM_LINUX;
        } elseif (stripos($this->userAgent, 'Nokia') !== false) {
            $this->platform = $this->PLATFORM_NOKIA;
        } elseif (stripos($this->userAgent, 'BlackBerry') !== false) {
            $this->platform = $this->PLATFORM_BLACKBERRY;
        } elseif (stripos($this->userAgent, 'FreeBSD') !== false) {
            $this->platform = $this->PLATFORM_FREEBSD;
        } elseif (stripos($this->userAgent, 'OpenBSD') !== false) {
            $this->platform = $this->PLATFORM_OPENBSD;
        } elseif (stripos($this->userAgent, 'NetBSD') !== false) {
            $this->platform = $this->PLATFORM_NETBSD;
        } elseif (stripos($this->userAgent, 'OpenSolaris') !== false) {
            $this->platform = $this->PLATFORM_OPENSOLARIS;
        } elseif (stripos($this->userAgent, 'SunOS') !== false) {
            $this->platform = $this->PLATFORM_SUNOS;
        } elseif (stripos($this->userAgent, 'OS\/2') !== false) {
            $this->platform = $this->PLATFORM_OS2;
        } elseif (stripos($this->userAgent, 'BeOS') !== false) {
            $this->platform = $this->PLATFORM_BEOS;
        } elseif (stripos($this->userAgent, 'win') !== false) {
            $this->platform = $this->PLATFORM_WINDOWS;
        }
    }
}
