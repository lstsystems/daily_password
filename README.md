# daily_password
This is a Drupa module that can be used with Drupal ^8.8 or 9

This module allows to select a username and set it up for automatic password change on a daily, weekly, montly or yearly basis. It also has a blocks that can be place on the site for an admin to see what the password is.

# User Case
This module came into being do to a need for a third party account access to a restricted part of the site and we didn't want them to have access for more than a day, this module will run and reset the password in one of the four given options then if they need access again at another time they need to contact us to get a new password.

# Usage
1. Install and enable the module.
2. Go to the settings page and remove the default sample data.
3. Add your user account or multiple accounts
4. Using Ultimate_cron set it up to run every day at midnight (0 0 * * * ) or any other time of your choosing.
5. Place the included block in a part of your site you want and restrict it only to those allowed to grant access to the configured accounts.




