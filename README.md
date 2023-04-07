# daily_password

This Drupal module is compatible with Drupal ^8.8, 9 or 10 versions.

## User Case

This module was developed to address the need for granting third-party account access to a restricted part of the site for a limited duration, usually one day. The module automates the process of resetting passwords based on the chosen time interval. If access is required at a different time, users need to contact the administrator for a new password. This module eliminates the need for manual password generation and resetting, which was previously time-consuming and labor-intensive.

## Usage

1. Install and enable the module.
2. Access the settings page and remove the default sample data.
3. Add user account(s) that require automated password changes.
4. Place the included block on the desired part of your site and restrict access only to authorized users who are allowed to grant access to the configured accounts.

## Other features

The module includes the ability to send the password to an endpoint as JSON data. The expected response from the endpoint is a status code of 200. Please note that the password is sent in plain text, so it is recommended to use HTTPS for secure transmission. Originally, the module was designed to send passwords only within the same backend network using PHP, and not over the internet.
