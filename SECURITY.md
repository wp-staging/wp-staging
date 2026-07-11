## Reporting a Vulnerability

WP STAGING, as one of the leading backup and staging plugins in the WordPress
ecosystem, takes security very seriously. No software project can cover every
security aspect completely, so we encourage our users and the security
community to report security findings directly to us. This document explains
how to submit a report, which guidelines apply, and how we evaluate and reward
contributions.

You can find the latest version of this policy at
[https://wp-staging.com/submit-a-security-report-for-wp-staging/](https://wp-staging.com/submit-a-security-report-for-wp-staging/)

## Recognition & Rewards

We offer monetary rewards for eligible vulnerability reports through our bug
bounty program. Each report is evaluated individually based on its technical
severity, exploitability, attack prerequisites, affected configurations,
report quality, completeness of the submission, and overall security impact.

While CVSS is an important input to our evaluation, it is not the sole
determining factor. Final severity classifications and reward decisions are
made solely at the discretion of WP STAGING.

## How we assess severity

Severity is primarily assessed using the
[Common Vulnerability Scoring System (CVSS)](https://www.first.org/cvss/calculator/3.0),
together with our own review of exploitability, attack prerequisites, affected
configurations, real-world impact, and other relevant technical factors. CVSS
gives us a common language for classifying vulnerabilities, but it serves as
guidance rather than as an automatic scoring formula.

Every vulnerability is unique. Two reports with similar CVSS scores may
receive different severity classifications or rewards depending on
exploitability, affected environments, report quality, and overall security
impact. Our internal review process, including the exact scoring and weighting
we apply, is not disclosed.

## Scope of this program

This reward program covers the latest release of our plugins:

- The free WP STAGING plugin
- WP Staging Pro

Other products and services related to WP STAGING, including the website and
the customer portal, are not part of this program. If you discover a
significant security issue in these areas anyway, we still want to hear about
it.

## Issues that are out of scope

- Disclosure of the WP STAGING version number.
- CSV (Comma Separated Values) injection without demonstrating a vulnerability.
- Missing best practices in the SSL/TLS configuration.
- Content spoofing and text injection issues without an attack vector, or
  without the ability to modify HTML or CSS.
- Theoretical vulnerabilities without a proof of concept that demonstrates a
  significant security impact.
- The fact that users with administrator or editor privileges can post
  arbitrary JavaScript.
- Raw output from automated scanners. Please verify issues manually and
  include a working proof of concept.
- Deviations from security best practices without a working proof of concept.

## How to get a copy of our plugins for testing

You can download the free WP STAGING plugin from wordpress.org at
[https://wordpress.org/plugins/wp-staging/](https://wordpress.org/plugins/wp-staging/).
If you would like to test WP Staging Pro, contact us at
support [at] wp-staging.com and briefly describe what you plan to test.

## Conditions and rules

- Write detailed reports and include step-by-step instructions that we can
  follow to reproduce the issue. Reports that cannot be reproduced from the
  information provided do not qualify for a reward.
- Reports that appear machine-generated and were not manually verified will
  be closed without a reward.
- Focus each report on a single vulnerability, unless demonstrating the impact
  requires chaining several vulnerabilities.
- For duplicate submissions, only the first reproducible report we receive is
  eligible for a reward. Issues that are already known to us are treated the
  same way.
- If multiple vulnerabilities stem from the same root cause, we may treat them
  as a single issue for reward purposes.
- When your testing could affect systems, services, or data you do not own,
  protect privacy, data integrity, and service availability at all times. Only
  interact with accounts you own or accounts whose holders have given you
  explicit consent.

## Responsible disclosure

Please keep your findings confidential. Do not publish or discuss a
vulnerability, even a resolved one, until a fix is available to our users and
we have agreed on disclosure with you. Coordinated disclosure protects the
many websites that rely on WP STAGING while we work on a fix.

## Submit your report

If you have identified a security issue that matches the guidelines and scope
of this program, send your findings to support [at] wp-staging.com and include
the following:

- The affected plugin and the version(s) you tested.
- Your own CVSS assessment with brief reasoning, using the
  [CVSS calculator](https://www.first.org/cvss/calculator/3.0). Including your
  calculation helps us evaluate your report more efficiently, but it does not
  automatically determine the final severity classification or reward.
- An explanation of the potential impact of the issue.
- A step-by-step walkthrough to reproduce the issue, with a proof of concept
  where appropriate.
- The email address associated with your WP STAGING account.

## After your report

We aim to acknowledge new reports within a few business days and to review
every submission promptly. We will keep you informed while we validate the
issue, work on a fix, and decide on a reward. Response times may vary
depending on the complexity of the issue, and reward decisions are made once
the full impact and the fix are clear.
