## Reporting a Vulnerability

WP Staging, being a major backup plugin in the WordPress environment, 
prioritizes its security immensely. Acknowledging that complete coverage of all 
security aspects is challenging, we encourage our users and the security 
community to directly communicate any security-related discoveries to us. 
This article will guide you on submitting your reports, 
the applicable guidelines, and the rewards you can expect for your contributions.

You can find the latest version of this this document at
[https://wp-staging.com/submit-a-security-report-for-wp-staging/](https://wp-staging.com/submit-a-security-report-for-wp-staging/)

| Severity     | Reward    |
| ------------ | ----------|
| Critical     | $800      |
| High         | $400      |
| Medium       | $200      |
| Low          | $100      |
| Informative  | -         |

The severity is based on the [CVSS (Common Vulnerability Scoring System)](https://www.first.org/cvss/calculator/3.0). 
When submitting your security report, make sure to include a calculation of the CVSS. 
The reward table provides general guidelines, and all final decisions are at the discretion of WP Staging.

## The premises / scope of this program

The scope of this reward program is the latest version of our plugins. Specifically:

- WP Staging free version
- WP Staging Pro version

The scope of this program does not extend to any other products or services related to WP Staging, 
including the WP Staging website and customer portal. However, if you do come across a significant 
security issue within these areas, we strongly encourage you to inform us.

## Issues that are out of scope

- WP Staging version number disclosure.
- Comma Separated Values (CSV) injection without demonstrating a vulnerability.
- Missing best practices in SSL/TLS configuration.
- Content spoofing and text injection issues without showing an attack vector/without being able to modify HTML/CSS.
- Theoretical vulnerabilities where you can’t demonstrate a significant security impact with a Proof of Concept.
- Users with administrator or editor privileges can post arbitrary JavaScript.
- Output from automated scans – please manually verify issues and include a valid Proof of Concept.
- Not following security best practices – without a working Proof of Concept.

## How to get a copy of our plugins for testing

You can get WP Staging free version from wordpress.org at
https://wordpress.org/plugins/wp-staging/

If you want to get WP Staging Pro for security testing, please contact us at support [at] wp-staging.com, providing a plan what you are going to do.

## Conditions and rules

Ensure your reports are detailed and include step-by-step procedures that can be easily followed. Reports that lack sufficient detail for issue reproduction will not qualify for a reward. 

Each report should focus on a single vulnerability, unless demonstrating the impact requires chaining multiple vulnerabilities. 

In cases of duplicate submissions, a reward will be given only to the first received report that can be fully reproduced. 

If multiple vulnerabilities stem from a single root cause, they may be considered as one for the purposes of reward eligibility. 

When conducting tests that might affect systems or services not owned by you, the tester, it is crucial to prioritize privacy, data integrity, and service continuity. Avoid any actions that might compromise these aspects. Interactions should be limited to accounts you own or those where you have received explicit consent from the account holder.

## Disclosure Policy

Please do not discuss any vulnerabilities (even resolved ones) without express consent.

## Submit Your report

If you identify a security concern that complies with the guidelines and scope of this project, kindly forward your findings to us at support @ wp-staging.com. In your email, please ensure you cover the following points:

- Your assessment of the CVSS score, utilizing the [provided calculator](https://www.first.org/cvss/calculator/3.0).
- An explanation of the potential impact of the problem.
- A comprehensive walkthrough detailing the steps to replicate the issue.
- The email address associated with your WP Staging account that you used for registration.

## After Your Report 

Our team is committed to addressing security reports with the utmost diligence and will aim to achieve the following response goals:

- Initial response time (after receiving the report) – within 3 business days
- Time to assess and categorize the report (from the time of submission) – within 10 business days
- Time to determine and issue a bounty (following the assessment phase) – within 10 business days

Throughout this process, we will ensure that you are regularly updated on our progress.
